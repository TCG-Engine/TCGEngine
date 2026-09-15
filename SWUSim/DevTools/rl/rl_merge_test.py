"""RL Phase 3, Task 4 — the batch merge (rl_merge.py): Monte Carlo mean returns per (style, state, move).
    docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 python3 SWUSim/DevTools/rl/rl_merge_test.py
"""
import os, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from rl_merge import reward, merge_batch

fails = 0
def check(ok, msg):
    global fails
    print(('PASS: ' if ok else 'FAIL: ') + msg)
    if not ok: fails += 1

check(reward({'winner': 1, 'capped': False}, 1) == 1.0 and reward({'winner': 2, 'capped': False}, 1) == -1.0,
      'win +1, loss -1')
check(reward({'winner': 0, 'capped': True}, 2) == -0.25 and reward({'winner': 0, 'capped': False}, 2) == -0.25,
      'a capped game, or one with no winner, scores -0.25')

table = {}
# Game 1: the learner (seat 1, normal) wins; it logged m1 twice in state s. Seat 2's lines (the opponent) are ignored.
# Game 2: capped; the learner (seat 2, normal) logged m2 once.
jobs = [
    {'learnerSeat': 1, 'learnerStyle': 'normal', 'metrics': {'winner': 1, 'capped': False, 'rounds': 6},
     'pairing': 'aggro/normal',
     'records': [{'seat': 1, 'style': 'normal', 's': 's', 'm': 'm1'}, {'seat': 1, 'style': 'normal', 's': 's', 'm': 'm1'},
                 {'seat': 2, 'style': 'aggro', 's': 'x', 'm': 'y'}]},
    {'learnerSeat': 2, 'learnerStyle': 'normal', 'metrics': {'winner': 0, 'capped': True, 'rounds': 19},
     'pairing': 'aggro/normal', 'records': [{'seat': 2, 'style': 'normal', 's': 's', 'm': 'm2'}]},
]
stats = merge_batch(table, jobs)
check(table['normal']['s']['m1'] == [2, 1.0], 'm1 = [2 visits, mean 1.0]; got %r' % table['normal']['s'].get('m1'))
check(table['normal']['s']['m2'] == [1, -0.25], 'm2 = [1, -0.25]; got %r' % table['normal']['s'].get('m2'))
check('aggro' not in table, 'the opponent seat is never learned')
# The spec's guardrail counts decisions that CREATE a state: the first visit to s creates it, the other two don't.
check(stats['games'] == 2 and stats['capped'] == 1 and stats['decisions'] == 3 and stats['new_states'] == 1,
      'batch stats: games, capped, decisions, state-creating decisions; got %r' % stats)
check(stats['learner_wins']['normal'] == [1, 2], 'learner wins per style: [wins, games]; got %r' % stats['learner_wins'])

# A second batch: a loss on m1 → [3, 1/3]; the state is no longer new.
stats2 = merge_batch(table, [{'learnerSeat': 1, 'learnerStyle': 'normal', 'metrics': {'winner': 2, 'capped': False, 'rounds': 8},
                              'pairing': 'normal/normal', 'records': [{'seat': 1, 'style': 'normal', 's': 's', 'm': 'm1'}]}])
n, mean = table['normal']['s']['m1']
check(n == 3 and abs(mean - 1.0 / 3.0) < 1e-9, 'a loss folds in: [3, 0.333]; got %r' % table['normal']['s']['m1'])
check(stats2['new_states'] == 0, 'a known state is not new')

# A game with no metrics (a crash) teaches nothing.
stats3 = merge_batch(table, [{'learnerSeat': 1, 'learnerStyle': 'normal', 'metrics': None, 'pairing': 'normal/normal',
                              'records': [{'seat': 1, 'style': 'normal', 's': 's', 'm': 'm1'}]}])
check(table['normal']['s']['m1'][0] == 3 and stats3['failed'] == 1, 'a failed game is counted and skipped')

print('\nALL PASS' if fails == 0 else '\n%d FAILED' % fails)
sys.exit(1 if fails else 0)
