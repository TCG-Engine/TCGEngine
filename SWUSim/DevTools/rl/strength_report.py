#!/usr/bin/env python3
"""Strength-test report (RL bots spec, Section 7). Reads strength_test.sh's results.tsv — deck1, deck2, seed,
side (1 = the NEW stack in seat 1), the SWUBOT_METRICS json, game id.

- all: the new stack's win rate over every game, one-sided binomial test against 50% (normal approximation with
  continuity correction). Each deck pair and seed is played both ways, so identical stacks score exactly 50%.
- A game without a winner (a timeout or a crash) is excluded from every rate and reported as a count.
- per style: the new stack's win rate PILOTING that style's decks against the OLD stack's win rate piloting the
  same decks, against the same opponents and seeds (the mirror games), with a one-sided two-proportion z-test.
  (A plain "new wins when it pilots style X" would measure the decks, not the stack: aggro decks win more.)
Verdict: FAIL when the new stack does worse; PASS when it does at least as well; STRONGER when p < 0.05."""
import collections, json, math, sys

games = 0; won = 0; no_winner = 0
new_n, new_w, old_n, old_w = (collections.Counter() for _ in range(4))
for line in open(sys.argv[1]):
    if not line.strip():
        continue
    a, b, _seed, side, metrics, *_ = line.rstrip('\n').split('\t')
    winner = int(json.loads(metrics[len('SWUBOT_METRICS '):]).get('winner') or 0)
    if winner == 0:          # a timeout or a crash: no result for either stack — counted, not scored
        no_winner += 1
        continue
    new_seat = int(side); old_seat = 3 - new_seat
    new_style = (a if new_seat == 1 else b).split('_')[0]
    old_style = (a if old_seat == 1 else b).split('_')[0]
    games += 1; won += winner == new_seat
    new_n[new_style] += 1; new_w[new_style] += winner == new_seat
    old_n[old_style] += 1; old_w[old_style] += winner == old_seat

def verdict(rate_new, rate_ref, p):
    return 'FAIL' if rate_new < rate_ref else ('STRONGER' if p < 0.05 else 'PASS')

z = (won - 0.5 * games - 0.5) / math.sqrt(0.25 * games)
p = 0.5 * math.erfc(z / math.sqrt(2))
print(f"all      new wins {won}/{games} = {100 * won / games:.1f}%  one-sided p = {p:.4f}  {verdict(won / games, 0.5, p)}")
for s in ('aggro', 'normal', 'control'):
    if not new_n[s] or not old_n[s]:
        continue
    pn, po = new_w[s] / new_n[s], old_w[s] / old_n[s]
    pool = (new_w[s] + old_w[s]) / (new_n[s] + old_n[s])
    se = math.sqrt(pool * (1 - pool) * (1 / new_n[s] + 1 / old_n[s])) or 1e-9
    ps = 0.5 * math.erfc(((pn - po) / se) / math.sqrt(2))
    print(f"{s:8s} new piloting {s}: {100 * pn:.1f}% ({new_w[s]}/{new_n[s]}) vs old {100 * po:.1f}% ({old_w[s]}/{old_n[s]})"
          f"  one-sided p = {ps:.4f}  {verdict(pn, po, ps)}")
print(f"games without a winner (excluded from every rate): {no_winner}")
