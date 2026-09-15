#!/usr/bin/env python3
"""Sweep retro — run every ~2,000 games of a fixture sweep (sweep_fixtures.sh), INSIDE the SWUSim container:

    docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
      python3 SWUSim/DevTools/rl/retro.py /tmp/fixture_sweep > retro.md

Only games finished since the previous retro are read (state: <out>/retro_state.json). It reports:
  1. FAILURES — each failed game's kept folder is classified by where it got stuck (classify_failed.php) and
     grouped; a stall shape not seen in an earlier retro is flagged NEW. Engine failures get a failing
     SchemaTest before any fix.
  2. COMBOS no test covers — mined from the games' combo traces (SWUBOT_TRACE_MODE=combo) and logs:
       ORDER  a trigger-ordering window: its cards and trigger types together
       CROSS  a decision the NON-turn player makes mid-action: the prompt and the cards behind it
       PLOT   a Plot play inside a leader deploy: the leader and the Plotted card
     A combo is COVERED when one SchemaTest section (SWUSim/Tests/Cases/**) names all its cards. Uncovered
     combos are ranked by how often the games reached them, each with an example game, and become new cases in
     SWUSim/Tests/Cases/interactions/ — RED or GREEN, the owner verifies them in the SchemaTest editor.
Owner's process: memory "sweep-retros-write-regression-tests".
"""
import collections, glob, json, os, re, subprocess, sys

OUT = sys.argv[1] if len(sys.argv) > 1 else '/tmp/fixture_sweep'
CASES = 'SWUSim/Tests/Cases'
STATE = os.path.join(OUT, 'retro_state.json')
CARD = re.compile(r'\b(?:[A-Z]{2,4}\d{0,2}_T?\d{2,3})\b')

state = json.load(open(STATE)) if os.path.exists(STATE) else {'done': [], 'failSigs': [], 'reported': [], 'n': 0}
done = set(state['done'])

# ── the new games ────────────────────────────────────────────────────────────────────────────────────
new = []
for f in glob.glob(os.path.join(OUT, 'games', '*.tsv')):
    key = os.path.basename(f)[:-4]
    if key in done: continue
    cols = open(f).read().rstrip('\n').split('\t')
    if len(cols) < 5: continue
    r = json.loads(cols[4][9:]) if cols[4].startswith('[RESULT] ') else {'fail': 99}
    new.append((key, r, cols[5] if len(cols) > 5 else ''))
state['n'] += 1
print(f"# Sweep retro #{state['n']} — {len(new)} new games (total seen {len(done) + len(new)})\n")

# ── 1. failures ──────────────────────────────────────────────────────────────────────────────────────
failed = [(k, r, g) for k, r, g in new if r.get('fail') or r.get('failureSignal')]
ids = [g for _, _, g in failed if g.isdigit()]
cls = {}
if ids:
    res = subprocess.run(['php', '-d', 'apc.enable_cli=1', '-d', 'xdebug.mode=off', 'SWUSim/DevTools/rl/classify_failed.php'] + ids,
                         capture_output=True, text=True).stdout
    for line in res.splitlines():
        try: j = json.loads(line); cls[j['id']] = j
        except Exception: pass
groups = collections.defaultdict(list)
for k, r, g in failed:
    j = cls.get(g, {})
    h = j.get('head')
    if r.get('failureSignal') == 'timeout': sig = 'TIMEOUT (engine loop?) at ' + (f"{h[1]} {h[2]}" if h else 'no pending decision')
    elif h: sig = f"STALL at {h[1]} {h[2] or ''}" if h[1] != 'CUSTOM' else f"STALL at CUSTOM {h[3].split('|')[0]}"
    elif j.get('winner'): sig = 'FINISHED, failed only the retry check'
    else: sig = 'STALL, no pending decision, after: ' + re.sub(r'P[12]', 'P#', (j.get('log') or [''])[-1])[:70]
    groups[sig].append((k, g, j))
print(f"## 1. Failures — {len(failed)} of {len(new)} new games\n")
if not failed: print("None.\n")
for sig, rows in sorted(groups.items(), key=lambda kv: -len(kv[1])):
    flag = '' if sig in state['failSigs'] else '  **NEW**'
    k, g, j = rows[0]
    print(f"- **{len(rows)}× {sig}**{flag} — e.g. `{k}` (game {g}); last log: {(j.get('log') or [''])[-1][:110]}")
    if sig not in state['failSigs']: state['failSigs'].append(sig)
print()

# ── 2. combos ────────────────────────────────────────────────────────────────────────────────────────
combos = collections.defaultdict(lambda: {'n': 0, 'ex': None, 'cards': ()})
def add(kind, detail, cards, key, where):
    cards = tuple(sorted(set(cards)))
    if not cards: return
    sig = f"{kind} {detail}"
    c = combos[sig]; c['n'] += 1; c['cards'] = cards
    if c['ex'] is None: c['ex'] = (key, where)
for key, r, g in new:
    tp = os.path.join(OUT, 'traces', key + '.jsonl')
    if os.path.exists(tp):
        for line in open(tp):
            try: t = json.loads(line)
            except Exception: continue
            where = f"round {t.get('round')}, seat {t.get('seat')}"
            if t.get('stack'):
                # A leader's second ability rides as "ASH_017#1" — the same card for coverage purposes.
                stack = [(re.sub(r'#\d+$', '', c), re.sub(r'#\d+$', '', ty)) for c, ty, _ in t['stack']]
                parts = sorted(f"{c}:{ty}" for c, ty in stack)
                add('ORDER', ' + '.join(parts), [c for c, _ in stack], key, where)
            elif t.get('kind') == 'decision' and t.get('seat') != t.get('turn'):
                cards = CARD.findall(t.get('next', '') + ' ' + t.get('tooltip', ''))
                add('CROSS', f"{t.get('tooltip')} ← {t.get('next', '').split('|')[0]}", cards, key, where)
    lp = os.path.join(OUT, 'logs', key + '.log')
    if os.path.exists(lp):
        entries = open(lp).read().split('<NL>')
        leader = {}
        for e in entries:
            m = re.search(r'(P[12]) deployed \[\[([A-Z0-9_]+)\|', e)
            if m: leader[m.group(1)] = m.group(2)
            m = re.search(r'(P[12]) played \[\[([A-Z0-9_]+)\|[^\]]*\]\] using Plot', e)
            if m: add('PLOT', f"{leader.get(m.group(1), '?')} → {m.group(2)}", [leader.get(m.group(1), ''), m.group(2)], key, 'log')

# Coverage index: every SchemaTest section → the set of card ids it names.
sections = []
for f in glob.glob(os.path.join(CASES, '**', '*.md'), recursive=True):
    for sec in re.split(r'^[ \t]*---[ \t]*$', open(f, errors='replace').read(), flags=re.M):
        name = re.search(r'^[ \t]*#(?!#|//)[ \t]*(.+?)[ \t]*$', sec, re.M)
        sections.append((f[len(CASES) + 1:], name.group(1) if name else '?', set(CARD.findall(sec))))
def covered(cards):
    cs = {c for c in cards if c}
    for f, n, ids in sections:
        if cs <= ids: return f"{f}::{n}"
    return None

rows = []
for sig, c in combos.items():
    cov = covered(c['cards'])
    rows.append((c['n'], sig, c, cov))
unc = sorted([r for r in rows if r[3] is None], key=lambda r: -r[0])
print(f"## 2. Combos — {len(rows)} distinct shapes in the new games; {len(unc)} with no SchemaTest naming all their cards\n")
for n, sig, c, _ in unc[:40]:
    fresh = '' if sig in state['reported'] else ' **(new)**'
    print(f"- {n}× `{sig}`{fresh} — cards {', '.join(c['cards'])}; e.g. `{c['ex'][0]}` ({c['ex'][1]})")
    if sig not in state['reported']: state['reported'].append(sig)
if len(unc) > 40: print(f"- … and {len(unc) - 40} more")
print(f"\nCovered shapes (already tested): {sum(1 for r in rows if r[3])}")

state['done'] = sorted(done | {k for k, _, _ in new})
json.dump(state, open(STATE, 'w'))
