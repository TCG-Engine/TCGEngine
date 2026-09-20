#!/usr/bin/env python3
"""CEILING DIAGNOSTIC (spec 2026-09-19 §6): gradient-boosted trees on the SAME split as value_train.py. Never a
pass/fail. Trees ~= logistic => features are the bottleneck; trees clearly better => a small MLP is the v2.
    python3 SWUSim/DevTools/rl/value_ceiling.py --data /tmp/value_run1/chunks --holdout-seeds v0037,v0038,v0039,v0040 \
        --holdout-decks control_thrawn_yellow,aggro_greef,normal_obiwan_vergence"""
import argparse, json, os, sys
import numpy as np
from sklearn.experimental import enable_hist_gradient_boosting  # noqa: F401  (sklearn 0.23 needs this import)
from sklearn.ensemble import HistGradientBoostingClassifier
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from value_train import load_chunks, split_masks, metrics

ap = argparse.ArgumentParser()
ap.add_argument('--data', required=True); ap.add_argument('--holdout-seeds', required=True)
ap.add_argument('--holdout-decks', required=True); ap.add_argument('--max-train', type=int, default=2000000)
a = ap.parse_args()
man, games, chunks = load_chunks(a.data)
hs, hd = set(a.holdout_seeds.split(',')), set(a.holdout_decks.split(','))
Xtr, ytr, Xg, yg = [], [], [], []
for X, M in chunks:
    tr, gate, _ = split_masks(M, games, hs, hd)
    Xtr.append(np.asarray(X[tr])); ytr.append(np.asarray(M[tr, 3]))
    Xg.append(np.asarray(X[gate])); yg.append(np.asarray(M[gate, 3]))
Xtr, ytr, Xg, yg = map(np.concatenate, (Xtr, ytr, Xg, yg))
if len(Xtr) > a.max_train:
    keep = np.random.RandomState(1).choice(len(Xtr), a.max_train, replace=False)
    Xtr, ytr = Xtr[keep], ytr[keep]
clf = HistGradientBoostingClassifier(max_iter=300, learning_rate=0.1, random_state=1).fit(Xtr, ytr)
print(json.dumps({'trees_gate': metrics(clf.predict_proba(Xg)[:, 1], yg), 'trainRows': int(len(Xtr))}))
