"""RL Phase 3 — fold a batch of training games into the learned table (plan:
docs/superpowers/plans/2026-09-14-swusim-rl-phase3-trainer-mvp.md, Task 4).

The table: {style: {state: {move: [visits, mean_return]}}}. Every-visit Monte Carlo: each learned decision the
LEARNER seat made in a game gets that game's return G (spec Section 4 — terminal only in the MVP: +1 win, -1 loss,
-0.25 when the game hit its round cap or ended without a winner). The opponent seat (a fixed heuristic bot) is never
learned. A game with no metrics (a crash / timeout of the process) is counted and skipped.
"""


def reward(metrics, learner_seat):
    winner = int(metrics.get('winner') or 0)
    if metrics.get('capped') or winner == 0:
        return -0.25
    return 1.0 if winner == learner_seat else -1.0


def merge_batch(table, jobs):
    stats = {'games': 0, 'failed': 0, 'capped': 0, 'decisions': 0, 'new_states': 0,
             'learner_wins': {}, 'rounds': {}}
    for job in jobs:
        m = job.get('metrics')
        if not m:
            stats['failed'] += 1
            continue
        seat, style = int(job['learnerSeat']), job['learnerStyle']
        g = reward(m, seat)
        stats['games'] += 1
        if m.get('capped'):
            stats['capped'] += 1
        w = stats['learner_wins'].setdefault(style, [0, 0])
        w[1] += 1
        if int(m.get('winner') or 0) == seat:
            w[0] += 1
        stats['rounds'].setdefault(job.get('pairing', '?'), []).append(int(m.get('rounds') or 0))
        style_table = table.setdefault(style, {})
        for r in job.get('records', []):
            if int(r.get('seat', 0)) != seat:
                continue
            stats['decisions'] += 1
            row = style_table.get(r['s'])
            if row is None:
                stats['new_states'] += 1
                row = style_table[r['s']] = {}
            e = row.get(r['m'])
            if e is None:
                row[r['m']] = [1, g]
            else:
                e[0] += 1
                e[1] += (g - e[1]) / e[0]
    return stats
