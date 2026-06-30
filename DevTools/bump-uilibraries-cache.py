#!/usr/bin/env python3
"""Bump the UILibraries cache-busting datestamp.

The browser-facing UI bundle lives at ``Core/UILibraries<YYYYMMDD>.js``. The datestamp
baked into the *filename* is how we bust Cloudflare's cache: change the name and every
client is forced to fetch the new bundle instead of a stale CDN copy.

This script makes that one operation atomic and complete:
  1. Pick the new stamp: today's date (UTC-naive local), or --stamp YYYYMMDD.
  2. Rename the current ``Core/UILibraries20*.js`` to ``Core/UILibraries<stamp>.js``
     using a plain filesystem move (NEVER ``git mv`` — staging is the user's job).
  3. Rewrite EVERY ``UILibraries20YYMMDD`` token across all git-tracked files to the
     new stamp (the live <script> src + filemtime() call, plus doc/comment references).

It is idempotent: re-running when the file is already named for today's date only
fixes stale references (the common case — the file was renamed by hand first).

Usage:
    python3 DevTools/bump-uilibraries-cache.py            # use today's date
    python3 DevTools/bump-uilibraries-cache.py --stamp 20260815
    python3 DevTools/bump-uilibraries-cache.py --dry-run  # show changes, write nothing
"""
import argparse
import datetime
import re
import subprocess
import sys
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent.parent
STAMP_RE = re.compile(r"UILibraries20\d{6}")          # matches the stamped token only
BUNDLE_GLOB = "Core/UILibraries20*.js"                # the physical bundle file


def find_bundle() -> Path | None:
    matches = sorted(REPO_ROOT.glob(BUNDLE_GLOB))
    if not matches:
        return None
    if len(matches) > 1:
        names = ", ".join(m.name for m in matches)
        sys.exit(f"ERROR: expected one {BUNDLE_GLOB}, found {len(matches)}: {names}\n"
                 f"Resolve the duplicates by hand first.")
    return matches[0]


def tracked_text_files() -> list[Path]:
    out = subprocess.run(
        ["git", "-C", str(REPO_ROOT), "ls-files"],
        capture_output=True, text=True, check=True,
    ).stdout.splitlines()
    return [REPO_ROOT / p for p in out if p]


def main() -> int:
    ap = argparse.ArgumentParser(description="Bump the UILibraries cache-busting datestamp.")
    ap.add_argument("--stamp", help="Target stamp YYYYMMDD (default: today).")
    ap.add_argument("--dry-run", action="store_true", help="Report changes without writing.")
    args = ap.parse_args()

    if args.stamp:
        if not re.fullmatch(r"20\d{6}", args.stamp):
            sys.exit(f"ERROR: --stamp must be YYYYMMDD (e.g. 20260815), got '{args.stamp}'.")
        stamp = args.stamp
    else:
        stamp = datetime.date.today().strftime("%Y%m%d")

    new_token = f"UILibraries{stamp}"
    new_bundle_name = f"UILibraries{stamp}.js"

    bundle = find_bundle()
    if bundle is None:
        sys.exit(f"ERROR: no {BUNDLE_GLOB} found under {REPO_ROOT}. Nothing to bump.")

    # 1. Rename the physical bundle (plain mv, never git mv).
    if bundle.name == new_bundle_name:
        print(f"bundle: {bundle.name} already at target stamp (no rename)")
    else:
        target = bundle.with_name(new_bundle_name)
        if target.exists():
            sys.exit(f"ERROR: {target.name} already exists; refusing to clobber.")
        if args.dry_run:
            print(f"bundle: would rename {bundle.name} -> {new_bundle_name}")
        else:
            bundle.rename(target)
            print(f"bundle: renamed {bundle.name} -> {new_bundle_name}")

    # 2. Rewrite every stamped reference across tracked files.
    total_refs = 0
    touched: list[tuple[str, int]] = []
    for path in tracked_text_files():
        if not path.is_file():
            continue
        try:
            text = path.read_text(encoding="utf-8")
        except (UnicodeDecodeError, IsADirectoryError):
            continue  # skip binaries
        new_text = STAMP_RE.sub(new_token, text)
        if new_text == text:
            continue  # no stamps, or every stamp already correct
        # Count only the tokens that were actually stale (not already == new_token).
        changed = sum(1 for m in STAMP_RE.finditer(text) if m.group(0) != new_token)
        rel = path.relative_to(REPO_ROOT)
        touched.append((str(rel), changed))
        total_refs += changed
        if not args.dry_run:
            path.write_text(new_text, encoding="utf-8")

    verb = "would update" if args.dry_run else "updated"
    if touched:
        print(f"refs: {verb} {total_refs} reference(s) in {len(touched)} file(s) -> {new_token}")
        for rel, n in touched:
            print(f"  {rel} ({n})")
    else:
        print(f"refs: all references already at {new_token} (nothing to update)")

    if args.dry_run:
        print("(dry run — no files written)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
