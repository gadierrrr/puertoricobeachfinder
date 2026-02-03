# Secret History Cleanup Runbook

Use this runbook after rotating leaked secrets to remove them from git history.

## 1) Preconditions

- All leaked keys are already rotated and old keys are revoked.
- Everyone knows history will be rewritten.
- You have a full backup clone of the repository.

## 2) Rewrite history with `git filter-repo`

Install tool (one-time):

```bash
python3 -m pip install --user git-filter-repo
```

Create a replacement file (`replacements.txt`):

```text
<old-google-key>==>REDACTED_GOOGLE_KEY
```

Run rewrite from repository root:

```bash
git filter-repo --replace-text replacements.txt --force
```

## 3) Validate rewrite

```bash
git log --all -S"google-maps-api-key" --oneline
grep -R "<google-key-prefix>" . --exclude-dir=.git --exclude-dir=node_modules
```

Expected: no leaked values in git history and active code.

## 4) Force-push rewritten history

```bash
git push origin --force --all
git push origin --force --tags
```

## 5) Collaborator recovery steps

All collaborators must reset local clones (or re-clone):

```bash
git fetch origin
git checkout main
git reset --hard origin/main
git clean -fd
```

Safer option: delete local clone and re-clone.

## 6) Post-cleanup checks

- Re-run CI secret scan (`gitleaks`).
- Confirm deploy environments use only new keys.
- Keep `.pre-commit-config.yaml` secret hook enabled.
