#!/usr/bin/env python3
"""Train the value model (spec 2026-09-19 §6): L2 logistic regression, solved exactly (Newton) over memory-mapped chunks.
    python3 SWUSim/DevTools/rl/value_train.py --data /tmp/value_run1/chunks --model /tmp/value_run1/value-model.json \
        --report /tmp/value_run1/report.json --holdout-seeds v0037,v0038,v0039,v0040 \
        --holdout-decks thrawn_yellow,greef,obiwan_vergence
Deterministic: exact solve, chunks in file order. --drop removes collinear columns (the model names what it uses). Prints the pre-registered OFFLINE GATE."""
import argparse, json, os
import numpy as np
from scipy.stats import rankdata   # scipy ships with Debian's python3-sklearn (Task 1)

CONTROL = ('softcontrol', 'hardcontrol')
AGGRO = ('hyperaggro', 'softaggro')


def load_chunks(d):
    man = json.load(open(os.path.join(d, 'manifest.json')))
    games = json.load(open(os.path.join(d, 'games.json')))
    chunks = [(np.load(os.path.join(d, f'chunk_{i:04d}_X.npy'), mmap_mode='r'),
               np.load(os.path.join(d, f'chunk_{i:04d}_M.npy'), mmap_mode='r')) for i in range(man['chunks'])]
    return man, games, chunks


def split_masks(M, games, holdout_seeds, holdout_decks):
    seed = np.array([games[g]['seed'] in holdout_seeds for g in M[:, 0]])
    deck = np.array([games[g]['deckA'] in holdout_decks or games[g]['deckB'] in holdout_decks for g in M[:, 0]])
    return ~seed & ~deck, seed & ~deck, deck


def _cols(X, cols):
    return np.asarray(X if cols is None else X[:, cols], np.float64)


def fit_logistic(chunks, masks, cols, iters=12, l2=1e-4):
    """L2 logistic regression solved EXACTLY by Newton's method (IRLS), streaming over the chunks: every iteration is
    one pass that accumulates the gradient and the (F+1)x(F+1) Hessian, so memory is independent of the row count.
    Replaced mini-batch Adam on 2026-09-19 (offline gate, attempt 1): Adam never converged — the TRAINING set was
    miscalibrated (mean prediction 0.518 vs 0.500 observed; an optimum matches exactly) and the gate failed
    calibration by 0.076; the exact fit is within 0.023. Deterministic: no shuffling, no step size. l2 is per ROW
    (the penalty is l2 * n * |w|^2 / 2), so it means the same at any dataset size; the bias is not penalised."""
    n = 0; s = None; ss = None
    for (X, _), m in zip(chunks, masks):
        Z = _cols(X, cols)[m]
        if len(Z) == 0:
            continue
        s = Z.sum(0) if s is None else s + Z.sum(0)
        ss = (Z * Z).sum(0) if ss is None else ss + (Z * Z).sum(0)
        n += len(Z)
    mean = s / n
    std = np.sqrt(np.maximum(ss / n - mean * mean, 0)); std[std < 1e-9] = 1.0
    F = len(mean)
    theta = np.zeros(F + 1)                              # weights, then the bias
    reg = np.append(np.full(F, l2 * n), 0.0)
    for _ in range(iters):
        g = reg * theta; H = np.diag(reg)
        for (X, M), m in zip(chunks, masks):
            if not m.any():
                continue
            Z = np.hstack([(_cols(X, cols)[m] - mean) / std, np.ones((int(m.sum()), 1))])
            y = np.asarray(M[m, 3], np.float64)
            p = 1 / (1 + np.exp(-(Z @ theta)))
            g += Z.T @ (p - y)
            H += (Z * (p * (1 - p))[:, None]).T @ Z
        step = np.linalg.solve(H, g)
        theta -= step
        if np.max(np.abs(step)) < 1e-10:
            break
    return mean, std, theta[:-1].copy(), float(theta[-1])


def predict(X, mean, std, w, b, cols=None):
    Z = (_cols(X, cols) - mean) / std
    return 1 / (1 + np.exp(-(Z @ w + b)))


def metrics(p, y):
    if len(y) == 0:   # an empty slice: no data — NaN compares False, so the gate cannot pass on it
        return {'logloss': float('nan'), 'brier': float('nan'), 'auc': float('nan'), 'n': 0}
    p = np.clip(np.asarray(p, np.float64), 1e-7, 1 - 1e-7); y = np.asarray(y, np.float64)
    ll = float(-np.mean(y * np.log(p) + (1 - y) * np.log(1 - p)))
    br = float(np.mean((p - y) ** 2))
    ranks = rankdata(p)                                      # average ranks over ties, O(n log n)
    npos = y.sum(); nneg = len(y) - npos
    auc = float((ranks[y == 1].sum() - npos * (npos + 1) / 2) / (npos * nneg)) if npos and nneg else 0.5
    return {'logloss': ll, 'brier': br, 'auc': auc, 'n': int(len(y))}


def calibration(p, y, bins=10, min_n=200):
    out = []
    edges = np.linspace(0, 1, bins + 1)
    for i in range(bins):
        m = (p >= edges[i]) & ((p < edges[i + 1]) if i < bins - 1 else (p <= 1))
        if m.sum() >= min_n:
            out.append((float(p[m].mean()), float(y[m].mean()), int(m.sum())))
    return out


def _collect(chunks, masks, fn):
    ps, ys = [], []
    for (X, M), m in zip(chunks, masks):
        if m.any():
            ps.append(fn(X[m])); ys.append(np.asarray(M[m, 3]))
    return (np.concatenate(ps), np.concatenate(ys)) if ps else (np.zeros(0), np.zeros(0))


def _clock(hp, pot):
    return np.where(pot > 0, np.minimum(10, np.ceil(hp / np.maximum(pot, 1e-9))), 10)


def perturb_threat(X, names):
    """Gate clause 3a (offline prereg, addendum 2): the enemy's biggest unguarded threat gains +1 power, and every
    feature that depends on it is recomputed. Returns (perturbed copy, rows where it applies)."""
    ix = names.index
    X = np.array(X, np.float64, copy=True)
    keep = X[:, ix('their_max_threat')] > 0
    pot = X[:, ix('their_pot_all')] + 1; hp = X[:, ix('my_hp_left')]
    X[:, ix('their_max_threat')] += 1
    X[:, ix('their_pot_all')] = pot
    X[:, ix('their_pot_x_early')] = np.where(X[:, ix('round')] <= 6, pot, 0)
    X[:, ix('their_clock')] = _clock(hp, pot)
    X[:, ix('they_lethal_next')] = (pot >= hp).astype(np.float64)
    X[:, ix('removal_x_threat')] = X[:, ix('hand_removal')] * X[:, ix('their_max_threat')]
    return X, keep


def perturb_my_hp(X, names):
    """Gate clause 3b: my base gains +1 HP; dependents recomputed. Rows with my_hp_frac == 0 are excluded (no max HP)."""
    ix = names.index
    X = np.array(X, np.float64, copy=True)
    frac = X[:, ix('my_hp_frac')]; keep = frac > 0
    maxhp = np.where(keep, X[:, ix('my_hp_left')] / np.where(keep, frac, 1), 1)
    hp = X[:, ix('my_hp_left')] + 1; pot = X[:, ix('their_pot_all')]
    X[:, ix('my_hp_left')] = hp
    X[:, ix('my_hp_frac')] = frac + 1 / maxhp
    X[:, ix('hpdiff_x_round')] += X[:, ix('round')]
    X[:, ix('their_clock')] = _clock(hp, pot)
    X[:, ix('they_lethal_next')] = (pot >= hp).astype(np.float64)
    return X, keep


def behaviour(X, names, fn):
    """Mean change in P(win) under each perturbation, and the share of rows moving the expected way."""
    base = fn(np.asarray(X, np.float64))
    out = {}
    for key, pert, sign in (('threat', perturb_threat, -1), ('myHp', perturb_my_hp, 1)):
        Xp, keep = pert(X, names)
        d = (fn(Xp) - base)[keep]
        out[key] = {'n': int(keep.sum()), 'meanDelta': float(d.mean()) if len(d) else float('nan'),
                    'rightWay': float((np.sign(d) == sign).mean()) if len(d) else float('nan')}
    return out


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--data', required=True); ap.add_argument('--model', required=True); ap.add_argument('--report', required=True)
    ap.add_argument('--holdout-seeds', required=True); ap.add_argument('--holdout-decks', required=True)
    ap.add_argument('--l2', type=float, default=1e-4); ap.add_argument('--iters', type=int, default=12)
    # Offline gate attempt 1 (2026-09-19): four damage features were near-duplicates of pot_all (their_power 0.90), so
    # the fit split one effect among them and their_pot_all's sign flipped. One measure per side is kept.
    ap.add_argument('--drop', default='my_power,their_power,my_pot_ready,their_pot_ready')
    a = ap.parse_args()
    man, games, chunks = load_chunks(a.data)
    allNames = man['names']
    dropped = [d for d in a.drop.split(',') if d]
    unknown = sorted(set(dropped) - set(allNames))
    if unknown:
        raise SystemExit(f'[train] --drop names not in the data: {unknown}')
    cols = [i for i, n in enumerate(allNames) if n not in dropped]
    names = [allNames[i] for i in cols]
    hs, hd = set(a.holdout_seeds.split(',')), set(a.holdout_decks.split(','))
    sp = [split_masks(M, games, hs, hd) for _, M in chunks]
    train = [s[0] for s in sp]; gate = [s[1] for s in sp]; held = [s[2] for s in sp]
    mean, std, w, b = fit_logistic(chunks, train, cols, a.iters, a.l2)
    # Baseline: logistic on (hp diff, round, hp diff x round), trained identically.
    iHm, iHt, iR = allNames.index('my_hp_left'), allNames.index('their_hp_left'), allNames.index('round')
    def base_X(X):
        d = np.asarray(X[:, iHm], np.float64) - np.asarray(X[:, iHt], np.float64); r = np.asarray(X[:, iR], np.float64)
        return np.stack([d, r, d * r], 1)
    bch = [(base_X(X), M) for X, M in chunks]
    bm, bs, bw, bb = fit_logistic(bch, train, None, a.iters, a.l2)
    model_fn = lambda X: predict(X, mean, std, w, b, cols)
    base_fn = lambda X: predict(base_X(X), bm, bs, bw, bb)
    # Control-vs-aggro rows: the row's seat plays a control deck and the other seat an aggro one.
    def cva_masks():
        out = []
        for (X, M), g in zip(chunks, gate):
            sel = np.array([(games[gi]['styleA'] if seat == 1 else games[gi]['styleB']) in CONTROL and
                            (games[gi]['styleB'] if seat == 1 else games[gi]['styleA']) in AGGRO
                            for gi, seat in zip(M[:, 0], M[:, 1])])
            out.append(g & sel)
        return out
    cva = cva_masks()
    rep = {'manifest': man, 'rows': {'train': int(sum(m.sum() for m in train)), 'gate': int(sum(m.sum() for m in gate)),
                                     'heldDecks': int(sum(m.sum() for m in held))}}
    for label, masks in (('gate', gate), ('gate_cva', cva), ('heldDecks', held)):
        pm, y = _collect(chunks, masks, model_fn); pb, _ = _collect(chunks, masks, base_fn)
        rep[label] = {'model': metrics(pm, y), 'baseline': metrics(pb, y)}
        if label == 'gate':
            rep['calibration'] = calibration(pm, y)
    wr = dict(zip(names, (w / std).tolist()))
    rep['signs'] = {'my_hp_left': wr.get('my_hp_left'), 'their_pot_all': wr.get('their_pot_all')}   # information only
    # Clause 3 (offline prereg addendum 2): the BEHAVIOURAL check on the gate rows, all columns, model on its own cols.
    Xg = np.concatenate([np.asarray(X[m]) for (X, _), m in zip(chunks, gate) if m.any()])
    rep['behaviour'] = behaviour(Xg, allNames, lambda Z: predict(Z, mean, std, w, b, cols))
    rel = lambda k: 1 - rep[k]['model']['logloss'] / rep[k]['baseline']['logloss']
    g1 = rel('gate') >= 0.03 and rel('gate_cva') >= 0.03
    g2 = all(abs(pr - ob) <= 0.05 for pr, ob, _ in rep['calibration'])
    g3 = rep['behaviour']['threat']['meanDelta'] < 0 and rep['behaviour']['myHp']['meanDelta'] > 0
    rep['gate_verdict'] = {'beats_baseline_3pct': g1, 'rel_gate': rel('gate'), 'rel_gate_cva': rel('gate_cva'),
                           'calibrated': g2, 'behaviour_ok': g3, 'PASS': bool(g1 and g2 and g3)}
    json.dump({'version': 'swu-value-v1', 'featureVersion': man['featureVersion'], 'names': names,
               'means': mean.tolist(), 'stds': std.tolist(), 'weights': w.tolist(), 'bias': b,
               'trainedOn': {'rows': rep['rows']['train'], 'games': man['games'], 'holdoutSeeds': sorted(hs),
                             'holdoutDecks': sorted(hd), 'solver': 'newton', 'iters': a.iters, 'l2': a.l2,
                             'dropped': dropped}},
              open(a.model, 'w'))
    json.dump(rep, open(a.report, 'w'), indent=1)
    print(json.dumps(rep['gate_verdict']))
    for k in ('gate', 'gate_cva', 'heldDecks'):
        print(k, 'model', rep[k]['model'], 'baseline', rep[k]['baseline'])


if __name__ == '__main__':
    main()
