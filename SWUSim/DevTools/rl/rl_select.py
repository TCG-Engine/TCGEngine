#!/usr/bin/env python3
"""RL Phase 3 — the offline selection step. Keeps only the (style, state, move) entries with at least --min-visits
visits, and only states where two or more moves qualify (an override needs the fallback's move AND an alternative
measured). The play-time significance rule (SWU_RL_Z, SWUSim/Rl/SwuPolicy.php) then decides each override.
    python3 SWUSim/DevTools/rl/rl_select.py /tmp/rl_run2/checkpoint.json /tmp/rl_run2/selected.json --min-visits 200
"""
import argparse, json, math, sys


def select(table, min_visits):
    out = {}
    for style, states in table.items():
        for s, moves in states.items():
            keep = {m: v for m, v in moves.items() if v[0] >= min_visits}
            if len(keep) >= 2:
                out.setdefault(style, {})[s] = keep
    return out


def significant_pairs(table, z):
    """(style, state, top move, better move, diff) where an alternative beats the most-visited move by > z SE."""
    found = []
    for style, states in table.items():
        for s, moves in states.items():
            tm, (tn, tq) = max(moves.items(), key=lambda kv: kv[1][0])
            for m, (n, q) in moves.items():
                if m == tm:
                    continue
                se = math.sqrt(max(1e-9, 1 - tq * tq) / tn + max(1e-9, 1 - q * q) / n)
                if q - tq > z * se:
                    found.append((style, s, tm, m, q - tq))
    return found


if __name__ == '__main__':
    ap = argparse.ArgumentParser()
    ap.add_argument('checkpoint'); ap.add_argument('out')
    ap.add_argument('--min-visits', type=int, default=200)
    ap.add_argument('--z', type=float, default=3.0)
    a = ap.parse_args()
    ck = json.load(open(a.checkpoint))
    if 'ab' in ck:
        # swu-v2 (rl_ab.py): keep only entries with --min-visits (effective) samples in BOTH arms; the play-time rule
        # (SWUSim/Rl/SwuPolicy.php) applies the z test to them.
        from rl_ab import ab_effect, significant_ab
        ab = {}
        for style, states in ck['ab'].items():
            for s, moves in states.items():
                for mv, e in moves.items():
                    x = ab_effect(e)
                    if x and x['nT'] >= a.min_visits and x['nC'] >= a.min_visits:
                        ab.setdefault(style, {}).setdefault(s, {})[mv] = e
        json.dump({'version': 'swu-v2', 'batches': ck.get('batches'), 'games': ck.get('games'),
                   'selected': {'min_visits': a.min_visits}, 'ab': ab}, open(a.out, 'w'), separators=(',', ':'))
        sig = significant_ab(ab, a.z, a.min_visits)
        for style in sorted(ab):
            n = sum(1 for p in sig if p[0] == style)
            print(f'{style:8} (state, move) entries kept {sum(len(m) for m in ab[style].values()):6}  significant (z={a.z}): {n}')
        sys.exit(0)
    sel = select(ck['table'], a.min_visits)
    json.dump({'version': ck.get('version', 'swu-v1'), 'batches': ck.get('batches'), 'games': ck.get('games'),
               'selected': {'min_visits': a.min_visits}, 'table': sel}, open(a.out, 'w'), separators=(',', ':'))
    pairs = significant_pairs(sel, a.z)
    for style in sorted(sel):
        n = sum(1 for p in pairs if p[0] == style)
        print(f'{style:8} states kept {len(sel[style]):5}  significant (z={a.z}) vs the top move: {n}')
