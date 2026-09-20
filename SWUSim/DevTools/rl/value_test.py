#!/usr/bin/env python3
"""Tests for value_pack.py / value_train.py (spec 2026-09-19 §9).
    docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 python3 SWUSim/DevTools/rl/value_test.py"""
import json, os, sys, tempfile, shutil
import numpy as np
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import value_pack, value_train

FAILS = 0
def check(ok, msg):
    global FAILS
    print(('PASS' if ok else 'FAIL') + ': ' + msg)
    FAILS += 0 if ok else 1

NAMES = ['round', 'my_hp_left', 'their_hp_left', 'x']
def write_game(d, name, rows, winner, capped=False, seed='v0001', a='control_a', b='aggro_b'):
    with open(os.path.join(d, name), 'w') as f:
        f.write(json.dumps({'h': 1, 'names': NAMES, 'featureVersion': 'abc123abc123'}) + '\n')
        for r in rows:
            f.write(json.dumps(r) + '\n')
        res = None if winner is None else {'winner': winner, 'capped': capped, 'rounds': 7}
        f.write(json.dumps({'result': res, 'deckA': a, 'deckB': b, 'styleA': 'softcontrol', 'styleB': 'softaggro', 'seed': seed}) + '\n')

tmp = tempfile.mkdtemp()
try:
    pos = os.path.join(tmp, 'pos'); os.makedirs(pos)
    write_game(pos, 'g1.jsonl', [[1, 1, 30, 20, 0.5], [2, 1, 20, 30, -0.5]], winner=1)
    write_game(pos, 'g2.jsonl', [[1, 2, 10, 25, 1.0]], winner=2, seed='v0002')
    write_game(pos, 'g3.jsonl', [[1, 3, 5, 5, 0.0]], winner=1, capped=True)
    write_game(pos, 'g4.jsonl', [[1, 3, 5, 5, 0.0]], winner=None)
    out = os.path.join(tmp, 'chunks')
    value_pack.pack(pos, out, chunk_rows=2)
    man, games, chunks = value_train.load_chunks(out)
    X = np.concatenate([c[0] for c in chunks]); M = np.concatenate([c[1] for c in chunks])
    check(man['names'] == NAMES and man['featureVersion'] == 'abc123abc123', 'manifest carries names + version')
    check(man['rows'] == 3 and len(chunks) == 2, 'capped + no-result games dropped; 3 rows in 2 chunks of <= 2')
    check(man['dropped'] == {'capped': 1, 'failed': 0, 'noResult': 1}, 'drop counts: %r' % man['dropped'])
    check(np.allclose(X[0], [1, 30, 20, 0.5]) and X.dtype == np.float32, 'round-trip: first row features exact')
    check(M[:, 3].tolist() == [1, 0, 0], 'labels: seat 1 won g1 (1), seat 2 lost g1 (0), seat 1 lost g2 (0)')
    check(games[M[2, 0]]['seed'] == 'v0002', 'game index resolves to its seed')

    # Recovery on synthetic data: y ~ Bernoulli(sigmoid(2*x0 - 1*x1)).
    rng = np.random.RandomState(0)
    Xs = rng.randn(40000, 3).astype(np.float32)
    y = (rng.rand(40000) < 1 / (1 + np.exp(-(2 * Xs[:, 0] - Xs[:, 1])))).astype(np.int32)
    Ms = np.zeros((40000, 4), np.int32); Ms[:, 3] = y
    ch = [(Xs[:20000], Ms[:20000]), (Xs[20000:], Ms[20000:])]
    masks = [np.ones(20000, bool), np.ones(20000, bool)]
    mean, std, w, b = value_train.fit_logistic(ch, masks, None, iters=12, l2=0.0)
    wr = w / std   # back to raw units
    check(abs(wr[0] - 2) < 0.15 and abs(wr[1] + 1) < 0.15 and abs(wr[2]) < 0.1, 'recovers known weights: %r' % wr)
    again = value_train.fit_logistic(ch, masks, None, iters=12, l2=0.0)
    check(all(np.array_equal(a, c) for a, c in zip((mean, std, w), again[:3])) and b == again[3], 'deterministic for a fixed seed')
    one = value_train.fit_logistic([(Xs, Ms)], [np.ones(40000, bool)], None, iters=12, l2=0.0)
    check(np.allclose(mean, one[0], atol=1e-5) and np.allclose(std, one[1], atol=1e-5), 'streaming standardisation == one-chunk standardisation')
    sub = value_train.fit_logistic(ch, masks, [0, 1], iters=12, l2=0.0)
    check(len(sub[2]) == 2 and abs(sub[2][0] / sub[1][0] - 2) < 0.15, 'column selection trains on the kept columns only')
    p = value_train.predict(Xs, mean, std, w, b)
    mt = value_train.metrics(p, y)
    check(0.0 < mt['logloss'] < 0.6 and mt['auc'] > 0.8, 'metrics sane: %r' % mt)
    check(abs(value_train.metrics(np.full(4, 0.5), np.array([0, 1, 0, 1]))['auc'] - 0.5) < 1e-9, 'AUC of a constant is 0.5')
    # BEHAVIOURAL check (gate clause 3, addendum 2): perturb, recompute dependents, compare P(win).
    BN = ['round', 'my_hp_left', 'my_hp_frac', 'their_pot_all', 'their_pot_x_early', 'their_clock', 'they_lethal_next',
          'their_max_threat', 'removal_x_threat', 'hand_removal', 'hpdiff_x_round']
    rows = np.array([[3, 10, 0.5, 4, 4, 3, 0, 4, 0, 0, -30],     # early, a threat
                     [8, 20, 1.0, 0, 0, 10, 0, 0, 0, 1, 80]], np.float32)   # no unguarded threat: excluded from 3a
    Xa, keep = value_train.perturb_threat(rows, BN)
    check(keep.tolist() == [True, False], '3a applies only where an unguarded threat exists')
    r = Xa[0]; ix = BN.index
    check((r[ix('their_max_threat')], r[ix('their_pot_all')], r[ix('their_pot_x_early')], r[ix('their_clock')]) == (5, 5, 5, 2),
          '3a recomputes threat, potential, early interaction and clock (ceil(10/5) = 2): %r' % r.tolist())
    Xb, keepb = value_train.perturb_my_hp(rows, BN)
    rb = Xb[0]
    check(rb[ix('my_hp_left')] == 11 and abs(rb[ix('my_hp_frac')] - 0.55) < 1e-6 and rb[ix('hpdiff_x_round')] == -27
          and rb[ix('their_clock')] == 3, '3b recomputes HP, fraction, interaction and their clock: %r' % rb.tolist())
    toy = lambda X: 1 / (1 + np.exp(-(0.1 * X[:, ix('my_hp_left')] - 0.3 * X[:, ix('their_max_threat')])))
    beh = value_train.behaviour(rows, BN, toy)
    check(beh['threat']['meanDelta'] < 0 and beh['myHp']['meanDelta'] > 0 and beh['threat']['n'] == 1, 'behaviour: %r' % beh)
finally:
    shutil.rmtree(tmp)
print('\n' + ('ALL PASS' if FAILS == 0 else f'{FAILS} FAILED'))
sys.exit(1 if FAILS else 0)
