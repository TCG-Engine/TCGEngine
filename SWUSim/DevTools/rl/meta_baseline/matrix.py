#!/usr/bin/env python3
"""Bot-vs-real matchup matrix (skill `swusim-meta-bot-run`): one row per ordered archetype pair — deck vs opponent, bot game win %,
real game win %, and (when the meta dir holds a frozen baseline and this is not it) the baseline's bot win % — as data and as a
self-contained HTML page for the Artifact tool.

usage: matrix.py <meta dir> <run dir | --baseline> <out.html> [--title "…"] [--subtitle "…"] [--min N=3]
  --min = the fewest real matches a matchup needs to appear (and the page's default filter); a small event wants --min 1.
  --baseline as the run renders the frozen baseline itself (no comparison column)."""
import sys, os, json
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from common import load_matches, load_labels, load_run, load_baseline, replay, cells, styles

HERE = os.path.dirname(os.path.abspath(__file__))


def build(meta, bot, base, min_matches=3):
    matches, label = load_matches(meta), load_labels(meta)
    done = replay(matches, bot)
    st = styles(done)
    bc = cells(replay(matches, base)) if base is not None else {}
    rows = []
    for (d, o), c in cells(done).items():
        if c[4] < min_matches or c[0] + c[1] == 0 or c[2] + c[3] == 0: continue
        b = bc.get((d, o))
        rows.append([label.get(d, d), label.get(o, o), round(100 * c[2] / (c[2] + c[3]), 1), round(100 * c[0] / (c[0] + c[1]), 1),
                     c[4], c[0] + c[1], c[2] + c[3], st.get(d, '?'), st.get(o, '?'),
                     round(100 * b[2] / (b[2] + b[3]), 1) if b and b[2] + b[3] else None])
    rows.sort(key=lambda r: -r[4])
    return rows, len(done), sum(1 for v in bot.values())


def write_html(rows, nmatch, ngames, out, title, subtitle, meta_info, compare):
    data = {'rows': rows, 'meta': dict(meta_info, matches=nmatch, games=ngames, compare=compare, subtitle=subtitle)}
    html = open(os.path.join(HERE, 'matrix_template.html')).read()
    html = html.replace('__TITLE__', title).replace('__DATA__', json.dumps(data))
    open(out, 'w').write(html)


if __name__ == '__main__':
    meta, run, out = sys.argv[1], sys.argv[2], sys.argv[3]
    opt = lambda f, d: sys.argv[sys.argv.index(f) + 1] if f in sys.argv else d
    info = json.load(open(os.path.join(meta, 'meta.json')))
    if run == '--baseline':
        bot, base = load_baseline(meta), None
    else:
        bot, _ = load_run(run); base = load_baseline(meta)
    mn = int(opt('--min', '3'))
    rows, nm, ng = build(meta, bot, base, mn)
    write_html(rows, nm, ng, out, opt('--title', info.get('matrixTitle', 'Bot vs Real Matchups')),
               opt('--subtitle', ''), dict(info, defaultMin=mn if mn < 3 else 10), base is not None)
    print(f'wrote {out}: {len(rows)} matchups ({mn}+ matches), {sum(1 for r in rows if r[4] >= 10)} with 10+'
          + (', with baseline column' if base is not None else ''))
