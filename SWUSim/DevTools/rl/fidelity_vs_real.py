"""Bot game-1 sweep vs the REAL matchup targets (SWUSim/Tests/BotFixtures/ash-meta-2026-09/real_matchups.json).

usage: fidelity_vs_real.py <sweep results.tsv> [real_matchups.json] [min real matches = 10]

Pools both seat orders of every ordered fixture pair (a vs b = games a won as seat 1 of a.b and as seat 2 of b.a) and
compares the bot's game win rate with the real gameWinPct, over cells with at least MIN real matches. Reports the
Pearson / Spearman r, MAE against a constant-50% predictor, how often the bot gets the matchup's DIRECTION right, how
often it lands inside the real 95% Wilson interval, and the mean bias per deck style (memory: aggro ran hot, control
cold). Bots play single games, so the comparison is per GAME, never per match."""
import sys, os, re, json, math, glob, collections

res = sys.argv[1]
real_path = sys.argv[2] if len(sys.argv) > 2 else 'SWUSim/Tests/BotFixtures/ash-meta-2026-09/real_matchups.json'
MIN = int(sys.argv[3]) if len(sys.argv) > 3 else 10
real = json.load(open(real_path))
fxdir = os.path.dirname(real_path)
style = {os.path.basename(f)[:-4]: re.search(r'^# Style:\s*(\S+)', open(f).read(), re.M).group(1)
         for f in glob.glob(os.path.join(fxdir, '*.txt'))}

won = collections.Counter(); played = collections.Counter(); fails = 0; rounds = []
for line in open(res):
    a, b, s, m, r, g = line.rstrip('\n').split('\t')
    if not m.startswith('SWUBOT_METRICS ') or '"fail":0' not in r: fails += 1; continue
    met = json.loads(m.split(' ', 1)[1]); w = met['winner']; rounds.append(met['rounds'])
    for d, o, seat in ((a, b, 1), (b, a, 2)):
        played[(d, o)] += 1
        if w == seat: won[(d, o)] += 1

def rank(xs):
    order = sorted(range(len(xs)), key=lambda i: xs[i]); rk = [0.0] * len(xs); i = 0
    while i < len(xs):
        j = i
        while j + 1 < len(xs) and xs[order[j + 1]] == xs[order[i]]: j += 1
        for k in range(i, j + 1): rk[order[k]] = (i + j) / 2
        i = j + 1
    return rk
def pearson(x, y):
    mx, my = sum(x) / len(x), sum(y) / len(y)
    sx = math.sqrt(sum((v - mx) ** 2 for v in x)); sy = math.sqrt(sum((v - my) ** 2 for v in y))
    return sum((p - mx) * (q - my) for p, q in zip(x, y)) / (sx * sy) if sx and sy else float('nan')

cells = []
for a, row in real['cells'].items():
    for b, c in row.items():
        n = sum(c['matches'])
        if n < MIN or played[(a, b)] == 0: continue
        cells.append((a, b, won[(a, b)] / played[(a, b)], c['gameWinPct'], c['gameWinCI95'], n, played[(a, b)]))

print(f'sweep: {sum(played.values()) // 2} games ({fails} failed) | median rounds {sorted(rounds)[len(rounds) // 2] if rounds else "-"}'
      f' | cells with >= {MIN} real matches and bot games: {len(cells)}')
bot = [c[2] for c in cells]; tru = [c[3] for c in cells]
mae = sum(abs(p - q) for p, q in zip(bot, tru)) / len(cells)
mae50 = sum(abs(0.5 - q) for q in tru) / len(cells)
sign = sum(1 for p, q in zip(bot, tru) if (p - 0.5) * (q - 0.5) > 0) / len(cells)
inci = sum(1 for c in cells if c[4][0] <= c[2] <= c[4][1]) / len(cells)
print(f'Pearson r {pearson(bot, tru):+.3f} | Spearman r {pearson(rank(bot), rank(tru)):+.3f}')
print(f'MAE {mae:.3f} (constant-50% predictor: {mae50:.3f}) | direction right {sign:.1%} | inside the real 95% CI {inci:.1%}')

print('\nmean bias (bot - real game WR) by the ROW deck\'s style:')
by = collections.defaultdict(list)
for c in cells: by[style.get(c[0], '?')].append(c[2] - c[3])
for s in ('hyperaggro', 'softaggro', 'midrange', 'softcontrol', 'hardcontrol'):
    if by[s]: print(f'  {s:11} {sum(by[s]) / len(by[s]):+.3f}  over {len(by[s])} cells')

print('\nper deck: bot game WR vs real game WR over its counted cells')
deck = collections.defaultdict(lambda: [0, 0, 0.0, 0])
for a, b, p, q, ci, n, k in cells:
    d = deck[a]; d[0] += won[(a, b)]; d[1] += k; d[2] += q; d[3] += 1
for a, (w, k, qs, nc) in sorted(deck.items(), key=lambda kv: (kv[1][0] / kv[1][1]) - kv[1][2] / kv[1][3]):
    print(f'  {a:40} {style.get(a, "?"):11} bot {w / k:5.1%}  real {qs / nc:5.1%}  gap {w / k - qs / nc:+.1%}  ({nc} cells)')

print('\nlargest misses (|bot - real|):')
for a, b, p, q, ci, n, k in sorted(cells, key=lambda c: -abs(c[2] - c[3]))[:12]:
    print(f'  {a[:30]:30} vs {b[:30]:30} bot {p:5.1%} real {q:5.1%} [{ci[0]:.0%}-{ci[1]:.0%}] ({n} real matches, {k} bot games)')
