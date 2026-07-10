#!/usr/bin/env python3
"""Audit beach cover images and optionally render labeled contact sheets."""

from __future__ import annotations

import argparse
import hashlib
import json
import math
import os
import re
import sqlite3
from collections import defaultdict
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont, ImageOps


def local_path(root: Path, url: str) -> Path | None:
    if url.startswith("/images/"):
        return root / "public" / url.removeprefix("/")
    if url.startswith("/uploads/"):
        return root / url.removeprefix("/")
    return None


def rendered_path(root: Path, row: dict) -> Path | None:
    path = local_path(root, row["cover_image"])
    if path is not None and path.is_file():
        return path
    if not row["cover_image"].startswith("/uploads/admin/beaches/"):
        return path

    directory = root / "uploads/admin/beaches"
    filename = Path(row["cover_image"]).name
    stored_base = re.sub(
        r"(_[0-9a-f]{6,}_[0-9]+)?(_\d+|_placeholder)?\.webp$", "", filename
    )
    candidates: list[Path] = []
    for prefix in (stored_base, row["slug"]):
        candidates.extend(directory.glob(f"{prefix}_*_800.webp"))
        if "-" in prefix:
            candidates.extend(directory.glob(f"{prefix}-*_800.webp"))
        if candidates:
            break
    if not candidates:
        return root / "public/images/beaches/placeholder-beach.webp"
    return max(set(candidates), key=lambda candidate: candidate.stat().st_mtime)


def difference_hash(image: Image.Image) -> int:
    grayscale = ImageOps.grayscale(image).resize((9, 8))
    pixels = list(grayscale.get_flattened_data())
    value = 0
    for y in range(8):
        for x in range(8):
            value = (value << 1) | (pixels[y * 9 + x] > pixels[y * 9 + x + 1])
    return value


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--db", type=Path, required=True)
    parser.add_argument("--root", type=Path, required=True)
    parser.add_argument("--output", type=Path, required=True)
    parser.add_argument("--contact-sheets", action="store_true")
    parser.add_argument("--offset", type=int, default=0)
    parser.add_argument("--limit", type=int)
    args = parser.parse_args()
    args.output.mkdir(parents=True, exist_ok=True)

    connection = sqlite3.connect(args.db)
    connection.row_factory = sqlite3.Row
    all_rows = [
        dict(row)
        for row in connection.execute(
            "SELECT id, slug, name, municipality, lat, lng, cover_image "
            "FROM beaches ORDER BY name COLLATE NOCASE"
        )
    ]
    rows = all_rows[args.offset : args.offset + args.limit if args.limit else None]
    connection.close()

    hash_groups: dict[str, list[dict]] = defaultdict(list)
    hashed_rows: list[dict] = []
    missing: list[dict] = []

    for index, row in enumerate(rows, start=1):
        path = rendered_path(args.root, row)
        row["path"] = str(path) if path else None
        if path is None or not path.is_file():
            missing.append(row)
            continue
        try:
            row["sha256"] = hashlib.sha256(path.read_bytes()).hexdigest()
            with Image.open(path) as image:
                row["width"], row["height"] = image.size
                row["dhash"] = difference_hash(image)
            hash_groups[row["sha256"]].append(row)
            hashed_rows.append(row)
        except Exception as error:  # noqa: BLE001 - audit should report corrupt files
            row["error"] = str(error)
            missing.append(row)
        if index % 25 == 0:
            print(f"audited {index}/{len(rows)}", flush=True)

    exact_duplicates = [group for group in hash_groups.values() if len(group) > 1]
    near_duplicates = []
    for index, first in enumerate(hashed_rows):
        for second in hashed_rows[index + 1 :]:
            distance = (first["dhash"] ^ second["dhash"]).bit_count()
            if distance <= 3 and first["sha256"] != second["sha256"]:
                near_duplicates.append(
                    {
                        "distance": distance,
                        "first": {
                            key: first[key]
                            for key in ("name", "municipality", "cover_image", "path")
                        },
                        "second": {
                            key: second[key]
                            for key in ("name", "municipality", "cover_image", "path")
                        },
                    }
                )

    artifacts = {
        "audit.json": rows,
        "missing.json": missing,
        "exact-duplicates.json": exact_duplicates,
        "near-duplicates.json": near_duplicates,
    }
    for filename, payload in artifacts.items():
        (args.output / filename).write_text(
            json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8"
        )

    if args.contact_sheets:
        font = ImageFont.truetype(
            "/System/Library/Fonts/Supplemental/Arial.ttf", 18
        )
        small_font = ImageFont.truetype(
            "/System/Library/Fonts/Supplemental/Arial.ttf", 15
        )
        columns, per_sheet = 5, 30
        cell_width, cell_height = 380, 280
        for start in range(0, len(rows), per_sheet):
            subset = rows[start : start + per_sheet]
            sheet = Image.new(
                "RGB",
                (
                    columns * cell_width,
                    math.ceil(len(subset) / columns) * cell_height,
                ),
                "white",
            )
            draw = ImageDraw.Draw(sheet)
            for offset, row in enumerate(subset):
                x = (offset % columns) * cell_width
                y = (offset // columns) * cell_height
                try:
                    with Image.open(row["path"]) as source:
                        image = ImageOps.exif_transpose(source).convert("RGB")
                        image.thumbnail((cell_width - 12, 210))
                        background = Image.new(
                            "RGB", (cell_width - 12, 210), (235, 235, 235)
                        )
                        background.paste(
                            image,
                            (
                                (cell_width - 12 - image.width) // 2,
                                (210 - image.height) // 2,
                            ),
                        )
                        sheet.paste(background, (x + 6, y + 4))
                except Exception:  # noqa: BLE001
                    draw.rectangle(
                        (x + 6, y + 4, x + cell_width - 6, y + 214),
                        fill=(220, 80, 80),
                    )
                label = f"{start + offset + 1:03d} {row['name']}"[:40]
                draw.text((x + 7, y + 219), label, font=font, fill="black")
                draw.text(
                    (x + 7, y + 244),
                    str(row["municipality"])[:32],
                    font=small_font,
                    fill=(60, 60, 60),
                )
            sheet.save(
                args.output
                / f"sheet-{(args.offset + start) // per_sheet + 1:02d}.jpg",
                quality=88,
            )

    print(
        json.dumps(
            {
                "rows": len(rows),
                "missing": len(missing),
                "exact_duplicate_groups": len(exact_duplicates),
                "near_duplicate_pairs": len(near_duplicates),
                "output": str(args.output),
            }
        )
    )


if __name__ == "__main__":
    main()
