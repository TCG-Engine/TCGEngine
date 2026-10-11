# SWUSim/DevTools/rl/fidelity_pairs_test.py — fidelity_pairs.py lists the unordered fixture pairs with >= MIN real matches.
#   python3 SWUSim/DevTools/rl/fidelity_pairs_test.py
import json, subprocess, tempfile, os
HERE = os.path.dirname(os.path.abspath(__file__))
cells = {
    'a': {'b': {'matches': [6, 4, 0]}, 'c': {'matches': [2, 1, 0]}},
    'b': {'a': {'matches': [4, 6, 0]}},
    'c': {'a': {'matches': [1, 2, 0]}, 'd': {'matches': [5, 5, 1]}},
    'd': {'c': {'matches': [5, 5, 1]}},
}
with tempfile.NamedTemporaryFile('w', suffix='.json', delete=False) as f:
    json.dump({'cells': cells}, f); path = f.name
run = lambda m: [l for l in subprocess.run(['python3', os.path.join(HERE, 'fidelity_pairs.py'), path, m], capture_output=True, text=True).stdout.split('\n') if l]
pairs = run('10')
assert pairs == ['a b', 'c d'], pairs            # a-c has 3 matches: excluded; c-d has 11: included; each pair once
out3 = run('3')
assert out3 == ['a b', 'a c', 'c d'], out3       # the threshold is >=, not >
print('ALL PASS')
