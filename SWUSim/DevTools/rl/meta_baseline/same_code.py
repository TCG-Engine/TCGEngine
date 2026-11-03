#!/usr/bin/env python3
"""Is today's working tree the code a frozen baseline was played on? (skill `swusim-meta-bot-run`, speed runs). Bots and engine are
deterministic, so a control arm on unchanged code replays the baseline game for game. When it is unchanged a speed run skips its control
and pairs the variant with the baseline (paired.py <arm> --baseline <meta>).

usage: same_code.py <meta dir>   — exit 0 = same code as baseline/meta.json 'codeRef'; 1 = it differs (the files are listed); 2 = no codeRef
Compared: every tracked or untracked (not ignored) file a self-play game can read — SWUSim/ minus docs, tests, fixtures, tools and game
folders, plus Core/, Schemas/SWUSim/, the lobby Player class and the self-play harness — by content, so commits elsewhere in the repo
don't count. Gitignored GENERATED files are not seen: they follow the schema, which is."""
import json, os, subprocess, sys

INCLUDE = ('SWUSim/', 'Core/', 'Schemas/SWUSim/', 'APIs/Lobbies/Classes/', 'DevTools/SWUSimBotSelfPlayTest.php')
EXCLUDE = ('SWUSim/docs/', 'SWUSim/Tests/', 'SWUSim/BotFixtures/', 'SWUSim/DevTools/', 'SWUSim/Games/')
SKIP_EXT = ('.md', '.js', '.css', '.html', '.webp', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.mp3', '.mp4', '.mov', '.ttf', '.woff2')
ROOT = subprocess.run(['git', 'rev-parse', '--show-toplevel'], capture_output=True, text=True, check=True).stdout.strip()


def git(*a, stdin=None):
    return subprocess.run(['git', *a], cwd=ROOT, input=stdin, capture_output=True, text=True, check=True).stdout


def engine_file(p):
    return p.startswith(INCLUDE) and not p.startswith(EXCLUDE) and not p.lower().endswith(SKIP_EXT)


def at_ref(ref):
    """path -> blob id of every engine file in a commit."""
    out = {}
    for l in git('ls-tree', '-r', '--full-tree', ref).splitlines():
        meta, path = l.split('\t', 1)
        if engine_file(path): out[path] = meta.split()[2]
    return out


def in_tree():
    """path -> blob id of every engine file as it is on disk now (tracked + untracked, not ignored)."""
    paths = [p for p in git('ls-files', '-co', '--exclude-standard').splitlines() if engine_file(p) and os.path.isfile(os.path.join(ROOT, p))]
    blobs = git('hash-object', '--stdin-paths', stdin='\n'.join(paths) + '\n').split()
    return dict(zip(paths, blobs))


def changed(ref):
    a, b = at_ref(ref), in_tree()
    return sorted(p for p in set(a) | set(b) if a.get(p) != b.get(p))


if __name__ == '__main__':
    if len(sys.argv) < 2: sys.exit(__doc__)
    ref = json.load(open(os.path.join(sys.argv[1], 'baseline', 'meta.json'))).get('codeRef')
    if not ref:
        print('the baseline has no codeRef (its tree was dirty at freeze) — run a speed control arm'); sys.exit(2)
    diff = changed(ref)
    if not diff:
        print(f'same code as the baseline ({ref}) — the baseline is the control'); sys.exit(0)
    print(f'{len(diff)} engine file(s) differ from the baseline ({ref}) — run a speed control arm:')
    for p in diff[:40]: print('   ' + p)
    if len(diff) > 40: print(f'   … and {len(diff) - 40} more')
    sys.exit(1)
