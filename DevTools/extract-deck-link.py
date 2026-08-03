#!/usr/bin/env python3
"""Extract the deck URL from a shared deck image.

Deck renders (swudb-style, ~2880x1620) carry their source link twice in the bottom-right
corner: as a QR code, and as printed text underneath it. This pulls it back out.

Usage:
    python3 DevTools/extract-deck-link.py <image...> [--json] [--verbose] [--no-ocr]

  <image...>   One or more image paths (png / jpg / webp -- anything Pillow reads).
  --json       Emit a JSON array of {file, url, method, raw, region} instead of bare URLs.
  --verbose    Log every attempted pass, and the raw OCR lines, to stderr.
  --no-ocr     QR code only. Keeps the run fully cross-platform.

Two paths are tried, stopping at the first hit:

  1. QR decode via OpenCV, sweeping region x upscale x threshold. The QR is small
     relative to the canvas (~130px on a 2880px-wide render), so a plain full-image
     decode often misses it -- the bottom-right crop upscaled 2-4x is what usually lands.
  2. OCR of the footer text via macOS Vision, reached through a small Swift helper
     compiled on first use into a temp cache. macOS only; skipped elsewhere.

stdout is just the URLs (one per line) so it pipes; everything else goes to stderr.
Exit code is non-zero if any input yields no URL.

Prereq: pip install pillow opencv-python
"""
import argparse
import hashlib
import json
import os
import re
import shutil
import subprocess
import sys
import tempfile

try:
    import numpy as np
    from PIL import Image
except ImportError:
    sys.exit("Pillow and numpy are required: python3 -m pip install --quiet pillow numpy")

try:
    import cv2
except ImportError:
    sys.exit("OpenCV is required: python3 -m pip install --quiet opencv-python")


# Hosts we know publish deck links. Only used to RANK candidates when a frame holds more
# than one URL-ish string -- an unknown host still wins if it's the only thing there.
DECK_HOST_HINTS = (
    "swudb.com",
    "swustats.net",
    "karabast.net",
    "sw-unlimited-db.com",
    "melee.gg",
    "sleeved.gg",
)

# Scheme optional: the QR carries a full https:// URL, but the printed footer text does not.
URL_RE = re.compile(
    r"""(?ix)
    (?: (?P<scheme> https? ) :// )?
    (?P<host> (?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+ [a-z]{2,} )
    (?P<path> / [^\s"'<>\\)\]]* )?
    """
)


def log(verbose, msg):
    if verbose:
        print(msg, file=sys.stderr)


# --------------------------------------------------------------------------------------
# URL normalization
# --------------------------------------------------------------------------------------

def _score(match):
    """Rank a URL candidate: known deck host and a /deck/ path both count for a lot."""
    host = match.group("host").lower()
    path = (match.group("path") or "").lower()
    score = 0
    if any(host == h or host.endswith("." + h) for h in DECK_HOST_HINTS):
        score += 10
    if "/deck" in path:
        score += 5
    if match.group("scheme"):
        score += 1
    score += min(len(path), 40) / 100.0  # tie-break toward the more specific path
    return score


def normalize_url(text):
    """Pull the most deck-link-looking URL out of `text` and give it a scheme.

    Returns None when nothing in the text looks like a URL at all.
    """
    if not text:
        return None
    best = None
    for m in URL_RE.finditer(text):
        # A bare "1.5" or a version string shouldn't qualify as a host.
        if not re.search(r"[a-z]", m.group("host"), re.I):
            continue
        if best is None or _score(m) > _score(best):
            best = m
    if best is None:
        return None
    host = best.group("host")
    path = (best.group("path") or "").rstrip(".,;")
    scheme = best.group("scheme") or "https"
    return f"{scheme}://{host}{path}"


# --------------------------------------------------------------------------------------
# Image loading
# --------------------------------------------------------------------------------------

def load_bgr(path):
    """Load any Pillow-readable image (incl. webp) as an OpenCV BGR array."""
    img = Image.open(path)
    if img.mode in ("P", "LA", "RGBA"):
        # Flatten transparency onto white so thresholds behave.
        img = img.convert("RGBA")
        flat = Image.new("RGB", img.size, (255, 255, 255))
        flat.paste(img, mask=img.split()[3])
        img = flat
    else:
        img = img.convert("RGB")
    return cv2.cvtColor(np.array(img), cv2.COLOR_RGB2BGR)


def crop(bgr, box):
    """box is (x0, y0, x1, y1) as fractions of width/height."""
    h, w = bgr.shape[:2]
    x0, y0, x1, y1 = box
    return bgr[int(y0 * h):int(y1 * h), int(x0 * w):int(x1 * w)]


def upscale(bgr, factor, max_edge=9000):
    """Upscale by an integer factor, refusing sizes where the decode stops being worth it.

    The cap has to stay generous: on a 2880px render the bottom-right crop at 4x is ~4400px
    and the full image at 2x is 5760px, and those are precisely the passes that rescue a
    small or soft QR. A tighter cap silently turns them into no-ops.
    """
    if factor == 1:
        return bgr
    h, w = bgr.shape[:2]
    if max(h, w) * factor > max_edge:
        return None
    return cv2.resize(bgr, (w * factor, h * factor), interpolation=cv2.INTER_CUBIC)


# --------------------------------------------------------------------------------------
# Pass 1: QR decode
# --------------------------------------------------------------------------------------

FULL = (0.0, 0.0, 1.0, 1.0)
BOTTOM_RIGHT = (0.62, 0.68, 1.0, 1.0)
BOTTOM_STRIP = (0.0, 0.80, 1.0, 1.0)

# (label, box, upscale) -- ordered cheapest / most-likely first.
QR_PASSES = (
    ("full", FULL, 1),
    ("bottom-right", BOTTOM_RIGHT, 2),
    ("bottom-right", BOTTOM_RIGHT, 3),
    ("bottom-right", BOTTOM_RIGHT, 4),
    ("bottom-strip", BOTTOM_STRIP, 2),
    ("full", FULL, 2),
)


# Above this many pixels, a region only gets the cheap binarizations. Adaptive threshold
# earns its keep on unevenly lit input (a photo of a screen) -- but that input arrives as a
# small crop, and paying for it on a 5760px upscale is what makes a no-QR sweep crawl.
ADAPTIVE_PIXEL_LIMIT = 8_000_000


def _variants(bgr):
    """Progressively more aggressive binarizations of one region."""
    gray = cv2.cvtColor(bgr, cv2.COLOR_BGR2GRAY)
    yield "gray", gray
    _, otsu = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
    yield "otsu", otsu
    yield "otsu-inv", cv2.bitwise_not(otsu)
    if gray.size <= ADAPTIVE_PIXEL_LIMIT:
        yield "adaptive", cv2.adaptiveThreshold(
            gray, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY, 31, 5
        )


def _decode_with(detector, img):
    """Run one detector over one image, returning the first non-empty payload."""
    try:
        ok, infos, _pts, _straight = detector.detectAndDecodeMulti(img)
        if ok:
            for info in infos:
                if info:
                    return info
    except cv2.error:
        pass
    try:
        data, _pts, _straight = detector.detectAndDecode(img)
        if data:
            return data
    except cv2.error:
        pass
    return None


def read_qr(bgr, verbose=False):
    """Sweep region x upscale x threshold until a QR decodes. Returns (payload, label)."""
    detectors = [cv2.QRCodeDetector()]
    if hasattr(cv2, "QRCodeDetectorAruco"):
        detectors.append(cv2.QRCodeDetectorAruco())

    for label, box, factor in QR_PASSES:
        region = upscale(crop(bgr, box), factor)
        if region is None:
            log(verbose, f"    qr {label}@{factor}x: skipped (too large)")
            continue
        for vname, img in _variants(region):
            for detector in detectors:
                payload = _decode_with(detector, img)
                if payload:
                    where = f"{label}@{factor}x/{vname}"
                    log(verbose, f"    qr {where}: HIT")
                    return payload, where
            log(verbose, f"    qr {label}@{factor}x/{vname}: miss")

    # Last resort: the curved decoder on the corner most likely to hold the code.
    region = upscale(crop(bgr, BOTTOM_RIGHT), 3)
    if region is not None:
        gray = cv2.cvtColor(region, cv2.COLOR_BGR2GRAY)
        try:
            data, _pts, _straight = cv2.QRCodeDetector().detectAndDecodeCurved(gray)
            if data:
                log(verbose, "    qr bottom-right@3x/curved: HIT")
                return data, "bottom-right@3x/curved"
        except cv2.error:
            pass
    return None, None


# --------------------------------------------------------------------------------------
# Pass 2: OCR the footer text (macOS Vision)
# --------------------------------------------------------------------------------------

VISION_SWIFT = r"""
import Foundation
import Vision
import AppKit

let args = CommandLine.arguments
guard args.count > 1 else {
    FileHandle.standardError.write("usage: vision-ocr <image>\n".data(using: .utf8)!)
    exit(2)
}
guard let image = NSImage(contentsOfFile: args[1]),
      let cgImage = image.cgImage(forProposedRect: nil, context: nil, hints: nil) else {
    FileHandle.standardError.write("vision-ocr: cannot load image\n".data(using: .utf8)!)
    exit(3)
}
let request = VNRecognizeTextRequest()
request.recognitionLevel = .accurate
request.usesLanguageCorrection = false   // URLs are not prose; correction mangles them
request.recognitionLanguages = ["en-US"]
let handler = VNImageRequestHandler(cgImage: cgImage, options: [:])
do {
    try handler.perform([request])
} catch {
    FileHandle.standardError.write("vision-ocr: \(error)\n".data(using: .utf8)!)
    exit(4)
}
var lines: [String] = []
for observation in (request.results ?? []) {
    for candidate in observation.topCandidates(3) {
        lines.append(candidate.string)
    }
}
print(lines.joined(separator: "\n"))
"""


def _vision_binary(verbose=False):
    """Compile (once) and return the path to the Swift Vision OCR helper, or None."""
    if sys.platform != "darwin":
        log(verbose, "    ocr: skipped (macOS Vision is not available on this platform)")
        return None
    swiftc = shutil.which("swiftc")
    if not swiftc:
        log(verbose, "    ocr: skipped (swiftc not found -- install Xcode command line tools)")
        return None

    digest = hashlib.sha256(VISION_SWIFT.encode()).hexdigest()[:16]
    cache = os.path.join(tempfile.gettempdir(), f"otmtcge-vision-ocr-{digest}")
    if os.path.exists(cache):
        return cache

    with tempfile.TemporaryDirectory() as tmp:
        src = os.path.join(tmp, "vision_ocr.swift")
        with open(src, "w") as fh:
            fh.write(VISION_SWIFT)
        out = os.path.join(tmp, "vision-ocr")
        log(verbose, "    ocr: compiling Vision helper (first run only)...")
        proc = subprocess.run(
            [swiftc, "-O", "-o", out, src], capture_output=True, text=True
        )
        if proc.returncode != 0:
            log(verbose, f"    ocr: swiftc failed:\n{proc.stderr.strip()}")
            return None
        shutil.move(out, cache)
    return cache


OCR_PASSES = (
    ("footer", (0.50, 0.88, 1.0, 1.0), 3),
    ("bottom-strip", (0.0, 0.85, 1.0, 1.0), 2),
)


def read_footer_text(bgr, verbose=False):
    """OCR the footer strip under the QR. Returns (raw_text, label)."""
    binary = _vision_binary(verbose)
    if not binary:
        return None, None

    for label, box, factor in OCR_PASSES:
        region = upscale(crop(bgr, box), factor, max_edge=6000)
        if region is None:
            continue
        with tempfile.NamedTemporaryFile(suffix=".png", delete=False) as tmp:
            tmp_path = tmp.name
        try:
            cv2.imwrite(tmp_path, region)
            proc = subprocess.run([binary, tmp_path], capture_output=True, text=True)
        finally:
            os.unlink(tmp_path)
        if proc.returncode != 0:
            log(verbose, f"    ocr {label}@{factor}x: helper exit {proc.returncode}")
            continue
        text = proc.stdout.strip()
        log(verbose, f"    ocr {label}@{factor}x lines:\n      " + "\n      ".join(text.splitlines()))
        if normalize_url(text):
            return text, f"{label}@{factor}x"
    return None, None


# --------------------------------------------------------------------------------------
# Driver
# --------------------------------------------------------------------------------------

def extract(path, use_ocr=True, verbose=False):
    """Returns a result dict; `url` is None when nothing was found."""
    result = {"file": path, "url": None, "method": None, "raw": None, "region": None}
    bgr = load_bgr(path)
    log(verbose, f"  {path} ({bgr.shape[1]}x{bgr.shape[0]})")

    payload, where = read_qr(bgr, verbose)
    if payload:
        url = normalize_url(payload)
        if url:
            result.update(url=url, method="qr", raw=payload, region=where)
            return result
        log(verbose, f"    qr decoded but held no URL: {payload!r}")

    if use_ocr:
        text, where = read_footer_text(bgr, verbose)
        if text:
            url = normalize_url(text)
            if url:
                result.update(url=url, method="ocr-vision", raw=text, region=where)
                return result
    return result


def main(argv=None):
    ap = argparse.ArgumentParser(
        description="Extract the deck URL from a deck image (QR code, or the printed link).",
    )
    ap.add_argument("images", nargs="+")
    ap.add_argument("--json", action="store_true", help="emit structured JSON")
    ap.add_argument("--verbose", action="store_true", help="log each pass to stderr")
    ap.add_argument("--no-ocr", action="store_true", help="QR code only")
    args = ap.parse_args(argv)

    results, failures = [], 0
    for path in args.images:
        if not os.path.isfile(path):
            print(f"  SKIP (not found): {path}", file=sys.stderr)
            results.append({"file": path, "url": None, "method": None,
                            "raw": None, "region": None, "error": "not found"})
            failures += 1
            continue
        try:
            result = extract(path, use_ocr=not args.no_ocr, verbose=args.verbose)
        except Exception as e:
            print(f"  FAIL {path}: {e}", file=sys.stderr)
            results.append({"file": path, "url": None, "method": None,
                            "raw": None, "region": None, "error": str(e)})
            failures += 1
            continue
        results.append(result)
        if result["url"]:
            if not args.json:  # under --json, stdout must stay parseable
                print(result["url"])
            if args.verbose:
                print(f"  -> {result['method']} ({result['region']})", file=sys.stderr)
        else:
            hint = "" if not args.no_ocr else " (try again without --no-ocr)"
            print(f"  no deck link found in {path}{hint}", file=sys.stderr)
            failures += 1

    if args.json:
        print(json.dumps(results, indent=2))
    return 1 if failures else 0


if __name__ == "__main__":
    sys.exit(main())
