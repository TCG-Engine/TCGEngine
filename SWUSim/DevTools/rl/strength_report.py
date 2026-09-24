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
import collections, glob, json, math, os, sys

DEFAULT_FIXTURES = 'SWUSim/Tests/BotFixtures/meta-2026-09'

# Deck name -> style, read from each fixture's "# Style: <style>" header — the same line strength_test.sh
# uses to pick the chooser, so the report and the run always agree on what a deck is.
#
# ⚠ This used to be `deckname.split('_')[0]`, compared against the three names ('aggro','normal','control').
# Real fixtures are called lando_blue, ahsoka_red, piett_red..., so that yielded "lando"/"ahsoka"/"piett",
# nothing ever matched, and the ENTIRE per-style section was skipped in silence — for every run, not just
# some. The 2026-09-24 kill-weight screen was therefore read as "flat" off the aggregate line alone, while
# the per-style split (done by hand afterwards) showed midrange +2.2pp at p <= 0.004. The per-style block
# is the "raise the weak, never lower the strong" check, so it now reports what it could not classify
# instead of dropping it.
def deck_styles(fixture_dir):
    out = {}
    for p in glob.glob(os.path.join(fixture_dir, '*.txt')):
        for line in open(p, encoding='utf-8', errors='replace'):
            if line.startswith('# Style:'):
                out[os.path.basename(p)[:-4]] = line.split(':', 1)[1].strip()
                break
    return out

STYLES = deck_styles(sys.argv[2] if len(sys.argv) > 2 else DEFAULT_FIXTURES)

games = 0; won = 0; no_winner = 0
new_n, new_w, old_n, old_w = (collections.Counter() for _ in range(4))
unknown = collections.Counter()
for line in open(sys.argv[1]):
    if not line.strip():
        continue
    a, b, _seed, side, metrics, *_ = line.rstrip('\n').split('\t')
    winner = int(json.loads(metrics[len('SWUBOT_METRICS '):]).get('winner') or 0)
    if winner == 0:          # a timeout or a crash: no result for either stack — counted, not scored
        no_winner += 1
        continue
    new_seat = int(side); old_seat = 3 - new_seat
    new_deck = a if new_seat == 1 else b
    old_deck = a if old_seat == 1 else b
    for d in (new_deck, old_deck):
        if d not in STYLES:
            unknown[d] += 1
    new_style = STYLES.get(new_deck)
    old_style = STYLES.get(old_deck)
    games += 1; won += winner == new_seat
    if new_style:
        new_n[new_style] += 1; new_w[new_style] += winner == new_seat
    if old_style:
        old_n[old_style] += 1; old_w[old_style] += winner == old_seat

def verdict(rate_new, rate_ref, p):
    return 'FAIL' if rate_new < rate_ref else ('STRONGER' if p < 0.05 else 'PASS')

z = (won - 0.5 * games - 0.5) / math.sqrt(0.25 * games)
p = 0.5 * math.erfc(z / math.sqrt(2))
print(f"all      new wins {won}/{games} = {100 * won / games:.1f}%  one-sided p = {p:.4f}  {verdict(won / games, 0.5, p)}")
for s in sorted(set(new_n) | set(old_n)):
    if not new_n[s] or not old_n[s]:
        continue
    pn, po = new_w[s] / new_n[s], old_w[s] / old_n[s]
    pool = (new_w[s] + old_w[s]) / (new_n[s] + old_n[s])
    se = math.sqrt(pool * (1 - pool) * (1 / new_n[s] + 1 / old_n[s])) or 1e-9
    ps = 0.5 * math.erfc(((pn - po) / se) / math.sqrt(2))
    print(f"{s:8s} new piloting {s}: {100 * pn:.1f}% ({new_w[s]}/{new_n[s]}) vs old {100 * po:.1f}% ({old_w[s]}/{old_n[s]})"
          f"  one-sided p = {ps:.4f}  {verdict(pn, po, ps)}")
if unknown:
    # Never skip in silence: an unclassified deck is a missing fixture or a header typo, and it silently
    # shrinks the per-style denominators above.
    named = ', '.join(f"{d} x{n}" for d, n in sorted(unknown.items()))
    print(f"⚠ no '# Style:' fixture found for {len(unknown)} deck(s), excluded from the per-style split: {named}")
if not new_n:
    print("⚠ per-style split EMPTY — no deck in results.tsv matched a fixture. Pass the fixture dir as argv[2].")
print(f"games without a winner (excluded from every rate): {no_winner}")
