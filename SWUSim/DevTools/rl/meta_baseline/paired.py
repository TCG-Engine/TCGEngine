#!/usr/bin/env python3
"""Paired comparison of two meta run dirs of ONE set (skill `swusim-meta-bot-run`; same matches, seed, seats): games whose winner changed,
   per deck style and archetype — how one lever is isolated from every other code change.
   python3 paired.py <run A> <run B> <meta dir> [min games per archetype]   — A is the arm under test, B the reference (a plain '' arm on
   today's code). Min games defaults to the set's meta.json 'pairedMin', else 60 (small sets such as HMW want ~8)."""
import collections, json, os, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import common

if len(sys.argv) < 4:
    sys.exit(__doc__)
a_dir, b_dir, META = sys.argv[1], sys.argv[2], sys.argv[3]
try:
    _meta = json.load(open(os.path.join(META, 'meta.json')))
except (OSError, ValueError):
    _meta = {}
MIN_GAMES = int(sys.argv[4]) if len(sys.argv) > 4 else int(_meta.get('pairedMin', 60))
A, _ = common.load_run(os.path.join(a_dir, 'sweep'))
B, _ = common.load_run(os.path.join(b_dir, 'sweep'))
matches = common.load_matches(META)
style, arch = {}, {}
for m in matches:
    style[m['a']] = m['sa']; style[m['b']] = m['sb']; arch[m['a']] = m['ka']; arch[m['b']] = m['kb']
keep = set(style)
won, lost = collections.Counter(), collections.Counter()
awon, alost, agames = collections.Counter(), collections.Counter(), collections.Counter()
n = changed = 0
for k in set(A) & set(B):
    d1, d2 = k
    if d1 not in keep or d2 not in keep: continue
    n += 1
    for d in (d1, d2): agames[arch[d]] += 1
    wa, wb = A[k][0], B[k][0]
    if wa == wb or wa == 0 or wb == 0: continue
    changed += 1
    winner, loser = (d1, d2) if wa == 1 else (d2, d1)
    won[style[winner]] += 1; lost[style[loser]] += 1
    awon[arch[winner]] += 1; alost[arch[loser]] += 1
print(f'{a_dir} vs {b_dir}: {n} paired games, winner changed in {changed} ({100 * changed / max(1, n):.1f}%)')
for s in ['hyperaggro', 'softaggro', 'midrange', 'softcontrol', 'hardcontrol']:
    print(f'   {s:12s} {won[s]:5d} : {lost[s]:<5d} net {won[s] - lost[s]:+5d}   p {common.sign_p(won[s], lost[s]):.4f}')
moves = sorted(((awon[k] - alost[k]) / agames[k], k) for k in agames if agames[k] >= MIN_GAMES)
print(f'largest archetype moves (net games / games played, {MIN_GAMES}+ games):')
for v, k in moves[:5] + moves[-5:]:
    print(f'   {k:48s} {100 * v:+5.1f}pp  ({awon[k]}:{alost[k]} of {agames[k]})')
