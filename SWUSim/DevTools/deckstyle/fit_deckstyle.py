"""Fit the deck-style classifier's three layers (SWU_DECKSTYLE_MODEL in SWUSim/Custom/BotDeckStyle.php) and validate them
LEAVE-ONE-ARCHETYPE-OUT (every list of the held-out archetype predicted by a model that never saw it). Archetypes weighted equally.
  L1 event share >= HARD -> hard control;  L2 logistic side (aggro vs middle);  L3 logistic hyper|soft and midrange|soft control.
Needs numpy + scipy (a venv: python3 -m venv v && v/bin/pip install numpy scipy).
usage: fit_deckstyle.py <features.jsonl from dump_features.php> [--side f,f] [--aggro f,f] [--ctrl f,f] [--hard 0.35]
Prints the validation and the PHP constant to paste into BotDeckStyle.php. Features are SWUBotDeckFeatures() keys."""
import json, sys, collections, argparse
import numpy as np
from scipy.optimize import minimize
ap = argparse.ArgumentParser(); ap.add_argument('features')
ap.add_argument('--side', default='units3,aggroTags,wipe,draw,burn,heal,avgCost')
ap.add_argument('--aggro', default='burn,cheap,units,units3,draw')
ap.add_argument('--ctrl', default='removalEvent,wipe,big,aggroTags,eventsBig')
ap.add_argument('--hard', type=float, default=0.35); ap.add_argument('--l2', type=float, default=0.01)
a = ap.parse_args()
rows = [json.loads(l) for l in open(a.features)]
F = {'side': a.side.split(','), 'aggro': a.aggro.split(','), 'ctrl': a.ctrl.split(',')}
X = {k: np.array([[float(r['f'][f]) for f in fs] for r in rows]) for k, fs in F.items()}
Y = np.array([r['rank'] for r in rows]); ARCH = np.array([r['arch'] for r in rows]); SET = np.array([r['set'] for r in rows]); GRP = np.array([r['grp'] for r in rows])
ES = np.array([r['f']['events'] / max(1, r['f']['n']) for r in rows])
cnt = collections.Counter(ARCH); W = np.array([1.0 / cnt[x] for x in ARCH])
sig = lambda t: 1 / (1 + np.exp(-np.clip(t, -30, 30)))
def logit(Xm, y, w):
    mu = np.average(Xm, 0, w); sd = np.sqrt(np.average((Xm - mu) ** 2, 0, w)) + 1e-6; Z = (Xm - mu) / sd
    def nll(p):
        q = sig(Z @ p[:-1] + p[-1]); return -(w * (y * np.log(q + 1e-12) + (1 - y) * np.log(1 - q + 1e-12))).sum() / w.sum() + a.l2 * (p[:-1] @ p[:-1])
    p = minimize(nll, np.zeros(Z.shape[1] + 1), method='L-BFGS-B').x
    return mu, sd, p
ev = lambda m, Xm: sig(((Xm - m[0]) / m[1]) @ m[2][:-1] + m[2][-1])
def fit(idx):
    nh = idx[Y[idx] < 4]; ag = nh[Y[nh] <= 1]; ct = nh[(Y[nh] >= 2) & (Y[nh] <= 3)]
    return (logit(X['side'][nh], (Y[nh] >= 2).astype(float), W[nh]), logit(X['aggro'][ag], (Y[ag] == 1).astype(float), W[ag]),
            logit(X['ctrl'][ct], (Y[ct] == 3).astype(float), W[ct]))
def predict(m, idx):
    q, s, b = ev(m[0], X['side'][idx]), ev(m[1], X['aggro'][idx]), ev(m[2], X['ctrl'][idx])
    P = np.stack([(1 - q) * (1 - s), (1 - q) * s, q * (1 - b), q * b], 1)
    return np.where(ES[idx] >= a.hard, 4, P.argmax(1))
pred = np.zeros(len(rows), int)
for x in cnt:
    tr, te = np.where(ARCH != x)[0], np.where(ARCH == x)[0]; pred[te] = predict(fit(tr), te)
acc = lambda m: f'{(pred[m] == Y[m]).sum()}/{m.sum()} ({100 * (pred[m] == Y[m]).mean():.0f}%) within-1 {100 * (abs(pred[m] - Y[m]) <= 1).mean():.0f}%'
print(f'LEAVE-ONE-ARCHETYPE-OUT  owner lists {acc(SET == "A")}  |  unseen HMW/Force Fam {acc(GRP == "U")}  |  tournament {acc(SET == "B")}')
m = fit(np.arange(len(rows)))
def php(name, fs, mm, note):
    mu, sd, p = mm
    ws = ', '.join(f"'{f}' => [{u:.4f}, {d:.4f}, {w:.4f}]" for f, u, d, w in zip(fs, mu, sd, p[:-1]))
    return f"    '{name}' => ['bias' => {p[-1]:.4f}, 'w' => [{ws}]],   // {note}"
print("const SWU_DECKSTYLE_MODEL = [\n    'hardEvents' => %.2f," % a.hard)
print(php('side', F['side'], m[0], 'P(middle side)')); print(php('aggro', F['aggro'], m[1], 'P(soft aggro | aggro side)')); print(php('ctrl', F['ctrl'], m[2], 'P(soft control | middle side)')); print('];')
