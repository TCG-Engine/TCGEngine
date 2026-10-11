"""How a Director Krennic (LAW) player actually plays — measured the SAME way from human Karabast logs and from SWUSim
bot game logs, so a human demonstration and the bot's own games can be compared side by side.

usage: krennic_play_profile.py <karabast dir> <human player name> <sweep logs dir> <bot krennic fixture> [opp ...]

Karabast logs: one plain-text game per file ("NininTCG plays Lepi Lookout", "Round: 3 - Action Phase").
SWUSim logs: the sweep's logs/<deckA>.<deckB>.<seed>.log ("<NL>"-joined; P1 = deckA). Only games where the Krennic
fixture is one of the two decks are read; with opponents given, only those pairings.
Measures, for the Krennic player: Krennic Action sacrifices (did the unit already attack this round? does it pay back
on death?), what Credits bought, when Krennic deployed and what happened earlier that round, initiative claimed
before vs after acting, when resourcing stopped, attack targets, Chimaera's friendly target."""
import sys, os, re, glob, collections, statistics

KAR, HUMAN, LOGS, FX = sys.argv[1:5]
OPPS = set(sys.argv[5:])
# Units whose death pays back (draw / resource itself / heal / replacement) — name and SET_NNN.
PAYBACK = {'Ant Droid', 'Expendable Mercenary', 'Nightsister Warrior', 'Onyx Squadron Brute', 'Imperial Door Technician',
           'LAW_159', 'LOF_059', 'JTL_033', 'LAW_097'}
SWEEPS = {'Single Reactor Ignition', 'Hyperspace Disaster', 'Pre Vizsla', 'Lawbringer', 'Chimaera', 'Bo-Katan Kryze',
          'LAW_044', 'SEC_078', 'ASH_053', 'LAW_101', 'ASH_052', 'SEC_051'}

def new_game(): return {'sacs': [], 'credits': [], 'deploy': None, 'claims': [], 'resourced': {}, 'attacks': [],
                        'chimaera_friendly': [], 'rounds': 0, 'won': None}

def karabast(path, me):
    g = new_game(); rnd = 0; acted = False; attacked = set(); played = set(); sweep_kills = 0; phase = ''
    lines = open(path).read().splitlines()
    for i, l in enumerate(lines):
        m = re.match(r'Round: (\d+) - (\w+) Phase', l)
        if m:
            rnd, phase = int(m.group(1)), m.group(2)
            if phase == 'Action': acted = False; attacked = set(); played = set(); sweep_kills = 0
            g['rounds'] = rnd; continue
        if not l.startswith(me + ' ') and not l.startswith(me + "'s "):
            # an enemy unit defeated by me, after one of my sweeps this round
            if re.match(r".*'s .* is defeated by " + re.escape(me), l) and not l.startswith(me): sweep_kills += 1
            continue
        if phase == 'Regroup' and 'resourced' in l:
            g['resourced'][rnd] = 0 if 'not resourced' in l else 1
        if ' attacks ' in l:
            m = re.match(re.escape(me) + r" attacks (.*?) with (.*)$", l)
            if m: g['attacks'].append('base' if m.group(1).endswith("base") else 'unit'); attacked.add(m.group(2)); acted = True
        if ' claims initiative' in l: g['claims'].append((rnd, acted))
        if re.search(r' plays | uses .* to deploy', l) and 'claims' not in l: acted = True
        m = re.match(re.escape(me) + r' plays (.*?)(,| with |$)', l)
        if m: played.add(m.group(1))
        m = re.match(re.escape(me) + r" uses Director Krennic, exhausting Director Krennic and defeating (.*?) to create a Credit", l)
        if m:
            u = m.group(1); g['sacs'].append({'round': rnd, 'unit': u, 'attacked': u in attacked, 'payback': u in PAYBACK, 'fresh': u in played})
        m = re.match(re.escape(me) + r" defeats (\d+) Credit tokens? to pay \d+ resources? less for (.*)$", l)
        if m: g['credits'].append((rnd, m.group(2), int(m.group(1))))
        if re.match(re.escape(me) + r" uses Director Krennic to deploy", l) and g['deploy'] is None:
            nxt = lines[i + 1] if i + 1 < len(lines) else ''
            g['deploy'] = {'round': rnd, 'kills_before': sweep_kills,
                           'deploy_kills': bool(re.search(r'deal \d+ damage', nxt)) and ' is defeated by ' + me in (lines[i + 2] if i + 2 < len(lines) else '')}
        m = re.match(re.escape(me) + r" uses Chimaera to defeat (.*?) and (.*)$", l)
        if m: g['chimaera_friendly'].append(m.group(1))
        if re.match(re.escape(me) + r' has won the game', l): g['won'] = True
    if g['won'] is None: g['won'] = False
    return g

def name(tok): m = re.match(r'\[\[([A-Z0-9_]+)\|([^\]]+)\]\]', tok); return (m.group(1), m.group(2)) if m else (tok, tok)

def swusim(path, seat, winner):
    g = new_game(); rnd = 1; acted = False; attacked = set(); played = set(); kills = 0
    P = f'P{seat}'
    entries = [re.sub(r'^\w+\|ALL\|@[0-9.]+\|', '', e) for e in open(path).read().strip().split('<NL>') if '|ALL|' in e]
    for i, e in enumerate(entries):
        m = re.search(r'=== Round (\d+) ===', e)
        if m: rnd = int(m.group(1)); acted = False; attacked = set(); played = set(); kills = 0; g['rounds'] = rnd; continue
        if e.startswith(f'{P} resourced a card'): g['resourced'][rnd] = 1
        if e.startswith(f'{P} took the initiative'): g['claims'].append((rnd, acted))
        m = re.match(re.escape(P) + r"'s (\[\[[^\]]+\]\]) attacked (P\d)'s (base|\[\[)", e)
        if m: g['attacks'].append('base' if m.group(3) == 'base' else 'unit'); attacked.add(name(m.group(1))[0]); acted = True
        if e.startswith(f'{P} played ') or e.startswith(f'{P} deployed ') or e.startswith(f'{P} used '): acted = True
        m = re.match(re.escape(P) + r' played (\[\[[^\]]+\]\])', e)
        if m: played.add(name(m.group(1))[0])
        m = re.match(re.escape(P) + r"'s \[\[LAW_008\|[^\]]+\]\] defeated " + re.escape(P) + r"'s (\[\[[^\]]+\]\])", e)
        if m and i > 0 and entries[i - 1].startswith(f"{P} used [[LAW_008"):
            cid, nm = name(m.group(1)); g['sacs'].append({'round': rnd, 'unit': nm, 'attacked': cid in attacked, 'payback': cid in PAYBACK, 'fresh': cid in played})
        m = re.match(re.escape(P) + r" defeated (\d+) Credit tokens? to pay \d+ less", e)
        if m:
            nxt = next((x for x in entries[i + 1:i + 3] if x.startswith(f'{P} played ') or x.startswith(f'{P} deployed ')), '')
            g['credits'].append((rnd, name(nxt.split(' ', 2)[-1])[1] if nxt else '?', int(m.group(1))))
        # an enemy unit I defeated this round (combat or effect)
        if re.match(r'.*' + re.escape(P) + r"'s .* defeated P\d's", e) and not re.search(re.escape(P) + r"'s \[\[[^\]]+\]\] defeated " + re.escape(P), e): kills += 1
        if e.startswith(f'{P} deployed [[LAW_008') and g['deploy'] is None:
            g['deploy'] = {'round': rnd, 'kills_before': kills, 'deploy_kills': False}
        m = re.match(re.escape(P) + r"'s \[\[ASH_052\|[^\]]+\]\] defeated " + re.escape(P) + r"'s (\[\[[^\]]+\]\])", e)
        if m: g['chimaera_friendly'].append(name(m.group(1))[1])
    g['won'] = winner == seat
    return g

def summarise(label, games):
    n = len(games)
    if not n: print(f'{label}: no games'); return
    sacs = [s for g in games for s in g['sacs']]
    att = [a for g in games for a in g['attacks']]
    claims = [c for g in games for c in g['claims']]
    deps = [g['deploy'] for g in games if g['deploy']]
    stop = [max([r for r, v in g['resourced'].items() if v] or [0]) for g in games]
    cred = collections.Counter(c[1] for g in games for c in g['credits'])
    chim = collections.Counter(u for g in games for u in g['chimaera_friendly'])
    pct = lambda k, t: f'{k}/{t} ({k / t:.0%})' if t else '-'
    print(f'\n== {label}: {n} games, won {sum(g["won"] for g in games)} ({sum(g["won"] for g in games) / n:.0%}), median length {statistics.median(g["rounds"] for g in games)} rounds')
    print(f'  Krennic sacrifices: {len(sacs) / n:.1f}/game | already attacked this round {pct(sum(s["attacked"] for s in sacs), len(sacs))} | '
          f'death payoff {pct(sum(s["payback"] for s in sacs), len(sacs))} | neither {pct(sum(not s["attacked"] and not s["payback"] for s in sacs), len(sacs))}')
    nei = [s for s in sacs if not s['attacked'] and not s['payback']]
    print(f'    of "neither": played that same round {pct(sum(s["fresh"] for s in nei), len(nei))} | an older unit that skipped its attack {pct(sum(not s["fresh"] for s in nei), len(nei))}')
    print(f'    any sacrifice: an OLDER unit that had not attacked this round {pct(sum(not s["fresh"] and not s["attacked"] for s in sacs), len(sacs))} | played that round {pct(sum(s["fresh"] for s in sacs), len(sacs))}')
    print(f'    by round: {dict(sorted(collections.Counter(s["round"] for s in sacs).items()))} | units: {collections.Counter(s["unit"] for s in sacs).most_common(6)}')
    print(f'  Credits spent on: {cred.most_common(8)}')
    print(f'  Krennic deployed in {pct(len(deps), n)} games, round median {statistics.median(d["round"] for d in deps) if deps else "-"}; '
          f'after >=1 enemy unit I defeated earlier that round {pct(sum(d["kills_before"] > 0 for d in deps), len(deps))}')
    print(f'  initiative claims {len(claims) / n:.1f}/game; claimed BEFORE acting that round {pct(sum(not a for r, a in claims), len(claims))}')
    print(f'  last round with a card resourced: median {statistics.median(stop)} (game median length {statistics.median(g["rounds"] for g in games)})')
    print(f'  attacks: {len(att) / n:.1f}/game, at base {pct(att.count("base"), len(att))}')
    if chim: print(f'  Chimaera friendly target: {chim.most_common(6)}')

human = [karabast(p, HUMAN) for p in sorted(glob.glob(os.path.join(KAR, '*.txt')))]
summarise(f'HUMAN {HUMAN} (Karabast)', human)
bot = []
res = {}
rpath = os.path.join(os.path.dirname(LOGS.rstrip('/')), 'results.tsv')
import json
for line in open(rpath):
    a, b, s, m, r, gid = line.rstrip('\n').split('\t')
    if m.startswith('SWUBOT_METRICS '): res[(a, b, s)] = json.loads(m.split(' ', 1)[1])['winner']
for path in sorted(glob.glob(os.path.join(LOGS, '*.log'))):
    a, b, s = os.path.basename(path)[:-4].split('.')
    if FX not in (a, b): continue
    opp = b if a == FX else a
    if OPPS and opp not in OPPS: continue
    bot.append(swusim(path, 1 if a == FX else 2, res.get((a, b, s), 0)))
summarise(f'BOT {FX}' + (f' vs {sorted(OPPS)}' if OPPS else ' vs all'), bot)
