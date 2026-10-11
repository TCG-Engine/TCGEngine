"""RL — the randomised-comparison estimator (rl_ab.py). RL run 3 (2026-09-15) showed the plain Monte Carlo table is
biased: a move's mean mostly comes from the HEURISTIC choosing it where it was right, so forcing it everywhere lost
(46.8%; no better than random overrides). This estimator compares, inside each state, the games where exploration
FORCED a move against the heuristic's own games where that move was legal. Exploration fires independently of the
situation, so the heuristic's judgement cannot leak into the comparison.
    docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 python3 SWUSim/DevTools/rl/rl_ab_test.py
"""
import os, random, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from rl_ab import merge_batch_ab, ab_effect, significant_ab
from rl_merge import merge_batch
from rl_select import significant_pairs

fails = 0
def check(ok, msg):
    global fails
    print(('PASS: ' if ok else 'FAIL: ') + msg)
    if not ok: fails += 1

def rec(m, how, L, s='s', seat=1, style='control', fb=None):
    return {'seat': seat, 'style': style, 's': s, 'm': m, 'how': how, 'fb': fb if fb is not None else m, 'L': L}

def game(records, winner, seat=1, style='control', capped=False):
    return {'learnerSeat': seat, 'learnerStyle': style, 'pairing': 'control/normal',
            'metrics': {'winner': winner, 'capped': capped, 'rounds': 8}, 'records': records}

# ── 1. What each arm counts ──────────────────────────────────────────────────────────────────────────────────────
ab = {}
st = merge_batch_ab(ab, [
    # a win: the heuristic kept 'pass' with {pass, attack} legal → a CONTROL sample for both moves
    game([rec('pass', 'keep', ['attack', 'pass'])], 1),
    # a loss: exploration forced 'attack' with 2 legal moves → a TREATMENT sample for attack, weight 2
    game([rec('attack', 'explore', ['attack', 'pass'], fb='pass')], 2),
    # a learned override is neither arm; the opponent seat's lines are never learned
    game([rec('attack', 'override', ['attack', 'pass'], fb='pass'), rec('pass', 'keep', ['attack', 'pass'], seat=2)], 1),
    # a crash teaches nothing
    {'learnerSeat': 1, 'learnerStyle': 'control', 'pairing': 'control/normal', 'metrics': None,
     'records': [rec('pass', 'keep', ['attack', 'pass'])]},
])
row = ab['control']['s']
check(row['pass'][3:] == [1, 1.0] and row['attack'][3:] == [1, 1.0], 'a kept decision is a control sample for EVERY legal move; got %r' % row)
check(row['attack'][:3] == [2, -2.0, 4] and row['pass'][:3] == [0, 0.0, 0],
      'an explored decision is a treatment sample for the forced move only, weighted by the legal-move count; got %r' % row)
check(st['explored'] == 1 and st['kept'] == 1 and st['failed'] == 1 and st['games'] == 3,
      'batch stats count explored / kept decisions and failed games; got %r' % st)
e = ab_effect(row['attack'])
check(e['nT'] == 1 and e['nC'] == 1 and abs(e['qT'] + 1) < 1e-9 and abs(e['qC'] - 1) < 1e-9 and abs(e['effect'] + 2) < 1e-9,
      'ab_effect: treatment mean, control mean, effective sizes, effect; got %r' % e)
check(ab_effect([0, 0.0, 0, 5, 1.0]) is None, 'no treatment samples → no effect estimate')

# ── 2. Trap A: the heuristic's judgement (what sank RL run 3) ─────────────────────────────────────────────────────
# One state, two hidden situations. Ahead (45%): the heuristic attacks and wins 90%. Behind: it passes and wins 40%.
# Forcing 'attack' while behind drops that to 15%, so attack is BAD to force (it only looks good because the heuristic
# picks it when ahead). 'dig' is never picked by the heuristic but helps in both situations (+5 / +10 points).
P_WIN = {('ahead', 'attack'): .90, ('ahead', 'pass'): .80, ('ahead', 'dig'): .95,
         ('behind', 'pass'): .40, ('behind', 'attack'): .15, ('behind', 'dig'): .50}
rng = random.Random(7); EPS = 0.25; L = ['attack', 'dig', 'pass']
jobs = []
for _ in range(60000):
    sit = 'ahead' if rng.random() < .45 else 'behind'
    fb = 'attack' if sit == 'ahead' else 'pass'
    if rng.random() < EPS:
        m, how = rng.choice(L), 'explore'
    else:
        m, how = fb, 'keep'
    jobs.append(game([rec(m, how, L, fb=fb)], 1 if rng.random() < P_WIN[(sit, m)] else 2))
v1, ab = {}, {}
merge_batch(v1, jobs); merge_batch_ab(ab, jobs)
old = {(p[2], p[3]) for p in significant_pairs(v1, 3.0)}
new = {p[2] for p in significant_ab(ab, 3.0, 100)}
check(('pass', 'attack') in old, 'the trap is real: the old estimator flags pass → attack; got %r' % old)
check('attack' not in new, 'the new estimator does NOT flag attack; got %r' % new)
check('dig' in new, 'the new estimator finds the move that genuinely helps (dig); got %r' % new)
eff = ab_effect(ab['control']['s']['attack'])
check(eff['effect'] < 0, 'forcing attack measures NEGATIVE (true effect: -0.275 in return); got %.3f' % eff['effect'])

# ── 3. Trap B: the legal-move count ───────────────────────────────────────────────────────────────────────────────
# Forcing 'm' changes nothing anywhere. But in the good situation (win 80%) only 2 moves are legal, and in the bad one
# (win 30%) 10 are, so exploration forces 'm' 5x as often in the good one. Unweighted, 'm' looks like a big winner.
rng = random.Random(11); jobs = []
for _ in range(60000):
    good = rng.random() < .5
    Ls = ['fb', 'm'] if good else ['fb', 'm'] + [f'o{i}' for i in range(8)]
    if rng.random() < EPS:
        m, how = rng.choice(Ls), 'explore'
    else:
        m, how = 'fb', 'keep'
    jobs.append(game([rec(m, how, Ls, fb='fb')], 1 if rng.random() < (.8 if good else .3) else 2))
ab = {}
merge_batch_ab(ab, jobs)
e = ab['control']['s']['m']
naive = sum(1 if j['metrics']['winner'] == 1 else -1 for j in jobs if j['records'][0]['how'] == 'explore' and j['records'][0]['m'] == 'm')
naive /= sum(1 for j in jobs if j['records'][0]['how'] == 'explore' and j['records'][0]['m'] == 'm')
check(naive - ab_effect(e)['qC'] > 0.25, 'the trap is real: an unweighted forced-move mean beats control by %.2f' % (naive - ab_effect(e)['qC']))
check(abs(ab_effect(e)['effect']) < 0.05, 'weighted by the legal-move count, the effect is ~0; got %.3f' % ab_effect(e)['effect'])
check('m' not in {p[2] for p in significant_ab(ab, 3.0, 100)}, 'and it is not flagged')

# ── 4. The significance floor ─────────────────────────────────────────────────────────────────────────────────────
ab = {'control': {'s': {'dig': [60, 30.0, 120, 50, 0.0]}}}      # effect +0.5 but only 30 treatment samples
check(significant_ab(ab, 3.0, 100) == [], 'fewer than min_samples treatment samples → never flagged')

print('\nALL PASS' if fails == 0 else '\n%d FAILED' % fails)
sys.exit(1 if fails else 0)
