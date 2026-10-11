#!/usr/bin/env python3
"""Pack per-game position logs (value_collect.sh) into binary chunks (spec 2026-09-19 §6).
    python3 SWUSim/DevTools/rl/value_pack.py --in /tmp/value_run1/pos --out /tmp/value_run1/chunks
Rows: label = 1 if the row's seat won. Games that were capped, failed (no winner) or have no result line are dropped
and counted. Every file must carry the same names + featureVersion, or packing stops."""
import argparse, json, os, sys
import numpy as np


def pack(indir, outdir, chunk_rows=500000):
    os.makedirs(outdir, exist_ok=True)
    names = ver = None
    games, X, M = [], [], []
    dropped = {'capped': 0, 'failed': 0, 'noResult': 0}
    chunk = 0; rows = 0

    def flush():
        nonlocal chunk, X, M
        if not X:
            return
        np.save(os.path.join(outdir, f'chunk_{chunk:04d}_X.npy'), np.asarray(X, np.float32))
        np.save(os.path.join(outdir, f'chunk_{chunk:04d}_M.npy'), np.asarray(M, np.int32))
        chunk += 1; X, M = [], []

    for fn in sorted(os.listdir(indir)):
        if not fn.endswith('.jsonl'):
            continue
        lines = open(os.path.join(indir, fn)).read().splitlines()
        if not lines:
            dropped['noResult'] += 1; continue
        head, last = json.loads(lines[0]), json.loads(lines[-1])
        if 'result' not in last:
            dropped['noResult'] += 1; continue
        if names is None:
            names, ver = head['names'], head['featureVersion']
        elif head['names'] != names or head['featureVersion'] != ver:
            sys.exit(f'[pack] {fn}: feature names/version differ from the first file — mixed collections')
        res = last['result']
        if not res or res.get('winner') not in (1, 2):
            dropped['noResult' if not res else 'failed'] += 1; continue
        if res.get('capped'):
            dropped['capped'] += 1; continue
        g = len(games)
        games.append({k: last[k] for k in ('deckA', 'deckB', 'styleA', 'styleB', 'seed')})
        for line in lines[1:-1]:
            r = json.loads(line)
            seat = int(r[0]); f = r[1:]
            X.append(f); M.append([g, seat, int(f[0]), 1 if res['winner'] == seat else 0])
            rows += 1
            if len(X) >= chunk_rows:
                flush()
    flush()
    json.dump(games, open(os.path.join(outdir, 'games.json'), 'w'))
    man = {'names': names, 'featureVersion': ver, 'rows': rows, 'chunks': chunk, 'games': len(games), 'dropped': dropped}
    json.dump(man, open(os.path.join(outdir, 'manifest.json'), 'w'), indent=1)
    return man


if __name__ == '__main__':
    ap = argparse.ArgumentParser()
    ap.add_argument('--in', dest='indir', required=True)
    ap.add_argument('--out', required=True)
    ap.add_argument('--chunk-rows', type=int, default=500000)
    a = ap.parse_args()
    print(json.dumps(pack(a.indir, a.out, a.chunk_rows)))
