#!/usr/bin/env python3
"""rl_train.py --learner / --opponents (2026-10-14): one fixed learner deck vs a weighted opponent pool.
   python3 SWUSim/DevTools/rl/rl_train_learner_test.py"""
import os, sys, random, collections
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from rl_train import make_learner_jobs

fails = 0
def check(ok, msg):
    global fails
    print(('PASS' if ok else 'FAIL') + ': ' + msg)
    if not ok: fails += 1

styles = {'krennic': 'softcontrol', 'ahsoka': 'softaggro', 'vader': 'hyperaggro', 'lando': 'hardcontrol'}
pool = {'ahsoka': 3.0, 'vader': 1.0}
jobs = make_learner_jobs(7, 4000, 'krennic', 'softcontrol', pool, styles, random.Random('rl-7'))
check(len(jobs) == 4000, 'one job per game')
check(all(j['learnerDeck'] == 'krennic' and j['learnerStyle'] == 'softcontrol' for j in jobs), 'every job: the fixed learner, on its own style')
check(all(j['opponentDeck'] in pool for j in jobs), 'opponents come only from the pool (a held-out deck never appears)')
check(all(j['opponentStyle'] == styles[j['opponentDeck']] for j in jobs), "each opponent plays its own fixture's style")
seats = collections.Counter(j['learnerSeat'] for j in jobs)
check(seats[1] == seats[2] == 2000 and [j['learnerSeat'] for j in jobs[:4]] == [1, 2, 1, 2], 'the learner seat alternates 1, 2, 1, 2 …')
share = sum(1 for j in jobs if j['opponentDeck'] == 'ahsoka') / len(jobs)
check(0.72 <= share <= 0.78, f'opponents are drawn by weight (3:1 → ~75% ahsoka; got {share:.3f})')
check(len({j['id'] for j in jobs}) == 4000 and jobs[0]['id'] == 'b7-0' and jobs[0]['seed'] == 'b7-0', 'ids/seeds are unique per batch')
again = make_learner_jobs(7, 4000, 'krennic', 'softcontrol', pool, styles, random.Random('rl-7'))
check([j['opponentDeck'] for j in again] == [j['opponentDeck'] for j in jobs], 'deterministic for the same batch seed (resume-safe)')
print('ALL PASS' if fails == 0 else f'{fails} FAILED'); sys.exit(1 if fails else 0)
