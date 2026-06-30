---
name: bump-uilibraries-cache
description: Bump the UILibraries cache-busting datestamp. Use when shipping UI/client changes that must reach users immediately past Cloudflare's CDN cache, or when references to Core/UILibrariesYYYYMMDD.js are stale / out of sync with the actual file. Renames the bundle to today's date and rewrites every UILibrariesYYYYMMDD reference repo-wide. Orchestrates DevTools/bump-uilibraries-cache.py.
---

# Bump UILibraries cache-bust datestamp

The browser-facing UI bundle is `Core/UILibraries<YYYYMMDD>.js`. The datestamp in the
**filename** is the cache-bust lever: Cloudflare caches by URL, so changing the name
forces every client to fetch the new bundle instead of a stale CDN copy. Bump it
whenever a UILibraries change must go live immediately.

This skill wraps `DevTools/bump-uilibraries-cache.py`, which makes the whole operation
atomic — rename the file **and** fix every reference in one pass.

## Usage

Bump to today's date (the normal case):

```
python3 DevTools/bump-uilibraries-cache.py
```

Preview without writing:

```
python3 DevTools/bump-uilibraries-cache.py --dry-run
```

Pin a specific stamp (rarely needed):

```
python3 DevTools/bump-uilibraries-cache.py --stamp 20260815
```

## What it does

1. Computes the new stamp (today, or `--stamp YYYYMMDD`).
2. Renames `Core/UILibraries20*.js` → `Core/UILibraries<stamp>.js` with a **plain `mv`**
   (never `git mv` — staging stays the user's manual step).
3. Rewrites **every** `UILibrariesYYYYMMDD` token across all git-tracked files to the new
   stamp: the live `<script src>` + its `filemtime()` call in `NextTurn.php`, plus every
   doc/comment reference (e.g. the GameLayout comments, the integration-test plan).

It is **idempotent**: if the bundle was already renamed by hand, re-running just brings
the stale references back in sync. It only matches the stamped token, so the unstamped
`Core/UILibraries.php` include is never touched.

## Verify

The script prints the rename and a per-file count of references updated. Confirm nothing
stale remains:

```
grep -rIno -E "UILibraries20[0-9]{6}" . --exclude-dir=.git | grep -v "$(date +%Y%m%d)"
```

(no output = every reference is on today's stamp). The user commits the rename +
edits manually.
