#!/usr/bin/env python3
"""Freeze a finished bot run as the meta's BASELINE (skill `swusim-meta-bot-run`). Every later run is compared with it, game for game,
until the owner asks for a new baseline — never re-freeze unasked.

usage: freeze.py <meta dir> <run dir> [--note "…"]
Writes <meta>/baseline/: bot_games.tsv.gz (deck1 deck2 winner rounds), analysis.txt (analyze.py --no-baseline), matrix.json, meta.json
(when, git HEAD + whether the tree was dirty, the sweep arm, games). Refuses to overwrite an existing baseline unless --force."""
import sys, os, json, gzip, subprocess, datetime
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from common import load_run
import matrix

meta, run = sys.argv[1], sys.argv[2]
B = os.path.join(meta, 'baseline')
if os.path.exists(os.path.join(B, 'bot_games.tsv.gz')) and '--force' not in sys.argv:
    sys.exit(f'{B} already holds a baseline — pass --force only when the owner asked for a NEW baseline')
os.makedirs(B, exist_ok=True)
bot, fails = load_run(run)
with gzip.open(os.path.join(B, 'bot_games.tsv.gz'), 'wt') as f:
    for (d1, d2), (w, r) in sorted(bot.items()): f.write(f'{d1}\t{d2}\t{w}\t{r}\n')
here = os.path.dirname(os.path.abspath(__file__))
an = subprocess.run([sys.executable, os.path.join(here, 'analyze.py'), meta, run, '--no-baseline'], capture_output=True, text=True).stdout
open(os.path.join(B, 'analysis.txt'), 'w').write(an)
rows, nm, ng = matrix.build(meta, {k: v for k, v in bot.items()}, None)
json.dump({'rows': rows}, open(os.path.join(B, 'matrix.json'), 'w'))
git = lambda *a: subprocess.run(['git', *a], capture_output=True, text=True).stdout.strip()
armf = next((p for p in (os.path.join(run, 'arm'), os.path.join(run, 'sweep', 'arm')) if os.path.exists(p)), None)
arm = open(armf).read().strip() if armf else ''
note = sys.argv[sys.argv.index('--note') + 1] if '--note' in sys.argv else ''
json.dump({'frozen': datetime.datetime.now(datetime.timezone.utc).strftime('%Y-%m-%dT%H:%M:%SZ'), 'gitHead': git('rev-parse', '--short', 'HEAD'),
           'treeDirty': bool(git('status', '--porcelain', '--', 'SWUSim', 'Core')), 'arm': arm, 'games': len(bot), 'failed': fails,
           'matches': nm, 'run': run, 'note': note}, open(os.path.join(B, 'meta.json'), 'w'), indent=1)
print(f'froze {len(bot)} games ({fails} failed) from {run} into {B}')
