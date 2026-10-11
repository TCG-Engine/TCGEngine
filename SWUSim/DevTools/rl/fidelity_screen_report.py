#!/usr/bin/env python3
"""Fidelity screen table: each arm vs the screen baseline, on the real-matchup metrics (fidelity_vs_real.py) plus paired movement.
usage: fidelity_screen_report.py <base results.tsv> <arm results.tsv>...
Columns: the five style biases (bot - real, game win rate), how many decks sit within +-10 pp of real, the per-deck Spearman of
bot vs real win rates, the cell Spearman, games whose winner changed vs the base (deterministic seeds: only the arm differs), and
the shift of each style bias toward 0 (positive = closer to real). Read every arm against how far the two jitter arms move."""
import sys, re, json, math, subprocess, os
HERE = os.path.dirname(os.path.abspath(__file__))
STYLES = ['hyperaggro', 'softaggro', 'midrange', 'softcontrol', 'hardcontrol']

def fid(path):
    out = subprocess.run(['python3', os.path.join(HERE, 'fidelity_vs_real.py'), path], capture_output=True, text=True).stdout
    bias = {m.group(1): float(m.group(2)) for m in re.finditer(r'^\s+(hyperaggro|softaggro|midrange|softcontrol|hardcontrol)\s+([+-][\d.]+)\s+over', out, re.M)}
    decks = [(float(m.group(1)), float(m.group(2))) for m in re.finditer(r'bot\s+([\d.]+)%\s+real\s+([\d.]+)%\s+gap', out)]
    cs = re.search(r'Spearman r ([+-]?[\d.]+|nan)', out)
    return bias, decks, float(cs.group(1)) if cs else float('nan')

def rank(xs):
    o = sorted(range(len(xs)), key=lambda i: xs[i]); r = [0.0] * len(xs); i = 0
    while i < len(xs):
        j = i
        while j + 1 < len(xs) and xs[o[j + 1]] == xs[o[i]]: j += 1
        for k in range(i, j + 1): r[o[k]] = (i + j) / 2
        i = j + 1
    return r

def pearson(x, y):
    if len(x) < 2: return float('nan')
    mx, my = sum(x) / len(x), sum(y) / len(y); sx = math.sqrt(sum((v - mx) ** 2 for v in x)); sy = math.sqrt(sum((v - my) ** 2 for v in y))
    return sum((p - mx) * (q - my) for p, q in zip(x, y)) / (sx * sy) if sx and sy else float('nan')

def wins(path):
    out = {}
    for line in open(path):
        a, b, s, m, r, g = line.rstrip('\n').split('\t')
        if m.startswith('SWUBOT_METRICS ') and '"fail":0' in r: out[(a, b, s)] = json.loads(m.split(' ', 1)[1])['winner']
    return out

def row(label, b, d, c, flips='', base_bias=None):
    within = sum(1 for x, y in d if abs(x - y) <= 10)
    dsp = pearson(rank([x for x, _ in d]), rank([y for _, y in d]))
    toward = ''
    if base_bias is not None:
        toward = ' '.join(f'{(abs(base_bias.get(s, 0)) - abs(b.get(s, 0))) * 100:+5.1f}' for s in STYLES)
    print(f'{label:34s} ' + ' '.join(f'{b.get(s, float("nan")) * 100:+6.1f}' for s in STYLES)
          + f'  {within:5d}  {dsp:+.2f}  {c:+.2f}  {flips:>5s}  {toward}')

base = sys.argv[1]; bb, bd, bc = fid(base); bw = wins(base)
print(f'{"arm":34s} ' + ' '.join(f'{s[:6]:>6s}' for s in STYLES) + '  in10  deckSp cellSp  flips  toward-0 by style (pp)')
row('BASE', bb, bd, bc)
for path in sys.argv[2:]:
    b, d, c = fid(path); w = wins(path)
    ks = set(bw) & set(w); flips = sum(1 for k in ks if bw[k] != w[k])
    row(os.path.basename(os.path.dirname(path)), b, d, c, str(flips), bb)
