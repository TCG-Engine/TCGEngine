"""Real-world matchup matrix — the targets the bots work towards — from melee events imported into SWUStats.
usage: real_matchup_matrix.py <meta_export.json> <out prefix> [<fixture target json>]
  <meta_export.json>  output of SWUSim/DevTools/rl/melee_archetype_export.php
  <out>.json          EVERY archetype pair (large; keep out of the repo)
  <fixture target>    the fixture-pair cells only, keyed by fixture FILENAME, for bot sweeps to compare against
Prints health checks and field coverage.
Each match is stored twice (once per player); a cell A-vs-B is read ONLY from A's rows, and the B-vs-A cell from
B's rows — the two are checked against each other."""
import sys, json, math, collections

j = json.load(open(sys.argv[1])); out = sys.argv[2]
decks = {int(k): v for k, v in j['decks'].items()}; labels = j['labels']; fixtures = j['fixtures']

def wilson(k, n, z=1.96):
    if n == 0: return (0.0, 1.0)
    p = k / n; d = 1 + z * z / n; c = p + z * z / (2 * n); h = z * math.sqrt(p * (1 - p) / n + z * z / (4 * n * n))
    return ((c - h) / d, (c + h) / d)

cell = collections.defaultdict(lambda: {'mw': 0, 'ml': 0, 'md': 0, 'gw': 0, 'gl': 0, 'gd': 0})
health = collections.Counter(); events = set(); field = collections.Counter()
for d in decks.values():
    events.add(d['t'])
    if d['k']: field[d['k']] += 1
    else: health['deck without leader/base'] += 1
for p, o, w, l, dr in j['matchups']:
    if o is None: health['row with no opponent (bye / unmatched)'] += 1; continue
    a, b = decks.get(p, {}).get('k'), decks.get(o, {}).get('k')
    if not a or not b: health['row with an unknown archetype'] += 1; continue
    if a == b: health['mirror row'] += 1; continue
    if w == 0 and l == 0 and dr == 0: health['row with no games (0-0-0)'] += 1; continue
    c = cell[(a, b)]; c['gw'] += w; c['gl'] += l; c['gd'] += dr
    if w > l: c['mw'] += 1
    elif l > w: c['ml'] += 1
    else: c['md'] += 1
    health['usable row'] += 1

# symmetry: A's view of A-B must mirror B's view of B-A
asym = [(a, b) for (a, b), c in cell.items() if (c['mw'], c['ml'], c['gw'], c['gl']) != (cell[(b, a)]['ml'], cell[(b, a)]['mw'], cell[(b, a)]['gl'], cell[(b, a)]['gw'])]
print(f'events {len(events)} | decks {len(decks)} | archetypes {len(field)}')
for k, v in health.most_common(): print(f'  {k}: {v}')
print(f'  asymmetric cells (A-view ≠ mirror of B-view): {len(asym)} of {len(cell)}')

rows = []
for (a, b), c in cell.items():
    n = c['mw'] + c['ml'] + c['md']; g = c['gw'] + c['gl']
    lo, hi = wilson(c['gw'], g)
    rows.append({'a': a, 'b': b, 'aLabel': labels.get(a), 'bLabel': labels.get(b), **c, 'matches': n,
                 'matchWinPct': (c['mw'] + 0.5 * c['md']) / n if n else None,
                 'gameWinPct': c['gw'] / g if g else None, 'gameWinCI95': [lo, hi]})
json.dump({'events': sorted(events), 'field': {k: {'decks': v, 'label': labels.get(k)} for k, v in field.most_common()},
           'cells': rows, 'fixtures': fixtures}, open(out + '.json', 'w'), indent=1)

# ── the fixtures (every file in ash-meta-2026-09) ──
fx = sorted(fixtures.items(), key=lambda kv: -field.get(kv[1], 0))
print(f'\nFIXTURE ARCHETYPES in the field (of {sum(field.values())} decks):')
for f, k in fx: print(f'  {field.get(k, 0):4}  {labels.get(k)}')
covered = [(a, b) for _, a in fx for _, b in fx if a != b]
have = [(a, b) for a, b in covered if (a, b) in cell]
n10 = [(a, b) for a, b in have if cell[(a, b)]['mw'] + cell[(a, b)]['ml'] + cell[(a, b)]['md'] >= 10]
print(f'\nfixture-pair cells: {len(covered)} ordered | with any real match {len(have)} | with >=10 matches {len(n10)}')

# ── fixture target file: only the fixture pairs, keyed by filename ──
if len(sys.argv) > 3:
    byk = {}
    for f, k in fixtures.items(): byk.setdefault(k, []).append(f)
    tgt = {}
    for f1, a in fixtures.items():
        for f2, b in fixtures.items():
            if a == b or (a, b) not in cell: continue
            c = next(r for r in rows if r['a'] == a and r['b'] == b)
            tgt.setdefault(f1, {})[f2] = {'matches': [c['mw'], c['ml'], c['md']], 'games': [c['gw'], c['gl'], c['gd']],
                'gameWinPct': round(c['gameWinPct'], 4) if c['gameWinPct'] is not None else None,
                'gameWinCI95': [round(x, 4) for x in c['gameWinCI95']], 'matchWinPct': round(c['matchWinPct'], 4),
                'thin': c['matches'] < 10}
    tot = collections.defaultdict(lambda: [0, 0])
    for r in rows: tot[r['a']][0] += r['gw']; tot[r['a']][1] += r['gl']
    json.dump({'source': {'events': len(events), 'meleeIds': sorted(events), 'decks': len(decks),
                          'note': 'Premier, ASH set, after the 2026-08-31 Cad Bane (ASH) suspension, >32 players, '
                                  'per swu-competitivehub.com; Redlands 449449 and Malmo 467453 excluded (owner). '
                                  'Cells count Swiss AND top cut; mirrors and byes excluded. thin = fewer than 10 matches.'},
               'archetypes': {f: {'key': k, 'label': labels.get(k), 'fieldDecks': field.get(k, 0),
                                  'gameWinPct': round(tot[k][0] / max(1, sum(tot[k])), 4)} for f, k in sorted(fixtures.items())},
               'cells': tgt}, open(sys.argv[3], 'w'), indent=1, sort_keys=True)
    print(f'fixture target written: {sys.argv[3]} ({sum(len(v) for v in tgt.values())} cells)')
