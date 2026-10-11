#!/usr/bin/env python3
"""BotData report — turn a recorded Arenabot corpus into the standing analysis pass.

    docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
      python3 SWUSim/DevTools/rl/botdata_report.py SWUSim/BotData > report.md

    # or against an extracted bundle, anywhere:
    python3 botdata_report.py /tmp/bundle --positions 12 > report.md

Input is a directory of <gameId>/ dirs — SWUSim/BotData itself, or an unpacked download. Same shape.
Spec: docs/superpowers/specs/2026-09-23-swusim-bot-data-loop-design.md.

⚠ THIS SCRIPT SELECTS; IT DOES NOT GRADE. It computes what is arithmetic — deltas, counts, rates — and
surfaces the decisions worth looking at. It never scores a play. A Python heuristic that judged SWU
lines would be a weaker player than the bot it is auditing, and its verdicts would launder into
findings that nobody could trace back. Reading the positions is the LLM's job; this narrows them down.

PRIMARY READER: Claude (owner decision 2026-09-23). Terse, high row counts, positions dumped in full.

Sections map to the spec's four analysis goals:
  1 hygiene    — is this corpus trustworthy at all (abandoned games, rewinds, identity, version drift)
  2 outcomes   — goal 3, loss mining: win rates and the per-round board delta split by win/loss
  3 cards      — goal 4: played vs held-while-affordable vs never played
  4 positions  — goal 1: the decisions worth judging, with their full snapshots
  5 value      — goal 2: are the value rows trainable yet
"""
import collections, glob, json, os, re, sys

DECK_LINE = re.compile(r'^\s*(\d+)\s+([A-Z0-9]{2,5}_T?\d{2,3})\s*$')
IDENTITY = ('authkey', 'userid', 'username')


class Game:
    def __init__(self, path):
        self.id = os.path.basename(path.rstrip('/'))
        self.path = path
        self.rows = []
        sp = os.path.join(path, 'states.jsonl')
        if os.path.isfile(sp):
            for line in open(sp, encoding='utf-8'):
                line = line.strip()
                if not line:
                    continue
                try:
                    self.rows.append(json.loads(line))
                except ValueError:
                    pass                      # a torn final line: skip it, do not lose the game
        self.meta = {}
        mp = os.path.join(path, 'meta.json')
        if os.path.isfile(mp) and os.path.getsize(mp) > 0:
            try:
                self.meta = json.load(open(mp, encoding='utf-8'))
            except ValueError:
                self.meta = {}
        self.value = []
        self.valueVersion = None
        vp = os.path.join(path, 'value.jsonl')
        if os.path.isfile(vp):
            for i, line in enumerate(open(vp, encoding='utf-8')):
                line = line.strip()
                if not line:
                    continue
                try:
                    r = json.loads(line)
                except ValueError:
                    continue
                if i == 0 and isinstance(r, dict):
                    self.valueVersion = r.get('featureVersion')
                elif isinstance(r, list):
                    self.value.append(r)

    @property
    def finished(self):
        return bool(self.meta.get('finished'))

    @property
    def botSeats(self):
        return [int(s) for s in (self.meta.get('botSeats') or [])] or self._inferBotSeats()

    def _inferBotSeats(self):
        # An abandoned game has no meta; the snapshots still say which seats are bots. ALL of them —
        # returning on the first would report a self-play game as human-vs-bot.
        out = set()
        for r in self.rows:
            for s in ('1', '2'):
                if r['seats'].get(s, {}).get('bot'):
                    out.add(int(s))
        return sorted(out)


def load_games(root):
    out = []
    for d in sorted(glob.glob(os.path.join(root, '*'))):
        if not os.path.isdir(d) or os.path.basename(d).startswith('.'):
            continue
        g = Game(d)
        if g.rows or g.meta:
            out.append(g)
    return out


def parse_decklist(text):
    """'3 LAW_037' lines under the Deck header -> {card: qty}.

    ⚠ SECTION-AWARE. A deckLink is 'Leader / <card> / Base / <card> / Deck / <cards>', so a naive
    line scan books the leader and the base as 1-of deck cards and the card table then reports a
    leader as an unplayed card in every single game.

    A URL or an unparseable blob yields {} rather than raising — deckLink is raw user input and may
    be a swudb link, in which case quantities are simply unavailable."""
    out = {}
    section, sawHeader = None, False
    for line in str(text or '').splitlines():
        head = line.strip().lower()
        if head in ('leader', 'base', 'deck', 'sideboard'):
            section, sawHeader = head, True
            continue
        m = DECK_LINE.match(line)
        # No headers at all: a bare list, take everything. With headers: the Deck section only.
        if m and (section == 'deck' or not sawHeader):
            out[m.group(2)] = out.get(m.group(2), 0) + int(m.group(1))
    return out


# ── 1. hygiene ───────────────────────────────────────────────────────────────────────────────
def hygiene(games):
    undo = bookmark = 0
    versions, leaks = set(), set()
    seatMix = collections.Counter()
    for g in games:
        # ⚠ Say who was actually playing. A SELF-PLAY corpus prints zeros down every human column, and
        # without this line the reader cannot tell "the human did nothing" from "there was no human".
        bots = len(g.botSeats)
        seatMix['bot-vs-bot' if bots >= 2 else ('human-vs-bot' if bots == 1 else 'no-bot-seat')] += 1
        for r in g.rows:
            k = r.get('action', {}).get('kind')
            if k == 'undo':
                undo += 1
            elif k == 'bookmark':
                bookmark += 1
        if g.valueVersion:
            versions.add(g.valueVersion)
        for fn in ('states.jsonl', 'meta.json'):
            p = os.path.join(g.path, fn)
            if not os.path.isfile(p):
                continue
            blob = open(p, encoding='utf-8', errors='replace').read().lower()
            for w in IDENTITY:
                if w in blob:
                    leaks.add(w)
    return {'games': len(games),
            'finished': sum(1 for g in games if g.finished),
            'abandoned': sum(1 for g in games if not g.finished),
            'rows': sum(len(g.rows) for g in games),
            'undo': undo, 'bookmark': bookmark,
            'featureVersions': sorted(versions),
            'seatMix': dict(seatMix),
            'identityLeaks': sorted(leaks)}


# ── 2. outcomes (goal 3) ─────────────────────────────────────────────────────────────────────
def outcomes(games):
    wins = losses = 0
    byStyle = {}
    byMatchup = {}
    rounds = []
    # ⚠ DAMAGE TAKEN per side, measured from each side's OWN starting HP — NOT an HP difference.
    # `botHP - humanHP` made a 33 HP base against a 30 HP base read "+3.0, bot ahead" at round 1,
    # before a card was played. Bases differ; damage is the comparable quantity.
    dmg = collections.defaultdict(lambda: {'bot': [], 'human': []})
    for g in games:
        if not g.finished:
            continue                            # an abandoned game has no outcome to rate
        bots = g.botSeats
        if not bots:
            continue
        bot = bots[0]
        human = 2 if bot == 1 else 1
        won = int(g.meta.get('winner', 0)) == bot
        wins += won
        losses += (not won)
        style = str(g.meta.get('botStyle', '')) or '(unknown)'
        w, l = byStyle.get(style, (0, 0))
        byStyle[style] = (w + won, l + (not won))
        mk = f"{g.meta.get('leader', {}).get(str(bot), '?')} vs {g.meta.get('leader', {}).get(str(human), '?')}"
        w, l = byMatchup.get(mk, (0, 0))
        byMatchup[mk] = (w + won, l + (not won))
        rounds.append(int(g.meta.get('rounds', 0)))
        # Starting HP is the first snapshot's — bases differ (33 vs 30 is common).
        if not g.rows:
            continue
        start = {side: int(g.rows[0]['seats'][str(seatNo)]['baseHp'])
                 for side, seatNo in (('bot', bot), ('human', human))}
        lastOfRound = {}
        for r in g.rows:
            lastOfRound[int(r.get('round', 0))] = r
        for rnd, r in lastOfRound.items():
            for side, seatNo in (('bot', bot), ('human', human)):
                dmg[rnd][side].append(start[side] - int(r['seats'][str(seatNo)]['baseHp']))
    return {'rated': wins + losses, 'botWins': wins, 'botLosses': losses,
            'byStyle': byStyle, 'byMatchup': byMatchup, 'rounds': sorted(rounds),
            'dmgByRound': {k: dict(v) for k, v in sorted(dmg.items())}}


# ── 3. card table (goal 4) ───────────────────────────────────────────────────────────────────
def _handEntry(e):
    """'LAW_037$1' -> ('LAW_037', 1). Cost is what the card costs RIGHT NOW, discounts included."""
    cid, _, cost = str(e).partition('$')
    try:
        return cid, int(cost)
    except ValueError:
        return cid, 0


def card_table(games):
    """⚠ BOT and HUMAN columns are kept APART. In a human-vs-bot corpus "the bot never plays X" and
    "the human never plays X" are opposite findings — the first is a bot defect, the second is a
    deckbuilding one. A single merged `played` column makes goal 4 unreadable."""
    blank = lambda: {'botPlayed': 0, 'humanPlayed': 0, 'inDeck': 0,
                     'botRefused': 0, 'humanRefused': 0, 'games': 0}
    rows = collections.defaultdict(blank)
    for g in games:
        seen = set()
        for s, txt in (g.meta.get('deckList') or {}).items():
            for cid, qty in parse_decklist(txt).items():
                rows[cid]['inDeck'] += qty
        # ⚠ REFUSED is counted once per ROUND, at that seat's LAST decision of it — the only moment
        # "affordable and still in hand" means the seat declined to cast it. Counting every snapshot
        # flagged a 2-drop as held in the same breath the bot spent 4 on something better, and on 5
        # real games produced "Yaddle held 38, played 2" for a bot whose round-end open resources
        # matched the human's exactly. That column was noise; this one is a decision.
        lastOfRound = {}
        for r in g.rows:
            if r.get('actor') in ('bot', 'human'):
                lastOfRound[(int(r.get('round', 0)), r['actor'])] = r
        for r in g.rows:
            who = 'bot' if r.get('actor') == 'bot' else ('human' if r.get('actor') == 'human' else None)
            if who is None:
                continue                        # system rows (undo / bookmark / final) belong to nobody
            a = r.get('action', {})
            if a.get('kind') == 'play' and a.get('card'):
                rows[a['card']][who + 'Played'] += 1
                seen.add(a['card'])
            for e in r['seats'].get(str(r.get('seat')), {}).get('hand', []):
                seen.add(_handEntry(e)[0])
        for (rnd, who), r in lastOfRound.items():
            sd = r['seats'].get(str(r.get('seat')), {})
            ready = int(sd.get('res', {}).get('ready', 0))
            for e in sd.get('hand', []):
                cid, cost = _handEntry(e)
                if cost <= ready:
                    rows[cid][who + 'Refused'] += 1
        for cid in seen:
            rows[cid]['games'] += 1
    out = [dict(r, card=cid, played=r['botPlayed'] + r['humanPlayed']) for cid, r in rows.items()]
    # Rank by the finding that matters: a card the BOT ended a round holding and could have cast.
    out.sort(key=lambda r: (-r['botRefused'], -r['botPlayed'], r['card']))
    return out


# ── 4. positions to judge (goal 1) ───────────────────────────────────────────────────────────
def select_positions(games, n=10):
    """Three computable reasons a decision is worth a human/LLM read. None of them claims the move was
    wrong — they claim the move MATTERED, which is all arithmetic can honestly say."""
    cands = []
    for g in games:
        bots = set(g.botSeats)
        for i, r in enumerate(g.rows):
            if r.get('actor') == 'system':
                continue                        # undo / bookmark markers are not decisions
            seat = str(r.get('seat'))
            if seat not in ('1', '2'):
                continue
            sd = r['seats'][seat]

            # (a) PRE-SWING: the action immediately before EITHER base takes a hit.
            #
            # ⚠ Check BOTH seats, not the acting one. Damage from an attack lands on the OPPONENT's
            # base, and snapshots are taken at action OPEN, so a swing shows up on the other seat in
            # the NEXT row. Comparing only the actor's own base meant this fired solely when a seat
            # damaged ITSELF — it produced ZERO candidates across 5 real games in which the bot lost
            # 17+ base HP.
            if i + 1 < len(g.rows):
                for dmgSeat in ('1', '2'):
                    before = int(r['seats'].get(dmgSeat, {}).get('baseHp', 0))
                    after = int(g.rows[i + 1]['seats'].get(dmgSeat, {}).get('baseHp', before))
                    drop = before - after
                    if drop > 0:
                        cands.append({'game': g.id, 'index': i, 'reason': 'pre-swing', 'magnitude': drop,
                                      'seat': int(seat), 'damagedSeat': int(dmgSeat),
                                      'actor': r.get('actor'), 'snapshot': r})

            # (b) BRANCHING: several affordable cards AND several ready units — a real choice, not a
            # forced move. Cheap one-option turns are noise.
            ready = int(sd.get('res', {}).get('ready', 0))
            afford = sum(1 for e in sd.get('hand', []) if _handEntry(e)[1] <= ready)
            readyUnits = sum(1 for u in sd.get('ground', []) + sd.get('space', []) if u.get('ready'))
            if afford >= 3 and readyUnits >= 2:
                cands.append({'game': g.id, 'index': i, 'reason': 'branching',
                              'magnitude': afford + readyUnits, 'seat': int(seat),
                              'actor': r.get('actor'), 'snapshot': r})

            # (c) HELD: a card affordable across 3+ consecutive decisions by this seat and never
            # played in the game — the bot refusing to commit something.
            if seat in bots or not bots:
                pass                            # held-cards are computed per game below

        # (c) continued — needs the whole game, so it runs once per game.
        played = {r['action']['card'] for r in g.rows
                  if r.get('action', {}).get('kind') == 'play' and r['action'].get('card')}
        streak = collections.defaultdict(int)
        firstIdx = {}
        for i, r in enumerate(g.rows):
            if r.get('actor') == 'system':
                continue
            seat = str(r.get('seat'))
            if seat not in ('1', '2'):
                continue
            sd = r['seats'][seat]
            ready = int(sd.get('res', {}).get('ready', 0))
            here = set()
            for e in sd.get('hand', []):
                cid, cost = _handEntry(e)
                if cost <= ready:
                    here.add(cid)
                    key = (seat, cid)
                    streak[key] += 1
                    firstIdx.setdefault(key, i)
                    if streak[key] == 3 and cid not in played:
                        cands.append({'game': g.id, 'index': firstIdx[key], 'reason': 'held',
                                      'magnitude': 3, 'seat': int(seat), 'card': cid,
                                      'actor': r.get('actor'), 'snapshot': g.rows[firstIdx[key]]})
            for key in [k for k in list(streak) if k[0] == seat and k[1] not in here]:
                streak[key] = 0
                firstIdx.pop(key, None)

    # Keep the strongest of each reason, interleaved, so one noisy reason cannot crowd the others out.
    byReason = collections.defaultdict(list)
    for c in cands:
        byReason[c['reason']].append(c)
    for v in byReason.values():
        v.sort(key=lambda c: -c['magnitude'])
    out, i = [], 0
    order = ['pre-swing', 'branching', 'held']
    while len(out) < n and any(len(byReason[r]) > i for r in order):
        for r in order:
            if len(byReason[r]) > i and len(out) < n:
                out.append(byReason[r][i])
        i += 1
    return out


# ── 5. value rows (goal 2) ───────────────────────────────────────────────────────────────────
def value_summary(games):
    rows = labelled = withRows = 0
    versions = set()
    for g in games:
        if not g.value:
            continue
        withRows += 1
        rows += len(g.value)
        if g.valueVersion:
            versions.add(g.valueVersion)
        if g.finished and int(g.meta.get('winner', 0)) > 0:
            labelled += len(g.value)
    return {'rows': rows, 'games': withRows, 'labelled': labelled,
            'versions': sorted(versions), 'versionConflict': len(versions) > 1}


# ── report ───────────────────────────────────────────────────────────────────────────────────
def _pct(a, b):
    return f'{(100.0 * a / b):.1f}%' if b else 'n/a'


def main(argv):
    root = argv[1] if len(argv) > 1 else 'SWUSim/BotData'
    npos = 10
    if '--positions' in argv:
        npos = int(argv[argv.index('--positions') + 1])
    games = load_games(root)
    if not games:
        print(f'No games under {root}.')
        return 1

    h = hygiene(games)
    print(f'# BotData report — {h["games"]} games, {h["rows"]} action rows\n')
    print('## 1. Corpus hygiene\n')
    print(f'- finished **{h["finished"]}** / abandoned **{h["abandoned"]}** '
          f'(an abandoned game has states but no outcome, so it is excluded from section 2)')
    print(f'- rewinds: {h["undo"]} undo, {h["bookmark"]} bookmark '
          f'(action rows before a marker describe a line that was taken back)')
    print(f'- seats: **{", ".join(f"{k} x{v}" for k, v in sorted(h["seatMix"].items())) or "unknown"}** '
          f'(bot-vs-bot means every human column below is empty BY CONSTRUCTION)')
    print(f'- value featureVersion(s): {h["featureVersions"] or "none"}')
    print(f'- identity leaks: **{h["identityLeaks"] or "none"}**\n')

    o = outcomes(games)
    print('## 2. Outcomes and the board delta (goal 3)\n')
    print(f'- rated games **{o["rated"]}** — bot {o["botWins"]}W / {o["botLosses"]}L '
          f'({_pct(o["botWins"], o["rated"])})')
    if o['rounds']:
        rs = o['rounds']
        print(f'- rounds: median {rs[len(rs) // 2]}, min {rs[0]}, max {rs[-1]}')
    for k, (w, l) in sorted(o['byStyle'].items()):
        print(f'  - style `{k}`: {w}W/{l}L ({_pct(w, w + l)})')
    for k, (w, l) in sorted(o['byMatchup'].items(), key=lambda kv: -(kv[1][0] + kv[1][1]))[:12]:
        print(f'  - `{k}`: {w}W/{l}L')
    print('\n  CUMULATIVE base damage TAKEN by each side, per round (each measured from its own\n'
          '  starting HP, so unequal bases do not read as a lead):\n')
    print('  | round | n | bot took | human took | per-round to bot | per-round to human |')
    print('  |---|---|---|---|---|---|')
    pb = ph = 0.0
    for rnd, v in o['dmgByRound'].items():
        b = sum(v['bot']) / len(v['bot']); h = sum(v['human']) / len(v['human'])
        print(f'  | {rnd} | {len(v["bot"])} | {b:.1f} | {h:.1f} | {b - pb:+.1f} | {h - ph:+.1f} |')
        pb, ph = b, h

    print('\n## 3. Cards (goal 4)\n')
    print('`refused` = ROUNDS that seat ENDED still holding the card with the resources to cast it. '
          'High **bot refused** with **bot played 0** is the bot declining to commit a card it could '
          'have cast. Bot and human columns are kept apart on purpose. `in decks` is 0 when the '
          'decklist was a URL rather than a card list — quantities are then simply unavailable.\n')
    print('| card | in decks | bot played | bot refused | human played | human refused | games |')
    print('|---|---|---|---|---|---|---|')
    for r in card_table(games)[:40]:
        print(f'| {r["card"]} | {r["inDeck"]} | {r["botPlayed"]} | {r["botRefused"]} '
              f'| {r["humanPlayed"]} | {r["humanRefused"]} | {r["games"]} |')

    pos = select_positions(games, npos)
    print(f'\n## 4. Positions to judge (goal 1) — {len(pos)}\n')
    print('Selected because each decision MATTERED, not because it was wrong. Read the snapshot and '
          'judge the move.\n')
    for p in pos:
        tag = f" card={p['card']}" if 'card' in p else ''
        dmg = f" · DAMAGED seat {p['damagedSeat']}" if 'damagedSeat' in p else ''
        print(f"### {p['reason']} · game {p['game']} · row {p['index']} · seat {p['seat']} "
              f"({p['actor']}){dmg} · magnitude {p['magnitude']}{tag}\n")
        print('```json')
        print(json.dumps(p['snapshot'], indent=1, sort_keys=True))
        print('```\n')

    v = value_summary(games)
    print('## 5. Value rows (goal 2)\n')
    print(f'- {v["rows"]} rows across {v["games"]} games; **{v["labelled"]}** joinable to an outcome')
    print(f'- versions {v["versions"] or "none"}'
          + ('  ⚠ **CONFLICT — do not train across these**' if v['versionConflict'] else ''))
    return 0


if __name__ == '__main__':
    sys.exit(main(sys.argv))
