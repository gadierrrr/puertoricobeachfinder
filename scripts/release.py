#!/usr/bin/env python3
"""Deploy the exact main commit that passed CI, using existing gh/SSH access."""
import argparse
import json
from pathlib import Path
import re
import shlex
import subprocess
import sys
import time

REPO = "gadierrrr/puertoricobeachfinder"
REQUIRED_JOBS = {"build-and-validate (8.3)", "build-and-validate (8.4)", "secret-scan"}


def run(*args, **kwargs):
    return subprocess.run(args, check=True, text=True, **kwargs)


def api(path):
    return json.loads(run("gh", "api", f"repos/{REPO}/{path}", capture_output=True).stdout)


def verified_run(sha, wait_seconds=600):
    deadline = time.monotonic() + wait_seconds
    announced = False
    while True:
        runs = api(f"actions/workflows/ci.yml/runs?branch=main&event=push&head_sha={sha}&per_page=10")["workflow_runs"]
        matching = [r for r in runs if r["head_sha"] == sha and r["event"] == "push" and r["head_branch"] == "main"]
        latest = max(matching, key=lambda r: r["id"]) if matching else None
        if latest and latest["status"] == "completed":
            if latest["conclusion"] != "success":
                raise RuntimeError(f"CI did not pass: {latest['html_url']}")
            jobs = api(f"actions/runs/{latest['id']}/jobs?filter=latest&per_page=100")["jobs"]
            passed = {j["name"] for j in jobs if j["status"] == "completed" and j["conclusion"] == "success"}
            if not REQUIRED_JOBS <= passed:
                raise RuntimeError("CI is missing successful required jobs: " + ", ".join(sorted(REQUIRED_JOBS - passed)))
            return latest
        if time.monotonic() >= deadline:
            raise RuntimeError("No completed CI run for this exact main commit yet. Run ./deploy.sh again when CI finishes.")
        if not announced:
            print(f"Waiting for CI on {sha[:12]}…", flush=True)
            announced = True
        time.sleep(10)


def merge_pr(number):
    pr = api(f"pulls/{number}")
    if pr["base"]["ref"] != "main":
        raise RuntimeError("Only pull requests targeting main can be released.")
    if pr["merged"]:
        return pr["merge_commit_sha"]
    if pr["state"] != "open" or pr["draft"]:
        raise RuntimeError("The pull request must be open and ready for review.")
    run("gh", "pr", "checks", str(number), "--repo", REPO, "--watch", "--fail-fast", "--interval", "10")
    # GitHub enforces the existing review policy. Never fall back to --admin.
    run("gh", "pr", "merge", str(number), "--repo", REPO, "--merge", "--match-head-commit", pr["head"]["sha"])
    merged = api(f"pulls/{number}")
    if not merged["merged"]:
        raise RuntimeError("The pull request has not merged. Production was not changed.")
    return merged["merge_commit_sha"]


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    group = parser.add_mutually_exclusive_group()
    group.add_argument("--check", action="store_true", help="check CI, server state and live health without deploying")
    group.add_argument("--pr", type=int, help="wait for PR checks, merge under existing rules, then deploy")
    args = parser.parse_args()
    started = time.monotonic()
    sha = merge_pr(args.pr) if args.pr else api("commits/main")["sha"]
    if not re.fullmatch(r"[0-9a-f]{40}", sha):
        raise RuntimeError("GitHub returned an invalid commit ID.")
    ci = verified_run(sha, wait_seconds=0 if args.check else 600)
    print(f"CI passed: {ci['html_url']}\nRelease: {sha}", flush=True)
    # Only tracked source is fetched on the server. Local .env, data and exports
    # never enter a release archive or SSH upload.
    script = Path(__file__).with_name("deploy-server.sh").read_text()
    command = shlex.join(["bash", "-s", "--", sha, "/var/www/beach-finder", "check" if args.check else "deploy"])
    run("ssh", "-o", "BatchMode=yes", "-o", "ConnectTimeout=10", "beach-prod", command, input=script)
    print(f"{'Check' if args.check else 'Release'} completed in {time.monotonic() - started:.1f}s.", flush=True)


if __name__ == "__main__":
    try:
        main()
    except (RuntimeError, subprocess.CalledProcessError, OSError) as error:
        print(f"Release stopped: {error}", file=sys.stderr)
        sys.exit(1)
