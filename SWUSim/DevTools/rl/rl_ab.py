"""RL — the randomised-comparison estimator. Replaces trusting the plain Monte Carlo table (rl_merge.py) for overrides.

Why: RL run 3 (2026-09-15) learned from heuristic-generated games, where a move's mean return mostly comes from the
HEURISTIC choosing it in the situations where it was right. Forcing that move everywhere lost (46.8% vs the heuristic,
no better than random overrides; Control's "pass → attack" was worse than random).

The fix compares, inside each (style, state), two groups the heuristic's judgement cannot reach:
  TREATMENT for move m: decisions where exploration FORCED m. Exploration fires on a hash of (seed, seat, decision #),
                        independent of the situation, then picks uniformly among the L legal moves — so a decision is
                        treated with probability eps/|L|. Each treatment sample is weighted by |L| (inverse
                        probability), so crowded positions are not under-represented (rl_ab_test.py, trap B).
  CONTROL for move m:   decisions where the heuristic kept its own pick and m was LEGAL — the heuristic's own value
                        over the same situations. Learned overrides count as neither.
effect(m) = weighted mean return of treatment − mean return of control: what forcing m does, on average, in the
situations where m is available. That is exactly what a play-time override does (SWUSim/Rl/SwuPolicy.php, swu-v2).

The table: ab[style][state][move] = [tw, twg, tw2, nc, cg] — treatment sum of weights, sum of weight x return, sum
of squared weights; control count and sum of returns. Episode records need 'how' (keep|explore|override) and 'L'.
"""
import math
from rl_merge import reward


def merge_batch_ab(ab, jobs):
    stats = {'games': 0, 'failed': 0, 'explored': 0, 'kept': 0}
    for job in jobs:
        m = job.get('metrics')
        if not m:
            stats['failed'] += 1
            continue
        seat, style = int(job['learnerSeat']), job['learnerStyle']
        g = reward(m, seat)
        stats['games'] += 1
        rows = ab.setdefault(style, {})
        for r in job.get('records', []):
            if int(r.get('seat', 0)) != seat:
                continue
            how, legal = r.get('how'), r.get('L') or []
            if how == 'explore':
                stats['explored'] += 1
                w = len(legal)
                e = rows.setdefault(r['s'], {}).setdefault(r['m'], [0, 0.0, 0, 0, 0.0])
                e[0] += w; e[1] += w * g; e[2] += w * w
            elif how == 'keep':
                stats['kept'] += 1
                row = rows.setdefault(r['s'], {})
                for mv in legal:
                    e = row.setdefault(mv, [0, 0.0, 0, 0, 0.0])
                    e[3] += 1; e[4] += g
    return stats


def ab_effect(e):
    """{qT, qC, nT (effective), nC, effect, se} for one [tw, twg, tw2, nc, cg] entry, or None without both arms."""
    tw, twg, tw2, nc, cg = e
    if tw <= 0 or nc <= 0:
        return None
    qT, qC = twg / tw, cg / nc
    nT = tw * tw / tw2
    se = math.sqrt(max(1e-9, 1 - qT * qT) / nT + max(1e-9, 1 - qC * qC) / nc)
    return {'qT': qT, 'qC': qC, 'nT': nT, 'nC': nc, 'effect': qT - qC, 'se': se}


def significant_ab(ab, z, min_samples):
    """(style, state, move, effect, se) where forcing the move beats the heuristic by > z SE, with at least
    min_samples (effective) in each arm."""
    found = []
    for style, states in ab.items():
        for s, moves in states.items():
            for mv, e in moves.items():
                x = ab_effect(e)
                if x and x['nT'] >= min_samples and x['nC'] >= min_samples and x['effect'] > z * x['se']:
                    found.append((style, s, mv, x['effect'], x['se']))
    return found
