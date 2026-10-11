"""BotData report (botdata_report.py) — every number it prints, against a SYNTHETIC corpus whose
answers are known by construction. No dependency on a recorded game.
    docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 python3 SWUSim/DevTools/rl/botdata_report_test.py
"""
import json, os, shutil, sys, tempfile

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from botdata_report import (load_games, hygiene, outcomes, card_table, select_positions,
                            value_summary, parse_decklist)

fails = 0
def check(ok, msg):
    global fails
    print(('PASS: ' if ok else 'FAIL: ') + msg)
    if not ok:
        fails += 1

# ── the synthetic corpus ─────────────────────────────────────────────────────────────────────
def unit(cid, p=2, hp=3, ready=True):
    return {'id': cid, 'p': p, 'hp': hp, 'dmg': 0, 'ready': ready, 'sentinel': False,
            'shields': 0, 'exp': 0, 'upgrades': [], 'effects': [], 'leader': False}

def seat(bot, base_hp, hand, ground, res_ready, res_total=None):
    return {'bot': bot, 'base': 'ASH_019', 'baseHp': base_hp,
            'leader': {'id': 'ASH_009', 'deployed': False, 'ready': True},
            'res': {'total': res_total if res_total is not None else res_ready, 'ready': res_ready},
            'hand': hand, 'ground': ground, 'space': [], 'discard': [], 'deck': 40}

def row(rnd, actor_seat, actor, kind, card, s1, s2):
    return {'round': rnd, 'phase': 'MAIN', 'turn': actor_seat, 'init': 'P1_UNCLAIMED',
            'seat': actor_seat, 'actor': actor,
            'action': {'kind': kind, 'card': card, 'mz': 'myHand-0'},
            'seats': {'1': s1, '2': s2}}

DECK2 = "Leader\n1 ASH_009\nBase\n1 ASH_019\nDeck\n3 LAW_037\n2 ASH_248\n1 LOF_093\n"

root = tempfile.mkdtemp(prefix='botdata_report_test_')
try:
    # GAME A — finished, seat 2 is the bot and LOSES. Seat 2's base falls 30 -> 22 between the
    # third and fourth rows, so the biggest swing against the bot is the action at row 3.
    a = os.path.join(root, '1001'); os.makedirs(a)
    rows_a = [
        # r1: bot plays. It holds LOF_093 ($2) affordable from here on and never plays it.
        row(1, 2, 'bot', 'play', 'ASH_248',
            seat(False, 30, ['LAW_037$1'], [], 3),
            seat(True, 30, ['LOF_093$2', 'ASH_248$1'], [], 3)),
        # r2: the human plays.
        row(2, 1, 'human', 'play', 'LAW_037',
            seat(False, 30, ['LAW_037$1'], [], 4),
            seat(True, 30, ['LOF_093$2'], [unit('ASH_248')], 4)),
        # r2: the bot's turn with REAL branching — 3 affordable cards and 2 ready units.
        row(2, 2, 'bot', 'attack', '',
            seat(False, 30, [], [unit('LAW_037')], 5),
            seat(True, 30, ['LOF_093$2', 'ASH_248$1', 'LAW_037$1'],
                 [unit('ASH_248'), unit('LOF_093')], 5)),
        # r3: ⚠ THE PRE-SWING ROW. The HUMAN acts and the BOT's base falls 8 immediately after. This is
        # the realistic shape — damage from an attack lands on the OTHER seat's base, and snapshots are
        # taken at action OPEN so it shows up in the next row. An earlier version of this fixture had
        # the bot acting here, which accidentally matched a selector that only compared the ACTING
        # seat's own base and so hid the bug on 5 real games.
        row(2, 1, 'human', 'attack', '',
            seat(False, 30, [], [unit('LAW_037')], 5),
            seat(True, 30, ['LOF_093$2'], [unit('ASH_248')], 5)),
        # r4: the bot's base has taken 8.
        row(3, 2, 'bot', 'play', 'ASH_248',
            seat(False, 30, [], [unit('LAW_037')], 5),
            seat(True, 22, ['LOF_093$2'], [unit('ASH_248')], 5)),
    ]
    with open(os.path.join(a, 'states.jsonl'), 'w') as f:
        for r in rows_a:
            f.write(json.dumps(r) + '\n')
    json.dump({'finished': True, 'rootName': 'SWUSim', 'rounds': 3, 'winner': 1,
               'botSeats': [2], 'botStyle': 'heuristic-softaggro', 'cardPool': 'premier',
               'leader': {'1': 'HMW_008', '2': 'ASH_009'}, 'base': {'1': 'HMW_021', '2': 'ASH_019'},
               'baseHpLeft': {'1': 30, '2': 22}, 'deckRemaining': {'1': [], '2': ['LAW_037']},
               'deckList': {'1': '', '2': DECK2}, 'gameLog': ['PLAY|ALL|x']},
              open(os.path.join(a, 'meta.json'), 'w'))
    with open(os.path.join(a, 'value.jsonl'), 'w') as f:
        f.write(json.dumps({'h': 1, 'names': ['round', 'my_hp_left'], 'featureVersion': 'abc123'}) + '\n')
        f.write(json.dumps([1, 1.0, 30.0]) + '\n')
        f.write(json.dumps([2, 1.0, 30.0]) + '\n')

    # GAME B — ABANDONED (no meta.json) and it carries an undo marker.
    b = os.path.join(root, '1002'); os.makedirs(b)
    with open(os.path.join(b, 'states.jsonl'), 'w') as f:
        f.write(json.dumps(row(1, 2, 'bot', 'play', 'LAW_037',
                               seat(False, 30, [], [], 2), seat(True, 30, ['LAW_037$1'], [], 2))) + '\n')
        f.write(json.dumps(row(1, 2, 'system', 'undo', '',
                               seat(False, 30, [], [], 2), seat(True, 30, ['LAW_037$1'], [], 2))) + '\n')

    games = load_games(root)
    check(len(games) == 2, f'loads both games; got {len(games)}')

    # ── 1. hygiene ───────────────────────────────────────────────────────────────────────────
    h = hygiene(games)
    check(h['games'] == 2 and h['finished'] == 1 and h['abandoned'] == 1,
          f"counts finished vs abandoned; got {h['games']}/{h['finished']}/{h['abandoned']}")
    check(h['undo'] == 1 and h['bookmark'] == 0, f"counts rewind markers; got {h}")
    check(h['featureVersions'] == ['abc123'], f"reports the value featureVersion set; got {h['featureVersions']}")
    check(h['identityLeaks'] == [], f'scans for identity leaks; got {h["identityLeaks"]}')
    # Without this, a SELF-PLAY corpus prints zeros in every human column and the reader is left to
    # guess whether the human did nothing or there was no human.
    check(h['seatMix'] == {'human-vs-bot': 2},
          f'names who was actually playing; got {h["seatMix"]}')

    # ── 2. outcomes ──────────────────────────────────────────────────────────────────────────
    o = outcomes(games)
    check(o['rated'] == 1, f'rates only FINISHED games; got {o["rated"]}')
    check(o['botWins'] == 0 and o['botLosses'] == 1, f'the bot lost its one game; got {o}')
    check(o['byStyle']['heuristic-softaggro'] == (0, 1), f'splits by bot style; got {o["byStyle"]}')
    # ⚠ DAMAGE TAKEN, not an HP difference. Reporting `botHP - humanHP` made a 33 HP base against a
    # 30 HP base read as "+3.0, the bot is ahead" at round 1, before anything had happened. Each side's
    # damage is measured from its OWN starting HP, so round 1 is 0/0 by construction.
    check(o['dmgByRound'][1] == {'bot': [0], 'human': [0]},
          f'round 1 is zero damage both sides; got {o["dmgByRound"].get(1)}')
    check(o['dmgByRound'][3] == {'bot': [8], 'human': [0]},
          f'round 3: the bot has taken 8, the human 0; got {o["dmgByRound"].get(3)}')

    # ── 3. card table ────────────────────────────────────────────────────────────────────────
    check(parse_decklist(DECK2) == {'LAW_037': 3, 'ASH_248': 2, 'LOF_093': 1},
          f'the DECK section only — the leader and base are not 1-of deck cards; got {parse_decklist(DECK2)}')
    check(parse_decklist('3 LAW_037\n2 ASH_248\n') == {'LAW_037': 3, 'ASH_248': 2},
          'a bare list with no section headers is taken whole')
    check(parse_decklist('https://swudb.com/deck/abc') == {}, 'a URL decklist parses to nothing, not a crash')
    ct = {r['card']: r for r in card_table(games)}
    check(ct['ASH_248']['botPlayed'] == 2, f"counts bot plays; got {ct['ASH_248']['botPlayed']}")
    # ⚠ "Refused" is measured at the seat's LAST decision of a round, not at every snapshot. Counting
    # every snapshot flagged a 2-drop as held in the same breath the bot spent 4 resources on something
    # better: on 5 real games it reported Yaddle "held 38, played 2" while the bot's round-end open
    # resources were statistically identical to the human's. The old column was noise.
    check(ct['LOF_093']['botPlayed'] == 0 and ct['LOF_093']['botRefused'] >= 1,
          f"LOF_093 was affordable at a round end and never played; got {ct.get('LOF_093')}")
    check(ct['LOF_093']['botRefused'] <= 3,
          f'refusals are counted per ROUND, not per snapshot; got {ct["LOF_093"]["botRefused"]}')
    check(ct['ASH_248']['inDeck'] == 2, f"carries the decklist quantity; got {ct['ASH_248']['inDeck']}")
    # LAW_037 is played ONCE by the human (game A) and ONCE by the bot (game B). Merging those into a
    # single "played: 2" would hide that the bot casts it and the human casts it for different reasons.
    check(ct['LAW_037']['humanPlayed'] == 1 and ct['LAW_037']['botPlayed'] == 1,
          f"bot and human plays are counted APART; got {ct['LAW_037']}")
    check(ct['ASH_009']['inDeck'] == 0 if 'ASH_009' in ct else True,
          'the LEADER is never booked as a deck card')

    # ── 4. positions to judge ────────────────────────────────────────────────────────────────
    pos = select_positions(games, 5)
    check(len(pos) > 0, 'selects at least one position')
    swings = [p for p in pos if p['reason'] == 'pre-swing']
    check(len(swings) == 1, f'exactly one pre-swing position in this corpus; got {len(swings)}')
    check(len(swings) == 1 and swings[0]['game'] == '1001' and swings[0]['index'] == 3,
          f"it is the action immediately BEFORE the 8-point swing; got {swings[0].get('index') if swings else None}")
    check(len(swings) == 1 and swings[0]['magnitude'] == 8,
          f'and it records the size of the swing; got {swings[0].get("magnitude") if swings else None}')
    # The seat that TOOK the damage is not the seat that acted — that distinction is the whole point.
    check(len(swings) == 1 and swings[0]['damagedSeat'] == 2 and swings[0]['seat'] == 1,
          f'it names the damaged seat AND the acting seat; got {swings[0] if not swings else (swings[0].get("damagedSeat"), swings[0].get("seat"))}')
    check('snapshot' in swings[0] and swings[0]['snapshot']['seats']['2']['baseHp'] == 30,
          'each position carries its FULL snapshot, so it can be judged offline')
    branch = [p for p in pos if p['reason'] == 'branching']
    check(any(p['game'] == '1001' for p in branch), f'and flags the high-branching decision; got {branch}')
    check(all(p['snapshot']['actor'] != 'system' for p in pos),
          'system rows (undo / bookmark markers) are never offered as decisions to judge')

    # ── 5. value rows ────────────────────────────────────────────────────────────────────────
    v = value_summary(games)
    check(v['rows'] == 2 and v['games'] == 1, f'counts value rows and the games with them; got {v}')
    check(v['labelled'] == 2, f'rows joinable to an outcome are trainable; got {v["labelled"]}')
    check(v['versionConflict'] is False, 'a single featureVersion is not a conflict')
finally:
    shutil.rmtree(root, ignore_errors=True)

print('\nALL PASS' if fails == 0 else f'\n{fails} FAILED')
sys.exit(0 if fails == 0 else 1)
