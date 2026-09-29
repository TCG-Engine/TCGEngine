#!/usr/bin/env python3
"""Turn a QR capture of a deck link into that deck's swudb JSON file.

A photo or screenshot of a deck render carries its swudb link as a QR code. This walks
the whole way from that image to a JSON file on disk:

    image -> QR decode -> https://swudb.com/deck/<id> -> swudb getDeckJson -> <out>/<name>.json

Usage:
    python3 DevTools/swu-deck-json-from-qr.py <image> --output <dir> --filename <name.json>

  <image>       One capture holding the QR (png / jpg / webp -- anything Pillow reads).
  --output      REQUIRED. Directory to write into, RELATIVE TO THE REPO ROOT (an absolute
                path is honoured as given). Created if missing.
  --filename    REQUIRED. File name to write (".json" appended when absent).
  --link        Skip the QR pass and use this deck URL (escape hatch for an unreadable QR).
  --force       Overwrite an existing output file.
  --print       Also echo the JSON to stdout.
  --verbose     Log each QR pass to stderr.

The written file is swudb's `getDeckJson` response BYTE-FOR-BYTE:

    {"metadata": {...}, "leader": {...}, "secondleader": ..., "base": {...},
     "deck": [{"id": "LOF_070", "count": 3}, ...], "sideboard": [...]}

Nothing is reshaped, aliased or re-serialized -- swudb already emits 2-space pretty JSON,
and `secondleader` (Twin Suns) passes through as-is. Deliberately NO SWUSim involvement:
card IDs are whatever swudb lists, so a reprint stays the printing the deck was built
with rather than the print SWUSim implements. Run the deck through SWUSim's own importer
(SWUResolveDeckInput) if you need aliasing or legality -- that is a separate concern.

Why decode the QR instead of reading the printed link under it: deck IDs are
case-sensitive base62, so `I` vs `l` and `O` vs `0` are a coin flip in most fonts, and a
misread lands on a different deck (or a 404) with nothing to flag it. The QR payload is
exact. DevTools/extract-deck-link.py does the decode, and falls back to macOS Vision OCR
of the footer text when the QR itself won't read.

Exit code is non-zero on any failure (no QR, non-swudb link, private deck, bad JSON).

Prereq: none. OpenCV is bootstrapped into a cached venv on first use, the same way
extract-deck-link.py compiles its OCR helper on first use.
"""
import argparse
import importlib.util
import json
import os
import re
import subprocess
import sys
import tempfile
import urllib.error
import urllib.parse
import urllib.request

HERE = os.path.dirname(os.path.abspath(__file__))
REPO = os.path.dirname(HERE)  # this script lives in <repo>/DevTools/
API = "https://swudb.com/api/getDeckJson/"
DECK_ID_RE = re.compile(r"/deck/([A-Za-z0-9_-]+)", re.I)


def log(verbose, msg):
    if verbose:
        print(msg, file=sys.stderr)


# --------------------------------------------------------------------------------------
# OpenCV bootstrap
# --------------------------------------------------------------------------------------

def ensure_opencv(verbose=False):
    """Re-exec inside a cached venv when cv2 is missing.

    macOS system python3 is PEP-668 "externally managed", so a plain
    `pip install opencv-python` is refused outright -- which is exactly the wall you hit
    the first time you try this by hand. A throwaway venv sidesteps it without touching
    the system python.
    """
    try:
        import cv2  # noqa: F401
        return
    except ImportError:
        pass

    if os.environ.get("_SWU_QR_BOOTSTRAPPED"):
        sys.exit("OpenCV still missing after venv bootstrap -- install it manually and retry.")

    venv = os.path.join(tempfile.gettempdir(), "otmtcge-deckqr-venv")
    py = os.path.join(venv, "bin", "python")
    if not os.path.exists(py):
        print("  bootstrapping OpenCV into a cached venv (first run only, ~20s)...",
              file=sys.stderr)
        subprocess.run([sys.executable, "-m", "venv", venv], check=True)
        subprocess.run([os.path.join(venv, "bin", "pip"), "install", "--quiet",
                        "opencv-python-headless", "pillow", "numpy"], check=True)
    log(verbose, f"  re-exec under {py}")
    os.environ["_SWU_QR_BOOTSTRAPPED"] = "1"
    os.execv(py, [py, os.path.abspath(__file__)] + sys.argv[1:])


# --------------------------------------------------------------------------------------
# Step 1: image -> deck URL
# --------------------------------------------------------------------------------------

def load_extractor():
    """Import DevTools/extract-deck-link.py as a module (its file name has hyphens)."""
    path = os.path.join(HERE, "extract-deck-link.py")
    if not os.path.isfile(path):
        sys.exit(f"Missing {path} -- this tool reuses its QR decoder.")
    spec = importlib.util.spec_from_file_location("extract_deck_link", path)
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    return mod


def deck_url_from_image(image, verbose=False):
    result = load_extractor().extract(image, use_ocr=True, verbose=verbose)
    if not result["url"]:
        sys.exit(f"No deck link found in {image}. Pass --link <url> to supply it by hand.")
    if result["method"] != "qr":
        # OCR read the printed footer text -- the exact place I/l and O/0 get confused.
        print(f"  WARNING: QR decode failed; fell back to {result['method']}. "
              f"Check the deck id by eye before trusting this.", file=sys.stderr)
    print(f"  link: {result['url']}  ({result['method']}, {result['region']})", file=sys.stderr)
    return result["url"]


# --------------------------------------------------------------------------------------
# Step 2: deck URL -> swudb JSON
# --------------------------------------------------------------------------------------

def fetch_deck_json(url):
    """GET swudb's getDeckJson for this deck link. Returns the raw response text."""
    if "swudb.com" not in url.lower():
        sys.exit(f"Not a swudb link: {url}\n"
                 f"This tool only speaks swudb's getDeckJson API. For melee.gg and the "
                 f"other builders, import the link through SWUSim instead.")
    m = DECK_ID_RE.search(url)
    if not m:
        sys.exit(f"Could not pull a deck id out of: {url}")
    deck_id = m.group(1)

    api = API + urllib.parse.quote(deck_id, safe="")
    req = urllib.request.Request(api, headers={"Accept": "application/json",
                                               "User-Agent": "OTMTCGE-deck-qr/1.0"})
    try:
        with urllib.request.urlopen(req, timeout=15) as resp:
            body = resp.read().decode("utf-8")
    except urllib.error.HTTPError as e:
        sys.exit(f"swudb returned HTTP {e.code} for deck '{deck_id}'. "
                 f"The deck may be private, deleted, or the id misread.")
    except urllib.error.URLError as e:
        sys.exit(f"Could not reach swudb: {e.reason}")

    if not body.strip():
        sys.exit(f"swudb returned an empty body for deck '{deck_id}'.")
    return deck_id, body


def parse_and_check(body, deck_id):
    """Parse for sanity checks only -- the PARSED object is never what gets written."""
    try:
        deck = json.loads(body)
    except json.JSONDecodeError:
        sys.exit(f"swudb did not return JSON for deck '{deck_id}':\n{body[:500]}")
    if not isinstance(deck, dict):
        sys.exit(f"swudb returned a {type(deck).__name__}, not a deck object.")
    missing = [k for k in ("leader", "base", "deck") if k not in deck]
    if missing:
        sys.exit(f"swudb response is missing {missing} -- not a deck payload:\n{body[:500]}")
    return deck


# --------------------------------------------------------------------------------------
# Driver
# --------------------------------------------------------------------------------------

def summarize(deck, path, url):
    def total(entries):
        return sum(e.get("count", 0) for e in entries or [])

    main, side = deck.get("deck") or [], deck.get("sideboard") or []
    meta = deck.get("metadata") or {}
    print(f"  wrote {path}")
    print(f"    name      : {meta.get('name') or '(untitled)'}"
          + (f"   by {meta['author']}" if meta.get("author") else ""))
    print(f"    source    : {url}")
    print(f"    leader    : {(deck.get('leader') or {}).get('id')}"
          f"   base: {(deck.get('base') or {}).get('id')}")
    if deck.get("secondleader"):
        print(f"    leader 2  : {deck['secondleader'].get('id')}   (Twin Suns)")
    print(f"    deck      : {total(main)} cards ({len(main)} unique)")
    print(f"    sideboard : {total(side)} cards ({len(side)} unique)")
    # Premier is 50+ main / 10 max side. Worth a nudge, never an edit -- an intentionally
    # partial or non-Premier list is a legitimate thing to capture.
    if total(main) < 50:
        print(f"    NOTE: {total(main)} main-deck cards (Premier minimum is 50).")
    if total(side) > 10:
        print(f"    NOTE: {total(side)} sideboard cards (Premier maximum is 10).")


def main(argv=None):
    ap = argparse.ArgumentParser(
        description="QR capture of a swudb deck link -> that deck's swudb JSON file.")
    ap.add_argument("image", nargs="?", help="image holding the QR (omit only with --link)")
    ap.add_argument("--output", required=True, metavar="DIR", help="directory to write into")
    ap.add_argument("--filename", required=True, metavar="NAME", help="file name to write")
    ap.add_argument("--link", help="skip the QR decode; use this deck URL")
    ap.add_argument("--force", action="store_true", help="overwrite an existing output file")
    ap.add_argument("--print", dest="echo", action="store_true", help="echo the JSON to stdout")
    ap.add_argument("--verbose", action="store_true")
    args = ap.parse_args(argv)

    if not args.image and not args.link:
        ap.error("give an image, or --link <url>")

    name = args.filename if args.filename.endswith(".json") else args.filename + ".json"
    # --output is REPO-ROOT relative, so the same command works from any working directory
    # (an absolute path, or a ~ one, is still honoured as given).
    out_dir = os.path.expanduser(args.output)
    out_dir = os.path.abspath(out_dir if os.path.isabs(out_dir) else os.path.join(REPO, out_dir))
    path = os.path.join(out_dir, name)
    # Refuse a silent clobber: these land somewhere a human curates them afterwards.
    if os.path.exists(path) and not args.force:
        sys.exit(f"{path} already exists. Pass --force to overwrite.")

    if args.link:
        url = args.link
        print(f"  link: {url}  (supplied)", file=sys.stderr)
    else:
        if not os.path.isfile(args.image):
            sys.exit(f"No such image: {args.image}")
        ensure_opencv(args.verbose)
        url = deck_url_from_image(args.image, args.verbose)

    deck_id, body = fetch_deck_json(url)
    deck = parse_and_check(body, deck_id)

    os.makedirs(out_dir, exist_ok=True)
    if not body.endswith("\n"):
        body += "\n"
    with open(path, "w") as fh:
        fh.write(body)  # verbatim: swudb already emits 2-space pretty JSON

    summarize(deck, path, url)
    if args.echo:
        print(body, end="")
    return 0


if __name__ == "__main__":
    sys.exit(main())
