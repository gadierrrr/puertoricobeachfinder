"""Exercise release refusal/recovery using real temporary Git repos and fake services."""
import importlib.util
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest
from unittest.mock import patch

SCRIPTS = Path(__file__).resolve().parent
spec = importlib.util.spec_from_file_location("release", SCRIPTS / "release.py")
release = importlib.util.module_from_spec(spec)
spec.loader.exec_module(release)
SHA = "a" * 40


class CiGateTests(unittest.TestCase):
    def verify(self, runs, jobs=None):
        responses = [{"workflow_runs": runs}]
        if jobs is not None:
            responses.append({"jobs": jobs})
        with patch.object(release, "api", side_effect=responses):
            return release.verified_run(SHA, wait_seconds=0)

    def workflow(self, **changes):
        return dict(id=1, head_sha=SHA, event="push", head_branch="main",
                    status="completed", conclusion="success", html_url="https://example.test/run", **changes)

    def test_requires_all_three_jobs(self):
        jobs = [dict(name=name, status="completed", conclusion="success") for name in release.REQUIRED_JOBS]
        self.assertEqual(self.verify([self.workflow()], jobs)["id"], 1)
        with self.assertRaises(RuntimeError):
            self.verify([self.workflow()], jobs[:-1])

    def test_skipped_job_is_not_success(self):
        jobs = [dict(name=name, status="completed", conclusion="skipped") for name in release.REQUIRED_JOBS]
        with self.assertRaises(RuntimeError):
            self.verify([self.workflow()], jobs)

    def test_wrong_commit_or_event_cannot_authorize_release(self):
        for change in [{"head_sha": "b" * 40}, {"event": "pull_request"}, {"head_branch": "feature"}]:
            run = self.workflow()
            run.update(change)
            with self.subTest(change=change), self.assertRaises(RuntimeError):
                self.verify([run])

    def test_latest_failed_or_pending_run_blocks_older_success(self):
        for status, conclusion in [("completed", "failure"), ("completed", "cancelled"), ("in_progress", None)]:
            newer = self.workflow()
            newer.update(id=2, status=status, conclusion=conclusion)
            with self.subTest(status=status, conclusion=conclusion), self.assertRaises(RuntimeError):
                self.verify([self.workflow(), newer])

    def test_merge_uses_existing_policy_and_pins_head(self):
        before = dict(base={"ref": "main"}, head={"sha": SHA}, merged=False, state="open", draft=False)
        after = dict(merged=True, merge_commit_sha="b" * 40)
        with patch.object(release, "api", side_effect=[before, after]), patch.object(release, "run") as run:
            self.assertEqual(release.merge_pr(57), "b" * 40)
        command = run.call_args_list[1].args
        self.assertIn("--match-head-commit", command)
        self.assertIn(SHA, command)
        self.assertNotIn("--admin", command)


FAKE_SERVICE = r'''#!/usr/bin/env python3
import json, os, pathlib, sys
name = pathlib.Path(sys.argv[0]).name
args = sys.argv[1:]
root = pathlib.Path.cwd()
scenario = os.environ.get("RELEASE_SCENARIO", "")
new_code = (root / "version").read_text().strip() == "new"
with open(os.environ["RELEASE_TRACE"], "a") as log:
    log.write(name + " " + " ".join(args) + "\n")
if name == "php":
    if args[0] == "-r":
        os.execv(os.environ["REAL_PHP"], ["php"] + args)
    if args[0].endswith("backup-db.php"):
        if scenario == "backup-failure": sys.exit(2)
        backup = root / "backups" / "test.sqlite"
        backup.write_text("fixture")
        print(json.dumps({"ok": True, "backup_path": str(backup)}))
    elif args[0].endswith("restore-smoke-test.php"):
        if scenario == "restore-failure": sys.exit(2)
    elif args[0].endswith("migrate.php"):
        if "--check" in args:
            if scenario == "migration-check-error" and new_code: sys.exit(255)
            if scenario.startswith("migration-") and new_code and not (root / "migrated").exists(): sys.exit(1)
        else:
            if scenario == "migration-failure": sys.exit(2)
            (root / "migrated").write_text("done")
elif name == "curl":
    if scenario in ("smoke-failure", "migration-smoke-failure") and new_code: sys.exit(22)
    body = '{"ok":true}' if "health/" in args[-1] else "<html>OK</html>"
    status = "301" if scenario == "redirect" and new_code else "200"
    sys.stdout.write(body + ("\n" + status if "--write-out" in args else ""))
elif name == "flock":
    if scenario == "locked": sys.exit(1)
elif name == "id":
    print("0")
elif name == "systemctl":
    if scenario in ("reload-failure", "migration-reload-failure") and new_code: sys.exit(1)
'''


class ServerReleaseTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.base = Path(self.temp.name)
        self.origin = self.base / "origin"
        self.server = self.base / "server"
        self.origin.mkdir()
        self.env = dict(os.environ, GIT_CONFIG_GLOBAL=os.devnull, GIT_CONFIG_NOSYSTEM="1",
                        GIT_AUTHOR_NAME="Release Test", GIT_AUTHOR_EMAIL="test@example.test",
                        GIT_COMMITTER_NAME="Release Test", GIT_COMMITTER_EMAIL="test@example.test")
        self.git(self.origin, "init", "-b", "main")
        (self.origin / "version").write_text("old\n")
        self.git(self.origin, "add", ".")
        self.git(self.origin, "commit", "-m", "old")
        self.old = self.git(self.origin, "rev-parse", "HEAD")
        self.git(self.base, "clone", str(self.origin), str(self.server))
        (self.origin / "version").write_text("new\n")
        self.git(self.origin, "commit", "-am", "new")
        self.new = self.git(self.origin, "rev-parse", "HEAD")
        bin_dir = self.base / "bin"
        bin_dir.mkdir()
        for name in ["php", "curl", "flock", "id", "systemctl"]:
            path = bin_dir / name
            path.write_text(FAKE_SERVICE)
            path.chmod(0o755)
        self.trace = self.base / "trace"
        self.env.update(PATH=str(bin_dir) + os.pathsep + os.environ["PATH"],
                        REAL_PHP=shutil.which("php"), RELEASE_TRACE=str(self.trace))

    def git(self, cwd, *args):
        return subprocess.run(["git", *args], cwd=cwd, env=self.env, text=True,
                              check=True, capture_output=True).stdout.strip()

    def deploy(self, scenario="", mode="deploy", target=None):
        self.env["RELEASE_SCENARIO"] = scenario
        return subprocess.run(["bash", str(SCRIPTS / "deploy-server.sh"),
                               target or self.new, str(self.server), mode],
                              env=self.env, text=True, capture_output=True)

    def head(self):
        return self.git(self.server, "rev-parse", "HEAD")

    def test_success_and_repeat_skip_backup(self):
        first = self.deploy()
        self.assertEqual(first.returncode, 0, first.stdout + first.stderr)
        self.assertEqual(self.head(), self.new)
        trace = self.trace.read_text()
        self.assertEqual(trace.count("php scripts/backup-db.php"), 1)
        self.assertNotIn("npm", trace)
        second = self.deploy()
        self.assertEqual(second.returncode, 0, second.stdout + second.stderr)
        self.assertEqual(self.trace.read_text().count("php scripts/backup-db.php"), 1)
        self.assertEqual(self.trace.read_text().count("systemctl reload php8.3-fpm"), 2)
        self.assertIn("Already deployed", second.stdout)

    def test_check_does_not_update_or_backup(self):
        result = self.deploy(mode="check")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(self.head(), self.old)
        self.assertFalse((self.server / "backups").exists())

    def test_retry_after_migration_failure_reloads_php(self):
        first = self.deploy("migration-reload-failure")
        self.assertNotEqual(first.returncode, 0)
        self.assertEqual(self.head(), self.new)
        # Simulate a retry after the service issue was resolved. The database
        # migration completed, but the prior release never finished.
        self.trace.write_text("")
        retry = self.deploy()
        self.assertEqual(retry.returncode, 0, retry.stdout + retry.stderr)
        self.assertIn("systemctl reload php8.3-fpm", self.trace.read_text())
        self.assertNotIn("php scripts/backup-db.php", self.trace.read_text())

    def test_redirected_page_does_not_pass_smoke(self):
        result = self.deploy("redirect")
        self.assertNotEqual(result.returncode, 0)
        self.assertEqual(self.head(), self.old)
        self.assertIn("Previous code restored", result.stderr)

    def test_dirty_tree_stops_before_backup(self):
        (self.server / "version").write_text("local work")
        result = self.deploy()
        self.assertNotEqual(result.returncode, 0)
        self.assertEqual((self.server / "version").read_text(), "local work")
        self.assertFalse((self.server / "backups").exists())

    def test_untracked_runtime_data_survives(self):
        runtime = self.server / "private-export.csv"
        runtime.write_text("private fixture")
        result = self.deploy()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(runtime.read_text(), "private fixture")

    def test_main_advance_stops_before_backup(self):
        (self.origin / "version").write_text("newer\n")
        self.git(self.origin, "commit", "-am", "advanced")
        result = self.deploy()
        self.assertNotEqual(result.returncode, 0)
        self.assertEqual(self.head(), self.old)
        self.assertFalse((self.server / "backups").exists())

    def test_backup_and_restore_failures_leave_code_untouched(self):
        for scenario in ["backup-failure", "restore-failure"]:
            # Distinct timestamp directories for separate attempts in the same second.
            shutil.rmtree(self.server / "backups", ignore_errors=True)
            result = self.deploy(scenario)
            with self.subTest(scenario=scenario):
                self.assertNotEqual(result.returncode, 0)
                self.assertEqual(self.head(), self.old)

    def test_failure_before_migration_restores_previous_code(self):
        for scenario in ["smoke-failure", "reload-failure", "migration-check-error"]:
            shutil.rmtree(self.server / "backups", ignore_errors=True)
            result = self.deploy(scenario)
            with self.subTest(scenario=scenario):
                self.assertNotEqual(result.returncode, 0)
                self.assertEqual(self.head(), self.old, result.stdout + result.stderr)
                self.assertIn("Previous code restored", result.stderr)

    def test_migrations_apply_once(self):
        result = self.deploy("migration-success")
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        self.assertEqual(self.head(), self.new)
        self.assertTrue((self.server / "migrated").exists())

    def test_failure_after_migration_requires_manual_recovery(self):
        result = self.deploy("migration-smoke-failure")
        self.assertNotEqual(result.returncode, 0)
        self.assertEqual(self.head(), self.new)
        self.assertIn("Manual recovery is required", result.stderr)

    def test_failed_migration_keeps_recovery_record(self):
        result = self.deploy("migration-failure")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("Manual recovery is required", result.stderr)
        record = next((self.server / "backups" / "releases").glob("*/release.txt")).read_text()
        self.assertIn("migration_started=yes", record)
        self.assertIn("status=failed", record)
        self.assertIn(self.old, record)

    def test_release_lock_blocks_update(self):
        result = self.deploy("locked")
        self.assertNotEqual(result.returncode, 0)
        self.assertEqual(self.head(), self.old)
        self.assertFalse((self.server / "backups").exists())


if __name__ == "__main__":
    unittest.main()
