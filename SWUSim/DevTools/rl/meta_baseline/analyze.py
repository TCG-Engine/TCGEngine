#!/usr/bin/env python3
"""Meta-baseline analysis (skill `swusim-ash-meta-bot-run`): how a bot run compares with the REAL tournament results, and — when the
meta dir holds a frozen baseline — with that baseline, game for game (same matches, same seed, both seats, so a changed winner is the
code/variant's doing).

usage: analyze.py <meta dir> <run dir> [--no-baseline] [--min N=30] [--cell-min N=10]
  (a small event — e.g. the first HMW set — needs lower minimums: --min 3 --cell-min 2)
  e.g. analyze.py SWUSim/Tests/BotFixtures/meta-baselines/ash .claude/tmp/ash-meta-runs/<label>

⚠ Match-level winner agreement is NOT a fidelity metric: on the ASH set even the real data's own archetype favourite (leave-one-out)
picks the real match winner only ~53% — tournament matches are near coin flips given archetypes. Read the ARCHETYPE and STYLE sections."""
import sys, os, statistics, collections
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
MIN = int(sys.argv[sys.argv.index('--min') + 1]) if '--min' in sys.argv else 30
CELLMIN = int(sys.argv[sys.argv.index('--cell-min') + 1]) if '--cell-min' in sys.argv else 10
mean = lambda v: statistics.mean(v) if v else float('nan')
sd = lambda v: statistics.pstdev(v) if v else float('nan')
from common import (load_matches, load_labels, load_run, load_baseline, replay, archetypes, cells, pearson, spearman, sign_p)


def fidelity(done, label, title):
    print(f'\n######## {title}')
    rows = archetypes(done, MIN)
    R = [r[1] for r in rows]; B = [r[2] for r in rows]
    print(f'ARCHETYPES ({MIN}+ matches): {len(rows)}   Pearson {pearson(R, B):+.2f}   Spearman {spearman(R, B):+.2f}   '
          f'MAE {mean([abs(a - b) for a, b in zip(R, B)]):.3f} (constant 50%: {mean([abs(a - .5) for a in R]):.3f})   '
          f'spread SD real {sd(R) * 100:.1f}pp / bot {sd(B) * 100:.1f}pp')
    w = collections.defaultdict(lambda: [0.0, 0])
    for k, r, b, n, s in rows: w[s][0] += (b - r) * n; w[s][1] += n
    order = ['hyperaggro', 'softaggro', 'midrange', 'softcontrol', 'hardcontrol']
    print('STYLE BIAS (bot − real game WR, match-weighted): ' + '   '.join(f'{s} {w[s][0] / w[s][1] * 100:+.1f}pp' for s in order if w[s][1]))
    rows.sort(key=lambda r: r[2] - r[1])
    fmt = lambda r: f'   {label.get(r[0], r[0])[:46]:46s} {r[4]:11s} real {r[1]:.0%}  bot {r[2]:.0%}  gap {100 * (r[2] - r[1]):+5.1f}  (n={r[3]})'
    print('most under-performing:'); [print(fmt(r)) for r in rows[:6]]
    print('most over-performing:'); [print(fmt(r)) for r in rows[-6:][::-1]]
    c = [(x[0] / (x[0] + x[1]), x[2] / (x[2] + x[3])) for x in cells(done).values() if x[4] >= CELLMIN and x[0] + x[1] and x[2] + x[3]]
    R = [a for a, b in c]; B = [b for a, b in c]
    fav = sum(1 for r, b in c if (r - .5) * (b - .5) > 0) / max(1, sum(1 for r, b in c if r != .5 and b != .5))
    print(f'MATCHUP CELLS (ordered, {CELLMIN}+ matches): {len(c)}   Pearson {pearson(R, B):+.2f}   '
          f'MAE {mean([abs(a - b) for a, b in zip(R, B)]):.3f} (constant 50%: {mean([abs(a - .5) for a in R]):.3f})   favourite right {fav:.0%}')
    return {r[0]: r for r in rows}


meta, run = sys.argv[1], sys.argv[2]
matches, label = load_matches(meta), load_labels(meta)
bot, fails = load_run(run)
done = replay(matches, bot)
rounds = [v[1] for v in bot.values()]
first = sum(1 for v in bot.values() if v[0] == 1) / max(1, sum(1 for v in bot.values() if v[0]))
print(f'RUN {run}: {len(done)}/{len(matches)} real matches replayed, {len(bot)} games ({fails} failed), median {statistics.median(rounds) if rounds else "-"} rounds, '
      f'seat-1 win {first:.1%}')
print(f'{len(load_matches(meta, include_ibh=True)) - len(matches)} matches on an Intro Battle: Hoth base ignored (20 HP in the sims, 30 HP in real play)')
cur = fidelity(done, label, 'THIS RUN vs REAL')

base = None if '--no-baseline' in sys.argv else load_baseline(meta)
if base is not None:
    bdone = replay(matches, base)
    old = fidelity(bdone, label, 'FROZEN BASELINE vs REAL (for reference)')
    kept = {k for m in matches for k in ((m['a'], m['b']), (m['b'], m['a']))}   # the kept matches' games only (IBH bases ignored)
    common_keys = set(bot) & set(base) & kept
    changed = [k for k in common_keys if bot[k][0] != base[k][0]]
    print(f'\n######## THIS RUN vs FROZEN BASELINE (paired: same match, same seed, same seat)')
    print(f'games compared {len(common_keys)}   winner changed in {len(changed)} ({len(changed) / max(1, len(common_keys)):.1%})')
    # per style: games the style's deck WON now and LOST in the baseline, vs the reverse (sign test)
    st = collections.defaultdict(lambda: [0, 0])
    for m in matches:
        for d1, d2, s1, s2 in ((m['a'], m['b'], m['sa'], m['sb']), (m['b'], m['a'], m['sb'], m['sa'])):
            k = (d1, d2)
            if k not in bot or k not in base or bot[k][0] == base[k][0]: continue
            for seat, s in ((1, s1), (2, s2)):
                if bot[k][0] == seat: st[s][0] += 1
                elif base[k][0] == seat: st[s][1] += 1
    print('per style, games newly WON : newly LOST (sign test):')
    for s in ['hyperaggro', 'softaggro', 'midrange', 'softcontrol', 'hardcontrol']:
        if s in st: print(f'   {s:12s} {st[s][0]:5d} : {st[s][1]:<5d} net {st[s][0] - st[s][1]:+5d}   p {sign_p(*st[s]):.4f}')
    moves = sorted(((k, cur[k][2] - old[k][2]) for k in cur if k in old), key=lambda x: x[1])
    print('largest archetype moves (bot WR, this run − baseline):')
    for k, d in moves[:5] + moves[-5:]:
        print(f'   {label.get(k, k)[:46]:46s} {d * 100:+5.1f}pp   now {cur[k][2]:.0%} (real {cur[k][1]:.0%})')
