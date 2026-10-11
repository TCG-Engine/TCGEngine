"""Paired comparison of two fixture sweeps (sweep_fixtures.sh results.tsv) over the SAME games: games are deterministic
per seed, so each (deck1, deck2, seed) in arm A has an exact twin in arm B and only the bot changes between them.

usage: paired_sweep_compare.py <control results.tsv> <treatment results.tsv> [label control] [label treatment]

Per deck (both seats pooled): win rate in each arm, games won only in B / only in A, and the exact two-sided McNemar p.
Then each deck style, overall game length, and failures. Decks are named by fixture; style comes from the fixture's
"# Style:" line (SWUSim/Tests/BotFixtures/ash-meta-2026-09)."""
import sys, os, re, json, glob, math, collections, statistics

A_PATH, B_PATH = sys.argv[1:3]
LA = sys.argv[3] if len(sys.argv) > 3 else 'A'
LB = sys.argv[4] if len(sys.argv) > 4 else 'B'
FX = 'SWUSim/Tests/BotFixtures/ash-meta-2026-09'
style = {os.path.basename(f)[:-4]: re.search(r'^# Style:\s*(\S+)', open(f).read(), re.M).group(1)
         for f in glob.glob(os.path.join(FX, '*.txt'))}

def load(p):
    out = {}; fails = 0
    for line in open(p):
        a, b, s, m, r, g = line.rstrip('\n').split('\t')
        if not m.startswith('SWUBOT_METRICS ') or '"fail":0' not in r: fails += 1; continue
        met = json.loads(m.split(' ', 1)[1]); out[(a, b, s)] = (met['winner'], met['rounds'])
    return out, fails

A, fa = load(A_PATH); B, fb = load(B_PATH)
keys = sorted(set(A) & set(B))
print(f'{len(keys)} paired games ({len(A)} in {LA}, {len(B)} in {LB}); failures {LA} {fa}, {LB} {fb}')
print(f'identical outcome {sum(A[k][0] == B[k][0] for k in keys) / len(keys):.1%}; '
      f'median rounds {LA} {statistics.median(A[k][1] for k in keys)}, {LB} {statistics.median(B[k][1] for k in keys)}; '
      f'mean rounds {LA} {statistics.mean(A[k][1] for k in keys):.2f}, {LB} {statistics.mean(B[k][1] for k in keys):.2f}')

def mcnemar(x, y):
    n = x + y
    if n == 0: return 1.0
    k = min(x, y)
    return min(1.0, 2 * sum(math.comb(n, i) for i in range(k + 1)) / 2 ** n)

per = collections.defaultdict(lambda: [0, 0, 0, 0])   # games, winsA, winsB, onlyB-minus-onlyA tallies below
gain = collections.defaultdict(lambda: [0, 0])        # [won only in B, won only in A]
for k in keys:
    a, b, s = k
    for d, seat in ((a, 1), (b, 2)):
        wa, wb = A[k][0] == seat, B[k][0] == seat
        per[d][0] += 1; per[d][1] += wa; per[d][2] += wb
        if wb and not wa: gain[d][0] += 1
        if wa and not wb: gain[d][1] += 1

print(f'\n{"deck":44} {"style":12} {LA:>7} {LB:>7}  {"delta":>6}  +B/-B   p')
for d in sorted(per, key=lambda d: (per[d][2] - per[d][1]) / per[d][0], reverse=True):
    n, wa, wb, _ = per[d]; g, l = gain[d]; p = mcnemar(g, l)
    print(f'{d:44} {style.get(d, "?"):12} {wa / n:7.1%} {wb / n:7.1%}  {(wb - wa) / n * 100:+5.1f}  {g:3}/{l:<3} {p:.3f}{" *" if p < 0.05 else ""}')

print(f'\n{"style":12} {LA:>7} {LB:>7}  delta')
by = collections.defaultdict(lambda: [0, 0, 0])
for d, (n, wa, wb, _) in per.items():
    t = by[style.get(d, '?')]; t[0] += n; t[1] += wa; t[2] += wb
for st, (n, wa, wb) in sorted(by.items()):
    print(f'{st:12} {wa / n:7.1%} {wb / n:7.1%}  {(wb - wa) / n * 100:+5.1f}')
