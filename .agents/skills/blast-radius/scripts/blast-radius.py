#!/usr/bin/env python3
"""Classify a git diff by which sims/products it can affect in this monolith.

Usage:
  python3 .claude/skills/blast-radius/scripts/blast-radius.py [--base origin/main] [--head HEAD]

Run from the repo root.
"""
import argparse
import re
import subprocess
import sys
from datetime import datetime
from pathlib import Path

REPO_ROOT = Path(subprocess.run(
    ["git", "rev-parse", "--show-toplevel"], capture_output=True, text=True, check=True
).stdout.strip())

OUTPUT_DIR = Path(__file__).resolve().parent.parent / "output"

# Top-level dirs that are a single product's own code. Changes here only
# affect that product unless the file is also reached by a shared/grep step.
PRODUCT_DIRS = {
    "SWUSim": "SWUSim",
    "SWUDeck": "SWUDeck",
    "AzukiSim": "AzukiSim",
    "AzukiDeck": "AzukiDeck",
    "GrandArchiveSim": "GrandArchiveSim",
    "GudnakSim": "GudnakSim",
    "SoulMastersDB": "SoulMastersDB",
    "CardEditor": "CardEditor",
}

# AppCore/<Game> is shared between that game's Sim and Deck builder.
APPCORE_GAME_CONSUMERS = {
    "SWU": ["SWUSim", "SWUDeck"],
}

# Dirs whose immediate subdirectory names a single owning product — the
# subdir IS the product, not a generic filename to grep for. (Schemas/<X>/
# holds per-product test-schema fixtures; a filename like GameSchema.txt
# repeats across products, so a basename grep would false-positive.)
PRODUCT_SCOPED_SUBDIR = {
    "Schemas": PRODUCT_DIRS,
}

# Top-level dirs whose code is shared infrastructure, reachable from any
# product. A change here needs a repo-wide consumer search, not an assumption.
SHARED_DIRS = [
    "Core", "AppCore", "SharedUI", "Utils", "Database", "Data",
    "APIs", "Stats", "AIEndpoints", "AccountFiles", "McpServer",
]

# Loose root-level PHP files that are shared engine/transport entry points.
ROOT_SHARED_FILES = {
    "NextTurn.php", "ProcessInput.php", "SubmitChat.php", "GetChat.php",
    "GetPopupContent.php",
}

# Root-level generator/tooling scripts — dev-only blast radius, not runtime.
ROOT_TOOLING_PREFIXES = ("zz",)

ALL_PRODUCT_NAMES = sorted(set(PRODUCT_DIRS.values()))

PUBLIC_API_HINT = (
    "Public API surface (Stats/APIs.php-documented or APIs//Stats/*API.php). "
    "Per CLAUDE.md: verify this change is additive/backward-compatible before "
    "shipping — do not alter response shape, defaults, or required params for "
    "existing consumers."
)

REGRESSION_HINTS = {
    "SWUSim": "SWUSim has an automated regression runner: curl "
              "'zzRegressionSWUSim.php' while logged in as a mod (see "
              "zzRunSWUSimTests.php for the harness). Run it before pushing.",
}

MANUAL_PLAYTEST_HINT = (
    "No automated regression runner found for this product — playtest a real "
    "game manually before pushing (see CLAUDE.md Creds section for test logins)."
)

# Matches a PHP/JS function/method definition line, to resolve which named
# symbol a changed diff hunk falls inside of.
FUNC_DEF_RE = re.compile(r"function\s+&?(\w+)\s*\(")
CLASS_DEF_RE = re.compile(r"^\s*(?:abstract\s+|final\s+)?class\s+(\w+)")
HUNK_HEADER_RE = re.compile(r"^@@ -\d+(?:,\d+)? \+(\d+)(?:,(\d+))? @@")

# How this codebase identifies which sim is running: shared code reads a
# $folderPath (request-level) or $rootName (passed-through) variable and
# compares it to a sim's literal directory name, e.g.
# `if ($folderPath === 'SWUSim') {...}`, `in_array($rootName, [...])`.
# See Core/EngineActionRunner.php, Core/GameAuth.php, Core/ViewerIdentity.php,
# NextTurn.php/ProcessInput.php/SubmitChat.php for the real pattern.
SIM_VAR_RE = re.compile(r"\$(?:folderPath|rootName)\b")
SIM_NAME_RE = re.compile(
    r"['\"](" + "|".join(re.escape(p) for p in ALL_PRODUCT_NAMES) + r")['\"]"
)

MAX_SCAN_BACK = 300  # lines to search upward for the enclosing function/class
FILE_LIST_CAP = 25   # cap on files itemized per product in the report


def run(cmd):
    result = subprocess.run(cmd, cwd=REPO_ROOT, capture_output=True, text=True)
    if result.returncode != 0:
        sys.stderr.write(result.stderr)
        sys.exit(result.returncode)
    return result.stdout


def changed_files(base, head):
    merge_base = run(["git", "merge-base", base, head]).strip()
    out = run(["git", "diff", "--name-only", merge_base, head])
    return [line.strip() for line in out.splitlines() if line.strip()]


def top_dir(path):
    parts = Path(path).parts
    return parts[0] if parts else path


def find_consumers(basename, exclude_top):
    """git grep (tracked files only) for a reference to basename in each
    product dir; return the product names that hit. Using git grep keeps
    this fast and correct — it skips gitignored save-data dirs (Games/,
    Matches/, GeneratedCode/) that a filesystem grep would otherwise crawl.
    """
    consumers = []
    for dirname, product in PRODUCT_DIRS.items():
        if dirname == exclude_top:
            continue
        result = subprocess.run(
            ["git", "grep", "-l", "-e", basename, "--", dirname],
            cwd=REPO_ROOT, capture_output=True, text=True,
        )
        if result.stdout.strip():
            consumers.append(product)
    return consumers


def _indent(line):
    return len(line) - len(line.lstrip(" \t"))


def _block_already_closed(file_lines, after_idx, before_idx, at_or_below_indent):
    """True if a standalone `}` at indent <= at_or_below_indent appears
    between after_idx (exclusive) and before_idx (exclusive) — i.e. the
    block opened at after_idx closed before reaching before_idx.
    """
    for j in range(after_idx + 1, before_idx):
        lj = file_lines[j]
        if lj.strip() == "}" and _indent(lj) <= at_or_below_indent:
            return True
    return False


def _enclosing_sim_guard(file_lines, hunk_idx):
    """Heuristic: is this line inside an `if ($folderPath === 'X')` (or
    in_array/$rootName equivalent) block? Scans upward for the nearest
    shallower-indented line that both mentions the sim-identity variable and
    a known sim name; then checks no same-or-shallower-indent `}` closed
    that block before reaching hunk_idx. Indentation-based, not a real
    parser — reliable for this codebase's formatting style, not bulletproof
    (see SKILL.md Known limitations).
    """
    if hunk_idx >= len(file_lines) or not file_lines[hunk_idx].strip():
        return None
    hunk_indent = _indent(file_lines[hunk_idx])
    for back in range(1, MAX_SCAN_BACK):
        i = hunk_idx - back
        if i < 0:
            return None
        line = file_lines[i]
        if not line.strip():
            continue
        li = _indent(line)
        if li >= hunk_indent:
            continue
        if SIM_VAR_RE.search(line) and SIM_NAME_RE.search(line):
            if _block_already_closed(file_lines, i, hunk_idx, li):
                return None
            return {"line": line.strip(), "sims": SIM_NAME_RE.findall(line)}
        return None  # shallower non-guard line bounds the search


def changed_symbols(file, merge_base, head):
    """Resolve which function/class names were actually touched by this
    file's diff, by walking each changed hunk's new-side line range upward
    to the nearest enclosing `function foo(` / `class Foo`. Filename-level
    consumer search only tells you the file is included somewhere; this
    tells you which specific symbol changed, so a caller of a different,
    untouched function in the same file isn't falsely flagged.

    Also resolves, per hunk, whether the changed line sits inside a
    sim-identity conditional (`$folderPath`/`$rootName` compared to a sim
    name) — a change guarded that way only affects the named sim(s); one
    with no such guard runs unconditionally for every sim that includes
    this file.

    Returns a list of {"symbol": str, "guard": dict|None} per hunk.
    """
    diff_out = subprocess.run(
        ["git", "diff", "--unified=0", merge_base, head, "--", file],
        cwd=REPO_ROOT, capture_output=True, text=True,
    ).stdout
    hunk_starts = []
    for line in diff_out.splitlines():
        m = HUNK_HEADER_RE.match(line)
        if m:
            new_start = int(m.group(1))
            new_count = int(m.group(2)) if m.group(2) is not None else 1
            if new_count > 0:
                hunk_starts.append(new_start)
    if not hunk_starts:
        return []

    content = subprocess.run(
        ["git", "show", f"{head}:{file}"],
        cwd=REPO_ROOT, capture_output=True, text=True,
    )
    if content.returncode != 0:
        return []
    file_lines = content.stdout.splitlines()

    results = []
    for start in hunk_starts:
        idx = min(start - 1, len(file_lines) - 1)
        symbol = "(top-level/no enclosing function)"
        for back in range(0, MAX_SCAN_BACK):
            i = idx - back
            if i < 0:
                break
            m = FUNC_DEF_RE.search(file_lines[i]) or CLASS_DEF_RE.search(file_lines[i])
            if m:
                # A def line only encloses the hunk if its block hasn't
                # already closed before the hunk (e.g. an unrelated earlier
                # function in the same file, textually above but out of
                # scope by the time the hunk's line is reached).
                if _block_already_closed(file_lines, i, idx, _indent(file_lines[i])):
                    continue
                symbol = m.group(1)
                break
        guard = _enclosing_sim_guard(file_lines, idx)
        results.append({"symbol": symbol, "guard": guard})
    return results


def find_symbol_consumers(symbol, exclude_top):
    """git grep for actual call sites of a specific symbol (word-boundary,
    followed by an opening paren) in each product dir. Far more precise than
    filename matching: tells you who calls the exact function that changed.
    """
    if symbol == "(top-level/no enclosing function)":
        return {}
    consumers = {}
    pattern = rf"\b{re.escape(symbol)}\s*\("
    for dirname, product in PRODUCT_DIRS.items():
        if dirname == exclude_top:
            continue
        result = subprocess.run(
            ["git", "grep", "-l", "-E", "-e", pattern, "--", dirname],
            cwd=REPO_ROOT, capture_output=True, text=True,
        )
        hits = [l for l in result.stdout.splitlines() if l.strip()]
        if hits:
            consumers[product] = hits
    return consumers


def analyze_shared_file(f, exclude_top, merge_base, head):
    """Build the full picture for one shared/infra file: which products
    reference the filename at all, which specific functions changed and who
    actually calls each one, and whether each changed symbol is guarded by
    a sim-identity check or runs unconditionally for every consumer.
    """
    basename = Path(f).name
    file_consumers = find_consumers(basename, exclude_top=exclude_top)
    hunks = changed_symbols(f, merge_base, head)

    by_symbol = {}
    for h in hunks:
        by_symbol.setdefault(h["symbol"], []).append(h["guard"])

    symbol_consumers = {}
    symbol_guard = {}
    for sym, guards in by_symbol.items():
        symbol_consumers[sym] = find_symbol_consumers(sym, exclude_top=exclude_top)
        if all(g is not None for g in guards):
            sims = sorted({s for g in guards for s in g["sims"]})
            symbol_guard[sym] = {"status": "guarded", "sims": sims}
        elif any(g is not None for g in guards):
            sims = sorted({s for g in guards if g for s in g["sims"]})
            symbol_guard[sym] = {"status": "mixed", "sims": sims}
        else:
            symbol_guard[sym] = {"status": "unconditional", "sims": []}

    return {
        "file": f,
        "file_consumers": file_consumers,
        "symbol_consumers": symbol_consumers,  # symbol -> {product: [files]}
        "symbol_guard": symbol_guard,           # symbol -> {status, sims}
    }


def classify(files, merge_base, head):
    direct = {}       # product -> [files]
    shared = []        # list of analyze_shared_file() dicts, or static-consumer dicts
    public_api = []     # files
    tooling = []        # files
    other = []          # files

    for f in files:
        td = top_dir(f)

        if td in PRODUCT_DIRS:
            direct.setdefault(PRODUCT_DIRS[td], []).append(f)
            continue

        if td == "AppCore":
            parts = Path(f).parts
            game = parts[1] if len(parts) > 1 else None
            consumers = APPCORE_GAME_CONSUMERS.get(game, [])
            shared.append({
                "file": f, "file_consumers": consumers,
                "symbol_consumers": {}, "symbol_guard": {},
            })
            continue

        if td in PRODUCT_SCOPED_SUBDIR:
            parts = Path(f).parts
            subdir = parts[1] if len(parts) > 1 else None
            owner = PRODUCT_SCOPED_SUBDIR[td].get(subdir)
            if owner:
                direct.setdefault(owner, []).append(f)
            else:
                other.append(f)
            continue

        if td in SHARED_DIRS:
            basename = Path(f).name
            shared.append(analyze_shared_file(f, exclude_top=td, merge_base=merge_base, head=head))
            if td == "APIs" or (td == "Stats" and (
                basename == "APIs.php" or re.search(r"API\.php$", basename)
            )):
                public_api.append(f)
            continue

        if td == f and f in ROOT_SHARED_FILES:
            shared.append(analyze_shared_file(f, exclude_top="", merge_base=merge_base, head=head))
            continue

        if td == f and f.startswith(ROOT_TOOLING_PREFIXES):
            tooling.append(f)
            continue

        other.append(f)

    return direct, shared, public_api, tooling, other


def render_file_list(flist, root=None):
    lines = []
    shown = flist if len(flist) <= FILE_LIST_CAP else flist[:FILE_LIST_CAP]
    for f in shown:
        lines.append(f"  - {f}")
    if len(flist) > FILE_LIST_CAP:
        lines.append(f"  - ... and {len(flist) - FILE_LIST_CAP} more (truncated; not dropped from counts)")
    return lines


def _guard_tag(guard):
    if not guard:
        return ""
    status, sims = guard["status"], guard["sims"]
    if status == "guarded":
        return f"  [guarded: {', '.join(sims)}-only]"
    if status == "mixed":
        return f"  [**partially unconditional** — guarded for {', '.join(sims)} in some hunks, unconditional in others]"
    return "  [**unconditional** — runs for every sim that includes this file]"


def render_shared_entry(entry):
    lines = [f"- `{entry['file']}`"]
    consumers = entry["file_consumers"]
    tag = "ALL PRODUCTS" if not consumers else ", ".join(sorted(set(consumers)))
    risk = " **[HIGH: multi-product]**" if len(set(consumers)) > 1 or not consumers else ""
    lines.append(f"  - file referenced by -> {tag}{risk}")

    for sym, sym_consumers in sorted(entry["symbol_consumers"].items()):
        guard_tag = _guard_tag(entry.get("symbol_guard", {}).get(sym))
        if sym == "(top-level/no enclosing function)":
            lines.append(f"  - top-level/no enclosing function changed — can't narrow past file-level{guard_tag}")
            continue
        if not sym_consumers:
            lines.append(f"  - `{sym}()` changed — no call sites found elsewhere (likely internal/dead, or dynamically invoked){guard_tag}")
        else:
            detail = ", ".join(
                f"{p} ({len(fs)} file(s))" for p, fs in sorted(sym_consumers.items())
            )
            lines.append(f"  - `{sym}()` changed — called from: {detail}{guard_tag}")
    return lines


def build_report(base, head, files, direct, shared, public_api, tooling, other, root=None):
    title = f"# Blast radius: `{base}...{head}`"
    if root:
        title += f"  (focused on {root})"
    lines = [title, "", f"{len(files)} file(s) changed.", ""]

    if not files:
        lines.append("No changes — nothing to analyze.")
        return "\n".join(lines) + "\n"

    if direct:
        lines.append("## Directly changed products")
        for product, flist in sorted(direct.items()):
            if root and product == root:
                lines.append(f"- **{product}** ({len(flist)} file(s)) — own product, not itemized (this is the --root)")
                continue
            lines.append(f"- **{product}** ({len(flist)} file(s))")
            lines.extend(render_file_list(flist))
        lines.append("")

    if shared:
        heading = "## Shared/infra files changed"
        if root:
            heading += f" (what {root}'s edits leak to everyone else)"
        else:
            heading += " (repo-wide consumer search)"
        lines.append(heading)
        for entry in shared:
            lines.extend(render_shared_entry(entry))
        lines.append("")

    if public_api:
        lines.append("## Public API surface touched")
        for f in public_api:
            lines.append(f"- `{f}`")
        lines.append("")
        lines.append(PUBLIC_API_HINT)
        lines.append("")

    if tooling:
        lines.append("## Dev tooling / generators (root zz*.php)")
        for f in tooling:
            lines.append(f"- `{f}`")
        lines.append("")

    if other:
        lines.append("## Unclassified (review manually)")
        for f in other:
            lines.append(f"- `{f}`")
        lines.append("")

    affected_products = set(direct.keys())
    for entry in shared:
        affected_products.update(entry["file_consumers"])
        for sym_consumers in entry["symbol_consumers"].values():
            affected_products.update(sym_consumers.keys())
    if any(not entry["file_consumers"] for entry in shared):
        affected_products.update(ALL_PRODUCT_NAMES)
    if root:
        affected_products.discard(root)

    if affected_products:
        heading = "## Suggested checks before pushing to main"
        if root:
            heading = f"## Suggested checks on products other than {root}"
        lines.append(heading)
        for product in sorted(affected_products):
            hint = REGRESSION_HINTS.get(product, MANUAL_PLAYTEST_HINT)
            lines.append(f"- **{product}**: {hint}")
        lines.append("")

    return "\n".join(lines) + "\n"


def resolve_root(root_arg):
    if root_arg is None:
        return None
    for product in ALL_PRODUCT_NAMES:
        if product.lower() == root_arg.lower():
            return product
    sys.exit(
        f"Unknown --root '{root_arg}'. Valid products: {', '.join(ALL_PRODUCT_NAMES)}"
    )


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--base", default="origin/main")
    parser.add_argument("--head", default="HEAD")
    parser.add_argument(
        "root", nargs="?", default=None,
        help="Focus the report on one product (e.g. swusim) — shows what its "
             "changes leak into other products instead of itemizing its own diff.",
    )
    args = parser.parse_args()
    root = resolve_root(args.root)

    merge_base = run(["git", "merge-base", args.base, args.head]).strip()
    files = changed_files(args.base, args.head)
    direct, shared, public_api, tooling, other = classify(files, merge_base, args.head)
    md = build_report(args.base, args.head, files, direct, shared, public_api, tooling, other, root=root)

    print(md)

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    stamp = datetime.now().strftime("%Y%m%d-%H%M%S")
    suffix = f"-{root.lower()}" if root else ""
    out_path = OUTPUT_DIR / f"blast-radius{suffix}-{stamp}.md"
    out_path.write_text(md)
    print(f"Report written to {out_path.relative_to(REPO_ROOT)}")


if __name__ == "__main__":
    main()
