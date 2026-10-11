---
name: swu-json-from-swudb-qr
description: Use when a swudb deck link arrives as a QR code — a screenshot, photo, or deck render with a QR in the corner — and the deck's JSON is wanted on disk. Also use when asked to pull a decklist out of a deck image, to "get the deck JSON from this QR", or when the link printed under a QR is too ambiguous to retype by hand. Orchestrates DevTools/swu-deck-json-from-qr.py.
---

# Deck QR → swudb deck JSON

Deck renders carry their source link twice in the corner: as a QR code, and as printed
text under it. This turns that image into the deck's JSON file:

```
image → QR decode → https://swudb.com/deck/<id> → swudb getDeckJson → <output>/<filename>
```

`DevTools/swu-deck-json-from-qr.py` does the whole run. It needs no container, no
database, and no deps you have to install.

**First run takes ~20s** and prints `bootstrapping OpenCV into a cached venv` — that is
expected, not a hang (pip runs quiet, so it looks idle). Allow a generous timeout on the
first call; later runs reuse the venv and take ~1s.

The cache is `otmtcge-deckqr-venv` inside Python's temp dir — on macOS that is the
per-user `$TMPDIR` (`/var/folders/...`), **not** `/tmp`. So it is per-user, and a caller
with a different `TMPDIR` gets its own 20s bootstrap. Print the exact path with
`python3 -c "import tempfile,os;print(os.path.join(tempfile.gettempdir(),'otmtcge-deckqr-venv'))"`;
delete it to force a rebuild.

## Usage

Both params are **required**:

```
python3 DevTools/swu-deck-json-from-qr.py <image> --output <dir> --filename <name.json>
```

That form assumes the repo root is your working directory. From anywhere else, invoke
the script by absolute path — `python3 <repo>/DevTools/swu-deck-json-from-qr.py ...`.
`--output` still resolves against the repo root either way.

- `--output` — directory to write into. A relative path resolves **against the repo
  root**, which the script locates from its own path (it lives in `<repo>/DevTools/`), so
  the same command works from any working directory. Absolute and `~` paths are used as
  given. Created if missing.
- `--filename` — file name to write. `.json` is appended if you leave it off.

Other flags: `--link <url>` (skip the QR, use a URL you already have), `--force`
(overwrite an existing file), `--print` (also echo the JSON to stdout — the file is still
written), `--verbose` (log each QR pass).

The caller decides where output goes; this tool has no canonical home directory and no
coupling to any other pipeline. Create the target dir wherever the work belongs.

Example:

```
python3 DevTools/swu-deck-json-from-qr.py /tmp/deck-photo.png \
    --output SomeDir/deck-captures --filename tarfful_HMW_blue.json
```

A successful run prints (to stderr) — the destination is echoed on the `wrote` line:

```
  link: https://swudb.com/deck/jhvyLyLIbtu  (qr, full@1x/gray)
  wrote /Users/you/repo/SomeDir/deck-captures/tarfful_HMW_blue.json
    name      : STAR WARS DAD Tarfful Blue   by StarWarsDad
    source    : https://swudb.com/deck/jhvyLyLIbtu
    leader    : HMW_010   base: HMW_021
    deck      : 51 cards (20 unique)
    sideboard : 10 cards (6 unique)
```

`(qr, full@1x/gray)` is provenance: which decode pass won. Any `qr` value means the code
was decoded exactly — that is the good case. See below for when it says something else.

## What lands on disk

swudb's `getDeckJson` response, **byte-for-byte** — swudb already emits 2-space pretty
JSON, so nothing is reshaped or re-serialized:

```json
{ "metadata": {"name": "...", "author": "..."},
  "leader": {"id": "HMW_010", "count": 1},
  "secondleader": null,
  "base": {"id": "HMW_021", "count": 1},
  "deck":      [{"id": "LOF_070", "count": 3}, ...],
  "sideboard": [{"id": "LAW_149", "count": 2}, ...] }
```

**Card IDs are whatever swudb lists** — a reprint stays the printing the deck was built
with. There is deliberately no SWUSim involvement, so no reprint aliasing
(`HMW_022` is NOT rewritten to `JTL_020`) and no legality check. If you need either, run
the deck through `SWUResolveDeckInput()` separately; that is a different job.

## Never retype the printed link

Decode the QR; do not read the text under it. Deck IDs are case-sensitive base62, so
`I` vs `l` and `O` vs `0` are a coin flip in most fonts — and a one-character misread
lands on a different deck or a 404 with nothing to flag it. The tool decodes the QR and
only falls back to OCR (macOS Vision) when the QR won't read, printing a loud WARNING
when it does. On that warning, confirm the id before trusting the file.

## When it fails

Reading the image:

| Message | Meaning |
|---|---|
| `No deck link found in <image>` | QR unreadable and OCR missed. Get a sharper capture, or pass `--link`. |
| `WARNING: ... fell back to ocr-vision` | Link was *read*, not decoded. Verify the id by eye. Not fatal — the file is still written. |

Reaching the deck (fires on `--link` too, or when a QR points somewhere unexpected):

| Message | Meaning |
|---|---|
| `swudb returned HTTP 404` | Deck is private or deleted — or the id was misread. |
| `Not a swudb link` | The link is not swudb at all (e.g. melee.gg). Other builders have no such API; import those through SWUSim. |

Writing the file:

| Message | Meaning |
|---|---|
| `<path> already exists` | Pass `--force` to overwrite. |

The tool exits non-zero on all of these and writes nothing — except the OCR warning,
which is advisory.

## Verify

The summary it prints is the check — confirm the deck name and leader/base match the
deck you meant to capture, and that the counts look sane. It flags a main deck under 50
or a sideboard over 10 as a NOTE (Premier limits) without editing anything, since an
intentionally partial list is legitimate.
