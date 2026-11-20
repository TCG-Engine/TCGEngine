#!/usr/bin/env python3
# usage: paired.py <run dir>   (a run-panel.sh output: arm-control/ + arm-on/ + meta.txt; e.g. .claude/tmp/postban-panel/runs/shipwait)
# Wins for OUR seat ("ours", either seat) per panel deck + the mirror, paired on (seatA, seatB, seed): flips and a two-sided
# sign test, per opponent style, and how often our variant's lever fired (coverage keys containing the lever name).
import json, math, sys, collections, re
d = sys.argv[1].rstrip('/')
meta = dict(l.strip().split('=', 1) for l in open(f'{d}/meta.txt') if '=' in l)
lever = re.sub(r'^(try|no)-', '', meta.get('on') or meta.get('control') or '')
style = {}
for f in __import__('glob').glob(f'{d}/fx/*.txt'):
    m = re.search(r'^# Style:\s*(\S+)', open(f).read(), re.M)
    style[f.rsplit('/', 1)[1][:-4]] = m.group(1) if m else '?'
def load(arm):
    out = {}
    for line in open(f'{d}/{arm}/results.tsv'):
        a, b, s, g, m = (line.rstrip('\n').split('\t') + [''] * 5)[:5]
        try: out[(a, b, s)] = json.loads(m.split(' ', 1)[1])
        except Exception: out[(a, b, s)] = None
    return out
def sign_p(up, dn):
    n = up + dn
    return 1.0 if n == 0 else min(1.0, 2 * sum(math.comb(n, i) for i in range(min(up, dn) + 1)) / 2 ** n)
C, O = load('arm-control'), load('arm-on')
tab = collections.defaultdict(lambda: [0, 0, 0, 0, 0]); fired = collections.Counter(); failed = 0
for k, mc in C.items():
    mo = O.get(k)
    if not mc or not mo: failed += 1; continue
    a, b, _ = k
    seat = 1 if a == 'ours' else 2
    opp = b if seat == 1 else a
    wc, wo = int(mc.get('winner') == seat), int(mo.get('winner') == seat)
    t = tab[opp]; t[0] += 1; t[1] += wc; t[2] += wo; t[3] += int(wo > wc); t[4] += int(wc > wo)
    for side, cov in (mo.get('coverage') or {}).items():
        for name, n in cov.items():
            if lever and lever in name: fired['ours' if int(side) == seat else 'opp'] += n
print(f"{meta.get('name')}: ours={meta.get('ours')} ({meta.get('style')})  control=[{meta.get('control')}] on=[{meta.get('on')}]  panel {meta.get('panel')}")
print(f'paired games {sum(t[0] for t in tab.values())}  failed {failed}')
for opp, t in sorted(tab.items()):
    print(f'  {opp:20} {style.get(opp, "?"):12} control {t[1]:3}/{t[0]}  on {t[2]:3}/{t[0]}  flips +{t[3]}/-{t[4]}  p={sign_p(t[3], t[4]):.3f}')
by = collections.defaultdict(lambda: [0, 0, 0, 0, 0])
for opp, t in tab.items():
    if opp == 'mirror': continue
    for i in range(5): by[style.get(opp, '?')][i] += t[i]
for s, t in sorted(by.items()):
    print(f'  [style {s:11}] control {t[1]}/{t[0]}  on {t[2]}  flips +{t[3]}/-{t[4]}  p={sign_p(t[3], t[4]):.3f}')
pan = [t for o, t in tab.items() if o != 'mirror']
up, dn = sum(t[3] for t in pan), sum(t[4] for t in pan)
print(f'  PANEL (8 decks)  control {sum(t[1] for t in pan)}/{sum(t[0] for t in pan)}  on {sum(t[2] for t in pan)}  flips +{up}/-{dn}  p={sign_p(up, dn):.3f}')
print(f'  lever firings in the ON arm ("{lever}"):', dict(fired))
