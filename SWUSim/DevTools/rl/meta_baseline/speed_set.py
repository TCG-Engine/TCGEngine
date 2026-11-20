#!/usr/bin/env python3
"""The meta's SPEED SET (skill `swusim-meta-bot-run`): the LONGEST games of the frozen baseline run, each in its own seat order only.
A bot change flips few games (0–5% on the HMW arms of 2026-10-24…28) and nearly all of them are long ones: the longest 5% of the
control's games held 31% of every flip, the longest 10% held 54%, the shorter half held 2%. So a speed run replays only these games
and pairs them with a control (or with the baseline itself — same_code.py). ⚠ It misses most EARLY-game changes (mulligan/curve —
curvekeep: 5 of 27 flips in the longest 10%); for those, screen on a lever subset or run the full set. It gives no fidelity numbers:
analyze.py needs both seats of a match.

usage: speed_set.py <meta dir> <run dir> [--pct N]   (N defaults to the set's meta.json 'speedPct', else 5)
Writes <meta>/baseline/speed.games ("deck1 deck2" per line, that seat order only) and a 'speed' entry in baseline/meta.json."""
import json, os, sys


def build(meta, run, pct=None):
    if pct is None:
        try: pct = float(json.load(open(os.path.join(meta, 'meta.json'))).get('speedPct', 5))
        except (OSError, ValueError): pct = 5.0
    games = []
    for l in open(os.path.join(run, 'sweep', 'results.tsv') if os.path.isdir(os.path.join(run, 'sweep')) else os.path.join(run, 'results.tsv')):
        p = l.rstrip('\n').split('\t')
        if len(p) < 5 or not p[3].startswith('SWUBOT_METRICS ') or '"fail":0' not in p[4]: continue
        m, r = json.loads(p[3].split(' ', 1)[1]), json.loads(p[4].split(' ', 1)[1])
        games.append((-int(m.get('rounds') or 0), -int(r.get('steps') or 0), p[0], p[1]))
    games.sort()   # longest first: rounds, then steps, then the deck ids (a stable cut)
    pick = games[:round(len(games) * pct / 100)]
    B = os.path.join(meta, 'baseline')
    with open(os.path.join(B, 'speed.games'), 'w') as f:
        for _, _, d1, d2 in pick: f.write(f'{d1} {d2}\n')
    steps = sum(-g[1] for g in games)
    info = {'pct': pct, 'games': len(pick), 'minRounds': -pick[-1][0], 'stepsShare': round(sum(-g[1] for g in pick) / steps, 3)}
    mp = os.path.join(B, 'meta.json')
    bm = json.load(open(mp)) if os.path.exists(mp) else {}
    bm['speed'] = info
    json.dump(bm, open(mp, 'w'), indent=1)
    return info


if __name__ == '__main__':
    if len(sys.argv) < 3: sys.exit(__doc__)
    info = build(sys.argv[1], sys.argv[2], float(sys.argv[sys.argv.index('--pct') + 1]) if '--pct' in sys.argv else None)
    print(f"speed set: {info['games']} games (longest {info['pct']:g}%, {info['minRounds']}+ rounds, {info['stepsShare']:.0%} of the run's steps)")
