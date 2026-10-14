# SWUSim/DevTools/rl/meta_baseline_ibh_test.py — the meta-baseline analyses ignore every real match where either deck is on an
# Intro Battle: Hoth base (owner 2026-10-12: those bases are double-sided, the real lists played the 30 HP side, the sims play 20).
#   python3 SWUSim/DevTools/rl/meta_baseline_ibh_test.py
import gzip, os, subprocess, sys, tempfile
HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.join(HERE, 'meta_baseline'))
from common import load_matches

rows = [
    ('a', 'b', 'LAW_008|grp:Standard:Blue', 'JTL_006|grp:Standard:Yellow'),   # kept
    ('c', 'd', 'LAW_008|IBH_054', 'JTL_006|grp:Standard:Yellow'),             # IBH base on side A: ignored
    ('e', 'f', 'JTL_006|grp:Standard:Yellow', 'IBH_053|IBH_002'),             # IBH base on side B: ignored
    ('g', 'h', 'IBH_053|ASH_019', 'LAW_008|JTL_024'),                         # IBH LEADER on a normal base: kept (only bases)
]
meta = tempfile.mkdtemp()
with gzip.open(os.path.join(meta, 'matches.tsv.gz'), 'wt') as f:
    for a, b, ka, kb in rows: f.write(f'{a}\t{b}\t2\t1\t0\t{ka}\t{kb}\tmidrange\tsoftcontrol\n')

kept = [(m['a'], m['b']) for m in load_matches(meta)]
assert kept == [('a', 'b'), ('g', 'h')], kept
everything = load_matches(meta, include_ibh=True)
assert len(everything) == len(rows), len(everything)

# analyze.py — the entry point the skill runs — must read the filtered set and say how many matches it dropped.
with open(os.path.join(meta, 'labels.json'), 'w') as f: f.write('{}')
run = tempfile.mkdtemp()
with open(os.path.join(run, 'results.tsv'), 'w') as f:
    for a, b, _, _ in rows:
        for d1, d2 in ((a, b), (b, a)):
            f.write(f'{d1}\t{d2}\ttb001\tSWUBOT_METRICS {{"winner":1,"rounds":7}}\t{{"fail":0}}\n')
out = subprocess.run(['python3', os.path.join(HERE, 'meta_baseline', 'analyze.py'), meta, run, '--no-baseline', '--min', '1'],
                     capture_output=True, text=True)
assert '2/2 real matches replayed' in out.stdout, out.stdout + out.stderr
assert '2 matches on an Intro Battle: Hoth base ignored' in out.stdout, out.stdout + out.stderr

# The paired section counts only the kept matches' games: a baseline where seat 2 won every game changes all 4 kept games, not 8.
os.makedirs(os.path.join(meta, 'baseline'))
with gzip.open(os.path.join(meta, 'baseline', 'bot_games.tsv.gz'), 'wt') as f:
    for a, b, _, _ in rows:
        for d1, d2 in ((a, b), (b, a)): f.write(f'{d1}\t{d2}\t2\t7\n')
out = subprocess.run(['python3', os.path.join(HERE, 'meta_baseline', 'analyze.py'), meta, run, '--min', '1'], capture_output=True, text=True)
assert 'games compared 4   winner changed in 4 ' in out.stdout, out.stdout + out.stderr
print('ALL PASS')
