#!/usr/bin/env python3
"""Per-deck screen report (fidelity plan Task 6): one deck's game win rate per opponent in each arm, next to the real number.
usage: deck_report.py <deck> <label>=<results.tsv> ...
Pools both seats. 'gap' = the deck's mean (bot - real) over opponents with >= 10 real matches (each opponent weighted equally)."""
import sys, json, collections
REAL = 'SWUSim/Tests/BotFixtures/ash-meta-2026-09/real_matchups.json'
deck = sys.argv[1]
arms = [a.split('=', 1) for a in sys.argv[2:]]
real = json.load(open(REAL))['cells'].get(deck, {})
tab = collections.defaultdict(dict)
for label, path in arms:
    w = collections.Counter(); n = collections.Counter()
    for line in open(path):
        a, b, s, m, r, g = line.rstrip('\n').split('\t')
        if deck not in (a, b) or not m.startswith('SWUBOT_METRICS ') or '"fail":0' not in r: continue
        seat = 1 if a == deck else 2; opp = b if seat == 1 else a
        n[opp] += 1; w[opp] += json.loads(m.split(' ', 1)[1])['winner'] == seat
    for o in n: tab[o][label] = (w[o], n[o])
labels = [l for l, _ in arms]
print(f'{"opponent":38s} {"real":>5s} ' + ' '.join(f'{l[:12]:>12s}' for l in labels))
gaps = collections.defaultdict(list); tot = collections.defaultdict(lambda: [0, 0])
for o in sorted(tab, key=lambda o: -sum(real.get(o, {}).get('matches', [0]))):
    c = real.get(o); rp = c['gameWinPct'] if c and sum(c['matches']) >= 10 else None
    cells = []
    for l in labels:
        if l in tab[o]:
            x, k = tab[o][l]; cells.append(f'{100 * x / k:5.0f}% ({k:2d})'); tot[l][0] += x; tot[l][1] += k
            if rp is not None: gaps[l].append(x / k - rp)
        else: cells.append(f'{"-":>12s}')
    print(f'{o:38s} {("%4.0f%%" % (100 * rp)) if rp is not None else "  -  "} ' + ' '.join(f'{c:>12s}' for c in cells))
print(f'{"ALL (win %)":38s} {"":5s} ' + ' '.join(f'{100 * tot[l][0] / max(1, tot[l][1]):11.1f}%' for l in labels))
print(f'{"GAP vs real (pp)":38s} {"":5s} ' + ' '.join(f'{100 * sum(gaps[l]) / max(1, len(gaps[l])):+11.1f} ' for l in labels))
