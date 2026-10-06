#!/usr/bin/env python3
"""Unordered fixture pairs with real tournament data, for a fidelity SCREEN sweep (sweep_fixtures.sh PAIRS_FILE=).
usage: fidelity_pairs.py <real_matchups.json> [min real matches = 10]
A pair qualifies when either direction's cell has at least MIN matches (W+L+D). One line per pair, 'a b' with a < b."""
import sys, json
real = json.load(open(sys.argv[1])); MIN = int(sys.argv[2]) if len(sys.argv) > 2 else 10
out = set()
for a, row in real['cells'].items():
    for b, c in row.items():
        if a != b and sum(c.get('matches', [0, 0, 0])) >= MIN: out.add(tuple(sorted((a, b))))
for a, b in sorted(out): print(a, b)
