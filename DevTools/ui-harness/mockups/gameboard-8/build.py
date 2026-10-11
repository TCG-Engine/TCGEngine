#!/usr/bin/env python3
"""
build.py — emits index.html: eight desktop gameboard STRUCTURES for SWUSim.

⚠ MOCKUP GENERATOR, NOT SHIPPED UI.

Why a generator and not eight hand-written files: the eight concepts must render the SAME game
state, or the comparison is worthless. That state is SWUSim/Tests/Visual/MobileLayout_FullBoard.md
— the fixture behind the screenshot this work started from — so every card id, every damage value,
every upgrade and every token below is real board data, not decoration.

    python3 build.py           # writes ./index.html

Serve it from the running SWUSim container:
    http://localhost:3400/TCGEngine/DevTools/ui-harness/mockups/gameboard-8/
"""

import html
import pathlib

IMG = "/TCGEngine/AppCore/SWU/Images/WebpImages"

# ── THE BOARD ────────────────────────────────────────────────────────────────────────────────────
# Straight out of Tests/Visual/MobileLayout_FullBoard.md, cross-checked against the screenshot for
# the printed cost / power / HP / delta numbers the fixture does not carry.

ME, THEM = "me", "them"

SEATS = {
    ME: dict(
        name="Mariotee", kind="You", cls="seat-me", tint="#6fb8ff",
        leader=("JTL_006", "Darth Vader", "Victor Squadron Leader"),
        base=("SEC_025", "Amnesty Housing", "Coruscant"), base_hp=30, base_dmg=16,
        res_avail=5, res_total=5,
        deck=38, discard=2, discard_top="JTL_125",
        initiative=True,
    ),
    THEM: dict(
        name="Drixx", kind="Opponent", cls="seat-them", tint="#ffa06f",
        leader=("JTL_012", "Luke Skywalker", "Hero of Yavin"),
        base=("JTL_024", "Data Vault", "Scarif"), base_hp=33, base_dmg=23,
        res_avail=5, res_total=5,
        deck=42, discard=3, discard_top="JTL_175",
        initiative=False,
    ),
}

UP_XP     = dict(id="SOR_T01", name="Experience", delta="+1/+1")
UP_SHIELD = dict(id="SOR_T02", name="Shield",     delta="")

def unit(cid, name, sub, cost, pow_, hp, dmg=0, dpow=None, dhp=None,
         ups=(), shield=False, xp=False, exhausted=False):
    return dict(id=cid, name=name, sub=sub, cost=cost, pow=pow_, hp=hp, dmg=dmg,
                dpow=dpow, dhp=dhp, ups=list(ups), shield=shield, xp=xp, exhausted=exhausted)

BOARD = {
    THEM: dict(
        space=[
            unit("ASH_159", "Alphabet Sqn U-Wing", "Quiet Devotion", 5, 5, 6),
            unit("ASH_109", "T-6 Shuttle 1974", "With a Mentor's Dedication", 2, 2, 6, dmg=2),
        ],
        ground=[
            unit("LAW_145", "R2-D2", "Part of the Plan", 2, 3, 5, dmg=1, dpow="+1", dhp="+1",
                 ups=[UP_XP], xp=True),
            unit("ASH_155", "Grogu", "Yes, Yes, Yes", 3, 5, 7, dmg=5, dpow="+3", dhp="+1",
                 ups=[dict(id="LOF_102", name="Yoda's Lightsaber", delta="+3/+1")], shield=True),
        ],
        hand=["back", "back"],
    ),
    ME: dict(
        space=[
            unit("ASH_095", "Remnant Interceptor", "", 2, 4, 4, dpow="+1", dhp="+1",
                 ups=[UP_XP], xp=True),
            unit("JTL_T01", "TIE Fighter", "Token Unit", 0, 1, 1, shield=True),
            unit("JTL_T01", "TIE Fighter", "Token Unit", 0, 1, 1),
            unit("ASH_241", "Marrok's Fiend Fighter", "Formidable Pursuer", 3, 7, 8, dmg=2,
                 dpow="+2", dhp="+3", ups=[dict(id="JTL_189", name="Boba Fett", delta="+2/+3")]),
        ],
        ground=[
            unit("TWI_T01", "Battle Droid", "Token Unit", 0, 1, 1),
            unit("TWI_T01", "Battle Droid", "Token Unit", 0, 1, 1),
            unit("TWI_T01", "Battle Droid", "Token Unit", 0, 1, 1, exhausted=True),
            unit("TWI_T01", "Battle Droid", "Token Unit", 0, 1, 1, exhausted=True),
            unit("TWI_T01", "Battle Droid", "Token Unit", 0, 1, 1),
            unit("TWI_T01", "Battle Droid", "Token Unit", 0, 1, 1),
            unit("TWI_T01", "Battle Droid", "Token Unit", 0, 1, 1),
        ],
        hand=["LOF_091"],
    ),
}

ROUND, PHASE = 1, "Action"

STREAM = [
    ('sys',  'Round <em>1</em> — Action Phase'),
    ('s2',   'Drixx', 'plays <em>Grogu</em> and attaches <em>Yoda&rsquo;s Lightsaber</em>'),
    ('sys',  '<em>Marrok&rsquo;s Fiend Fighter</em> attacks <em>Data Vault</em> for 7'),
    ('s1',   'Mariotee', 'gl hf'),
    ('sys',  '<em>Grogu</em> gains a Shield'),
    ('s2',   'Drixx', 'good luck'),
    ('sys',  'Mariotee passes the action'),
]


# ── COMPONENTS ───────────────────────────────────────────────────────────────────────────────────

def e(s):
    return html.escape(str(s), quote=True)


SVG_SHIELD = ('<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" '
              'stroke-linejoin="round"><path d="M8 1.6 13.4 3.6v4.2c0 3.1-2.2 5.4-5.4 6.6'
              '-3.2-1.2-5.4-3.5-5.4-6.6V3.6Z"/></svg>')
SVG_XP     = ('<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" '
              'stroke-linejoin="round"><path d="M8 1.8 9.9 6l4.3.5-3.2 3 .9 4.3L8 11.6 '
              '4.1 13.8l.9-4.3-3.2-3L6.1 6Z"/></svg>')


def art(cid, alt=""):
    return f'<img src="{IMG}/{cid}.webp" alt="{e(alt)}" loading="lazy" decoding="async">'


def render_unit(u, side, *, uw=None, simple=False):
    """One unit tile. `simple` drops the upgrade strip (used where a layout is width-bound)."""
    own = "u--mine" if side == ME else "u--theirs"
    cls = ["u", own]
    if u["exhausted"]:
        cls.append("is-exhausted")
    style = f' style="--uw:{uw}px"' if uw else ""
    wound = min(100, round(100 * u["dmg"] / u["hp"])) if u["hp"] else 0

    toks = ""
    if u["shield"]:
        toks += f'<span class="tok tok--shield" title="Shield">{SVG_SHIELD}</span>'
    if u["xp"]:
        toks += f'<span class="tok tok--xp" title="Experience">{SVG_XP}</span>'
    toks = f'<span class="u__toks">{toks}</span>' if toks else ""

    ups = ""
    if u["ups"] and not simple:
        rows = "".join(
            f'<span class="up">{art(x["id"], x["name"])}<b>{e(x["name"])}</b>'
            f'{f"<i>{e(x['delta'])}</i>" if x["delta"] else ""}</span>'
            for x in u["ups"])
        ups = f'<span class="u__ups">{rows}</span>'

    sub = f'<span class="u__sub">{e(u["sub"])}</span>' if u["sub"] else ""
    dpow = f'<s>{e(u["dpow"])}</s>' if u["dpow"] else ""
    dhp  = f'<s>{e(u["dhp"])}</s>' if u["dhp"] else ""
    dmg  = f'<span class="u__dmg">{u["dmg"]}</span>' if u["dmg"] else '<span class="u__dmg"></span>'

    return (
        f'<span class="{" ".join(cls)}"{style}>'
        f'<span class="u__head"><span class="u__cost">{u["cost"]}</span>'
        f'<span class="u__name">{e(u["name"])}{sub}</span></span>'
        f'<span class="u__art">{art(u["id"], u["name"])}{toks}</span>'
        f'{ups}'
        f'<span class="u__foot" style="--wound:{wound}%">'
        f'<span class="u__pow">{u["pow"]}{dpow}</span>{dmg}'
        f'<span class="u__hp">{u["hp"]}{dhp}</span></span>'
        f'</span>')


def render_mini(u):
    wound = min(100, round(100 * u["dmg"] / u["hp"])) if u["hp"] else 0
    cls = "mini is-exhausted" if u["exhausted"] else "mini"
    return (f'<span class="{cls}" style="--wound:{wound}%"><b>{e(u["name"])}</b>'
            f'<span class="bar"><i></i></span>'
            f'<s><em>{u["pow"]}</em><u>{u["hp"] - u["dmg"]}/{u["hp"]}</u></s></span>')


def units(lst, side, *, uw=None, simple=False, extra=""):
    """Units always HUG THE MIDLINE. The opponent's rows sit above it, so they pack to the bottom
    of their box; yours sit below it, so they pack to the top. The two armies then face each other
    across the seam instead of being flung to the outer edges of the board — which is what the
    first cut of this sheet did, and it made every concept read worse than it is."""
    align = "units--bot" if side == THEM else ""
    return (f'<span class="units {align} {extra}">'
            + "".join(render_unit(u, side, uw=uw, simple=simple) for u in lst)
            + "</span>")


def minis(lst):
    return '<span class="b6__minis">' + "".join(render_mini(u) for u in lst) + "</span>"


def seat_chip(side):
    s = SEATS[side]
    return (f'<span class="seat"><span class="seat__k">{e(s["kind"])}</span>'
            f'<span class="seat__n">{e(s["name"])}</span></span>')


def res(side, *, compact=False):
    s = SEATS[side]
    pips = "".join(f'<i class="{"on" if i < s["res_avail"] else ""}"></i>'
                   for i in range(s["res_total"]))
    n = (f'<span class="res__n"><b>{s["res_avail"]}</b>'
         f'{"" if compact else "/" + str(s["res_total"]) + " Resources"}</span>')
    return f'<span class="res"><span class="res__pips">{pips}</span>{n}</span>'


def initiative(side):
    return ('<span class="init">Initiative</span>' if SEATS[side]["initiative"]
            else '<span class="init init--ghost">No initiative</span>')


def leader(side, lw=208):
    cid, name, sub = SEATS[side]["leader"]
    return f'<span class="card-l" style="--lw:{lw}px">{art(cid, name + " — " + sub)}</span>'


def base(side, lw=208):
    s = SEATS[side]
    cid, name, sub = s["base"]
    remain = s["base_hp"] - s["base_dmg"]
    wound = round(100 * s["base_dmg"] / s["base_hp"])
    crit = " base--critical" if remain <= 8 else ""
    return (f'<span class="base{crit}" style="--lw:{lw}px">'
            f'<span class="card-b">{art(cid, name)}</span>'
            f'<span class="base__meter" style="--wound:{wound}%"><span class="base__fill"></span>'
            f'<b><span>Base</span><s>{remain}<i> / {s["base_hp"]}</i></s></b></span></span>')


def piles(side, pw=54):
    s = SEATS[side]
    return (f'<span class="pile" style="--pw:{pw}px">'
            f'<span class="pile__card pile__back"></span>'
            f'<span class="pile__k">Deck<b>{s["deck"]}</b></span></span>'
            f'<span class="pile" style="--pw:{pw}px">'
            f'<span class="pile__card">{art(s["discard_top"], "Discard")}</span>'
            f'<span class="pile__k">Discard<b>{s["discard"]}</b></span></span>')


def hand(side, hw=124):
    cards = BOARD[side]["hand"]
    if side == THEM:
        inner = "".join('<span class="h"></span>' for _ in cards)
        return (f'<span class="hand hand--theirs" style="--hw:{hw}px">{inner}'
                f'<span class="n">Hand<b>{len(cards)}</b></span></span>')
    inner = "".join(f'<span class="h is-playable">{art(c, "Hand card")}</span>' for c in cards)
    return f'<span class="hand" style="--hw:{hw}px">{inner}</span>'


def turn_block():
    return (f'<span class="turn"><span class="turn__r"><span class="lbl">Round</span>'
            f'<b>{ROUND}</b></span><span class="turn__p">{e(PHASE)} &middot; your action</span></span>')


def actions(stacked=True):
    return ('<span class="b1__acts">'
            '<button class="btn" type="button">Keep initiative</button>'
            '<button class="btn btn--go" type="button">Pass</button></span>'
            if stacked else
            '<button class="btn" type="button">Keep initiative</button>'
            '<button class="btn btn--go" type="button">Pass</button>')


def stream(rows=7):
    out = []
    for item in STREAM[:rows]:
        if item[0] == "sys":
            out.append(f'<p class="sys">{item[1]}</p>')
        else:
            out.append(f'<p><b class="{item[0]}">{e(item[1])}</b> {item[2]}</p>')
    return "".join(out)


def sidebar(rows=7, cls="pa-glass"):
    return (f'<aside class="side {cls}">'
            f'<span class="side__top">{turn_block()}'
            f'<button class="btn" type="button">Undo</button></span>'
            f'<span class="lbl">Game log &amp; chat</span>'
            f'<span class="log">{stream(rows)}</span>'
            f'<span class="chatbar"><input placeholder="Message&hellip;" aria-label="Message">'
            f'<button class="btn" type="button">Send</button></span></aside>')


def arena_key(word, side, which):
    n = len(BOARD[side][which])
    return f'<span class="arena-k"><b>{word}</b> &middot; {n}</span>'


def mid(text="Midline"):
    """The chip carries markup (entities, <em>), so it is NOT escaped."""
    inner = f'<span class="mid__chip">{text}</span>' if text else ""
    return f'<span class="mid">{inner}</span>'


# ── THE EIGHT LAYOUTS ────────────────────────────────────────────────────────────────────────────

def c1():
    """MIRROR MAT — today's topology, rebuilt."""
    def rail(side):
        s = SEATS[side]
        return (f'<div class="b1__rail {s["cls"]}">{seat_chip(side)}{initiative(side)}'
                f'<span class="piles">{piles(side, 44)}</span>{res(side)}</div>')

    def half(side):
        return (f'<div class="b1__half {SEATS[side]["cls"]}">'
                f'<div class="b1__col">{arena_key("Space", side, "space")}'
                f'{units(BOARD[side]["space"], side, uw=98)}</div>'
                f'<div class="b1__spine">{leader(side, 216)}{base(side, 216)}</div>'
                f'<div class="b1__col">{arena_key("Ground", side, "ground")}'
                f'{units(BOARD[side]["ground"], side, uw=98)}</div>'
                f'</div>')

    return (f'<div class="board b1">'
            f'<div class="b1__play">{rail(THEM)}{half(THEM)}{mid("Round 1 &middot; Action")}{half(ME)}{rail(ME)}'
            f'<div class="b1__hand">{hand(THEM, 56)}{hand(ME, 118)}{actions()}</div></div>'
            f'{sidebar(7)}</div>')


def c2():
    """TWO THEATRES — split by arena, not by player."""
    def theatre(word, which, kind, live):
        return (f'<div class="b2__th b2__th--{kind}{" is-live" if live else ""}">'
                f'<span class="brk"><i></i><i></i><i></i><i></i></span>'
                f'<div class="b2__thk"><h3>{word} theatre</h3>'
                f'<span class="lbl">{SEATS[THEM]["name"]} {len(BOARD[THEM][which])}'
                f' &nbsp;vs&nbsp; {len(BOARD[ME][which])} {SEATS[ME]["name"]}</span>'
                f'<span class="tally">{"Live" if live else "Quiet"}</span></div>'
                f'{units(BOARD[THEM][which], THEM, uw=104)}'
                f'{mid()}'
                f'{units(BOARD[ME][which], ME, uw=104)}</div>')

    pylon = (f'<div class="b2__pylon">'
             f'<div class="{SEATS[THEM]["cls"]}">{seat_chip(THEM)}</div>'
             f'{leader(THEM, 208)}{base(THEM, 208)}'
             f'<div style="align-self:center;display:grid;gap:8px;justify-items:center">'
             f'{initiative(ME)}{turn_block()}</div>'
             f'{base(ME, 208)}{leader(ME, 208)}'
             f'<div class="{SEATS[ME]["cls"]}" style="display:flex;align-items:center;gap:12px">'
             f'{seat_chip(ME)}{res(ME, compact=True)}</div></div>')

    strip = (f'<div class="b1__hand">{hand(THEM, 56)}{hand(ME, 118)}{actions()}</div>')

    return (f'<div class="board b2">{pylon}'
            f'<div class="b2__theatres">{theatre("Space", "space", "space", False)}'
            f'{theatre("Ground", "ground", "ground", True)}{strip}</div>{sidebar(7)}</div>')


def c3():
    """THE SPINE — a full-height command column; arenas become vertical lanes."""
    def lane(word, which, kind):
        return (f'<div class="b3__lane b3__lane--{kind}">'
                f'{arena_key(word, THEM, which)}'
                f'<div class="{SEATS[THEM]["cls"]}">{units(BOARD[THEM][which], THEM, uw=100, extra="units--c")}</div>'
                f'{mid()}'
                f'<div class="{SEATS[ME]["cls"]}">{units(BOARD[ME][which], ME, uw=100, extra="units--c")}</div>'
                f'</div>')

    spine = (f'<div class="b3__spine pa-glass">'
             f'<div class="{SEATS[THEM]["cls"]}" style="display:flex;align-items:center;gap:12px;width:100%">'
             f'{seat_chip(THEM)}<span style="margin-inline-start:auto">{res(THEM, compact=True)}</span></div>'
             f'{leader(THEM, 200)}{base(THEM, 200)}'
             f'<div class="b3__turn">{initiative(ME)}{turn_block()}'
             f'<div style="display:grid;gap:8px;width:100%">{actions(False)}</div></div>'
             f'{base(ME, 200)}{leader(ME, 200)}'
             f'<div class="{SEATS[ME]["cls"]}" style="display:flex;align-items:center;gap:12px;width:100%">'
             f'{seat_chip(ME)}<span style="margin-inline-start:auto">{res(ME, compact=True)}</span></div>'
             f'</div>')

    return (f'<div class="board b3">{lane("Space", "space", "space")}{spine}'
            f'{lane("Ground", "ground", "ground")}{sidebar(9)}'
            f'<div class="b3__hand b1__hand">{hand(THEM, 54)}{hand(ME, 118)}'
            f'<span class="piles" style="display:flex;gap:16px">{piles(ME, 48)}</span></div></div>')


def c4():
    """THE CONSOLE — asymmetric; one command column owns all of your state."""
    cmd = (f'<div class="b4__cmd pa-glass {SEATS[ME]["cls"]}">'
           f'<div class="b4__cmdrow">{seat_chip(ME)}{res(ME)}</div>'
           f'{leader(ME, 292)}{base(ME, 292)}'
           f'<div class="b4__cmdrow" style="gap:20px">{piles(ME, 50)}'
           f'<span style="margin-inline-start:auto;display:grid;gap:6px;justify-items:end">'
           f'{initiative(ME)}{turn_block()}</span></div>'
           f'<span class="log">{stream(7)}</span>'
           f'<span class="chatbar"><input placeholder="Message&hellip;" aria-label="Message">'
           f'<button class="btn" type="button">Send</button></span></div>')

    strip = (f'<div class="b4__strip {SEATS[THEM]["cls"]}">{seat_chip(THEM)}{initiative(THEM)}'
             f'{leader(THEM, 138)}{base(THEM, 138)}{res(THEM)}'
             f'<span class="piles">{piles(THEM, 42)}{hand(THEM, 48)}</span></div>')

    def arenas(side, bot=False):
        cls = SEATS[side]["cls"]
        return (f'<div class="b4__arenas {cls}">'
                f'<div class="b4__ar">{arena_key("Space", side, "space")}'
                f'{units(BOARD[side]["space"], side, uw=106)}</div>'
                f'<div class="b4__ar">{arena_key("Ground", side, "ground")}'
                f'{units(BOARD[side]["ground"], side, uw=106)}</div></div>')

    return (f'<div class="board b4">{cmd}'
            f'<div class="b4__board">{strip}{arenas(THEM)}{mid()}{arenas(ME, True)}'
            f'<div class="b1__hand">{hand(ME, 118)}{actions()}</div></div></div>')


def c5():
    """THE INSTRUMENT WALL — fixed plates, fixed boxes, nothing ever moves."""
    def plate(label, body, col, row, live=False, cls=""):
        return (f'<div class="plate {cls}{" is-live" if live else ""}" '
                f'style="grid-column:{col};grid-row:{row}">'
                f'<span class="brk"><i></i><i></i><i></i><i></i></span>'
                f'<span class="lbl">{label}</span>{body}</div>')

    out = []
    # Their band (row 1)
    s = SEATS[THEM]
    out.append(plate("Seat", f'<div class="b5__cell {s["cls"]}">{seat_chip(THEM)}{initiative(THEM)}</div>', "1 / 3", "1", cls=s["cls"]))
    out.append(plate("Resources", f'<div class="b5__cell">{res(THEM, compact=True)}</div>', "3 / 5", "1"))
    out.append(plate("Deck &amp; discard", f'<div class="b5__cell">{piles(THEM, 38)}</div>', "5 / 7", "1"))
    out.append(plate("Hand", f'<div class="b5__cell">{hand(THEM, 42)}</div>', "7 / 9", "1"))
    out.append(plate("Leader", f'<div class="b5__cell">{leader(THEM, 126)}</div>', "9 / 11", "1"))
    out.append(plate("Base", f'<div class="b5__cell">{base(THEM, 126)}</div>', "11 / 13", "1"))
    # Their arenas (row 2)
    out.append(plate("Space arena &middot; opponent", units(BOARD[THEM]["space"], THEM, uw=96),
                     "1 / 7", "2", cls="plate--arena " + s["cls"]))
    out.append(plate("Ground arena &middot; opponent", units(BOARD[THEM]["ground"], THEM, uw=96),
                     "7 / 13", "2", cls="plate--arena " + s["cls"]))
    # Engagement bar (row 3)
    out.append(f'<div class="b5__eng" style="grid-row:3">{turn_block()}'
               f'<span class="lbl">Priority</span><span class="seat__n" style="font-size:var(--t-15)">'
               f'{e(SEATS[ME]["name"])}</span>{initiative(ME)}'
               f'<span style="margin-inline-start:auto;display:flex;gap:12px">{actions(False)}</span></div>')
    # My arenas (row 4)
    s = SEATS[ME]
    out.append(plate("Space arena &middot; you", units(BOARD[ME]["space"], ME, uw=96),
                     "1 / 7", "4", live=False, cls="plate--arena " + s["cls"]))
    out.append(plate("Ground arena &middot; you", units(BOARD[ME]["ground"], ME, uw=96),
                     "7 / 13", "4", live=True, cls="plate--arena " + s["cls"]))
    # My band (row 5)
    out.append(plate("Seat", f'<div class="b5__cell {s["cls"]}">{seat_chip(ME)}</div>', "1 / 3", "5", cls=s["cls"]))
    out.append(plate("Resources", f'<div class="b5__cell">{res(ME, compact=True)}</div>', "3 / 5", "5"))
    out.append(plate("Deck &amp; discard", f'<div class="b5__cell">{piles(ME, 38)}</div>', "5 / 7", "5"))
    out.append(plate("Leader", f'<div class="b5__cell">{leader(ME, 126)}</div>', "7 / 9", "5"))
    out.append(plate("Base", f'<div class="b5__cell">{base(ME, 126)}</div>', "9 / 11", "5"))
    out.append(plate("Undo", '<div class="b5__cell"><button class="btn" type="button">Undo last</button></div>',
                     "11 / 13", "5"))
    # Hand (row 6)
    out.append(plate("Hand &middot; 1 card", f'<div class="b5__cell">{hand(ME, 116)}</div>', "1 / 13", "6"))
    out.append(f'<div class="b5__side">{sidebar(12, "")}</div>')
    return f'<div class="board b5">{"".join(out)}</div>'


def c6():
    """FOCUS MAT — the board spends its space on the decision in front of you."""
    def rail(side):
        s = SEATS[side]
        return (f'<div class="b6__rail {s["cls"]}">{seat_chip(side)}{initiative(side)}'
                f'<span style="display:flex;gap:16px">{piles(side, 34)}</span>{res(side)}</div>')

    fold = (f'<div class="b6__fold"><h3>Space</h3>'
            f'<div class="{SEATS[THEM]["cls"]}">{minis(BOARD[THEM]["space"])}</div>'
            f'<span class="lbl">vs</span>'
            f'<div class="{SEATS[ME]["cls"]}">{minis(BOARD[ME]["space"])}</div>'
            f'<span class="b6__hint">Collapsed &middot; click to open</span></div>')

    return (f'<div class="board b6">'
            f'<div class="b6__play">{rail(THEM)}{fold}'
            f'<div class="b6__open is-live {SEATS[THEM]["cls"]}">'
            f'<span class="brk"><i></i><i></i><i></i><i></i></span>'
            f'<div class="b2__thk"><h3 style="margin:0;font-size:var(--t-12);font-weight:700;'
            f'letter-spacing:.24em;text-transform:uppercase;color:var(--ink-2)">Ground &middot; live</h3>'
            f'<span class="lbl">{e(SEATS[THEM]["name"])}</span>'
            f'<span style="margin-inline-start:auto;display:flex;gap:12px;align-items:center">'
            f'{leader(THEM, 150)}{base(THEM, 150)}</span></div>'
            f'{units(BOARD[THEM]["ground"], THEM, uw=148)}</div>'
            f'{mid("Ground engagement")}'
            f'<div class="b6__open {SEATS[ME]["cls"]}" style="background:linear-gradient(0deg,rgba(62,54,40,.30),transparent 70%)">'
            f'<div class="b2__thk"><h3 style="margin:0;font-size:var(--t-12);font-weight:700;'
            f'letter-spacing:.24em;text-transform:uppercase;color:var(--ink-2)">Your ground</h3>'
            f'<span style="margin-inline-start:auto;display:flex;gap:12px;align-items:center">'
            f'{base(ME, 150)}{leader(ME, 150)}</span></div>'
            f'{units(BOARD[ME]["ground"], ME, uw=148)}</div>'
            f'{fold.replace("Space", "Space").replace("Collapsed &middot; click to open", "Collapsed &middot; your 4 vs their 2")}'
            f'{rail(ME)}'
            f'<div class="b1__hand">{hand(THEM, 54)}{hand(ME, 118)}{actions()}</div></div>'
            f'{sidebar(7)}</div>')


def c7():
    """CLEAN MAT — the cards are the interface."""
    def edge(side, bot=False):
        s = SEATS[side]
        return (f'<div class="b7__edge{" b7__edge--bot" if bot else ""} {s["cls"]}">'
                f'<span style="color:var(--tint)">{e(s["kind"])}</span><b>{e(s["name"])}</b>'
                f'<span>Resources <b>{s["res_avail"]}/{s["res_total"]}</b></span>'
                f'<span>Deck <b>{s["deck"]}</b></span>'
                f'<span>Discard <b>{s["discard"]}</b></span>'
                f'<span>Hand <b>{len(BOARD[side]["hand"])}</b></span>'
                f'<span class="sp">{"Round 1 &middot; Action &middot; " if not bot else ""}'
                f'{"<b>Initiative</b>" if s["initiative"] else "No initiative"}</span></div>')

    def half(side, bot=False):
        al = "units--bot" if bot else ""
        return (f'<div class="b7__half {SEATS[side]["cls"]}">'
                f'{units(BOARD[side]["space"], side, uw=118, simple=True)}'
                f'<div class="b7__gut"><span>Space</span></div>'
                f'<div class="b7__ctr">{leader(side, 228)}{base(side, 228)}</div>'
                f'<div class="b7__gut"><span>Ground</span></div>'
                f'{units(BOARD[side]["ground"], side, uw=118, simple=True)}</div>')

    return (f'<div class="board b7">{edge(THEM)}{half(THEM)}{mid("")}{half(ME, True)}{edge(ME, True)}'
            f'<div class="b7__hand"><span></span>{hand(ME, 116)}'
            f'<span style="display:flex;gap:12px;align-items:flex-end;justify-content:flex-end">'
            f'{actions(False)}</span></div></div>')


def c8():
    """DEPTH TABLE — their half recedes; depth carries ownership."""
    def rail(side):
        s = SEATS[side]
        return (f'<div class="b8__rail {s["cls"]}">{seat_chip(side)}{initiative(side)}'
                f'<span style="display:flex;gap:16px">{piles(side, 36)}</span>{res(side)}</div>')

    def row(side, bot=False):
        al = "units--bot" if bot else ""
        return (f'<div class="b8__row">'
                f'{units(BOARD[side]["space"], side, uw=96)}'
                f'<div class="b8__ctr">{leader(side, 232)}{base(side, 232)}</div>'
                f'{units(BOARD[side]["ground"], side, uw=96)}</div>')

    return (f'<div class="board b8">'
            f'<div class="b8__far">{rail(THEM)}{row(THEM)}</div>'
            f'<div class="b8__edge"></div>'
            f'<div class="b8__near">{row(ME, True)}{rail(ME)}</div>'
            f'<div class="b8__hand">{turn_block()}{hand(ME, 128)}'
            f'<span style="display:flex;gap:12px;align-items:flex-end;justify-content:flex-end">'
            f'{actions(False)}</span></div>'
            f'<div class="b8__side pa-glass">{sidebar(5, "")}</div></div>')


# ── THE SHEET ────────────────────────────────────────────────────────────────────────────────────

CONCEPTS = [
    (1, "Mirror Mat", c1, False,
     ["Today&rsquo;s arrangement, rebuilt properly rather than replaced. Space left, a leader/base spine "
      "down the centre, ground right, mirrored across a midline, stream on the right. It refuses nothing "
      "and risks nothing, which is exactly its job here: it is the <b>control</b> in the experiment.",
      "One thing is deliberately left alone. C1 keeps the shipped <b>centred damage disc</b> so you can "
      "judge it directly against the wound meter every other card uses &mdash; look at Grogu in C1, then "
      "Grogu anywhere else."],
     [("Familiar", 0), ("Lowest migration cost", 0), ("Centre still holds the least-changing cards", 1),
      ("Wide arenas still crush at the edges", 1)]),

    (2, "Two Theatres", c2, True,
     ["Stop splitting the screen by <b>player</b> and split it by <b>arena</b>. A full-width space theatre "
      "over a full-width ground theatre, each with its own midline and its own engagement. Leaders and "
      "bases move off the centre onto an outboard command pylon.",
      "This is the only layout here that matches the rules topology: a unit only ever fights inside its own "
      "arena, so every attack becomes a short <b>vertical hop</b> instead of a diagonal across a spine. "
      "Your seven Battle Droids get 1 080px instead of 380px."],
     [("Rules-true topology", 0), ("Solves arena crush outright", 0), ("Full-width combat reading", 0),
      ("Breaks 4 years of muscle memory", 1), ("Leaders lose the centre", 1)]),

    (3, "The Spine", c3, True,
     ["A full-height glass command column down the centre owns both leaders, both bases, initiative, round, "
      "phase and the action buttons &mdash; everything that answers <b>who, and whose turn</b>. The two "
      "arenas become continuous vertical lanes either side, each holding both players.",
      "The centre becomes the most-looked-at thing on the board instead of the least. You read by column: "
      "the whole space war on the left, the whole ground war on the right."],
     [("Turn state is finally findable", 0), ("Arena identity is spatial", 0),
      ("Narrow lanes at 7+ units", 1), ("Centre column is heavy chrome", 1)]),

    (4, "The Console", c4, False,
     ["Asymmetric. You are not a second player on a shared table; you are the operator. A left command "
      "column owns everything of yours that is static &mdash; seat, resources, piles, leader, base, "
      "initiative, turn, stream &mdash; and the board itself carries <b>no chrome at all</b>.",
      "The opponent gets one compact strip, because on your turn almost nothing about them changes. "
      "The 300px sidebar that today shows an empty log and a dash finally earns its width."],
     [("Sidebar earns its width", 0), ("Board area is pure board", 0),
      ("Opponent state is compressed", 1), ("Asymmetry is disorienting in mirror matches", 1)]),

    (5, "The Instrument Wall", c5, True,
     ["<b>Absolute positional constancy.</b> Every zone is a labelled plate at a fixed grid box that never "
      "moves and never resizes, whatever the board holds. Overflow scrolls <i>inside</i> its plate instead "
      "of shoving a neighbour &mdash; so a seventh Battle Droid can never push your hand off-screen.",
      "Between the two halves runs the engagement bar: round, phase, priority, initiative and the two "
      "actions, at the one place your eye already crosses. Corner brackets light on the plate that is live.",
      "Built for the player who has played 500 games and wants their eye to land without searching."],
     [("Nothing ever moves", 0), ("Overflow is contained, not destructive", 0),
      ("Live-zone marker", 0), ("Most chrome of the eight", 1), ("Smallest cards", 1)]),

    (6, "Focus Mat", c6, True,
     ["The board spends its space on the decision in front of you. The arena you can act in runs at full "
      "size; the other collapses to a summary strip that still names every unit, its wound and its stats. "
      "One click re-opens it.",
      "Shown here mid-game with <b>ground live</b>: the seven droids and Grogu are at 126px, while the "
      "space war &mdash; six units nobody is about to attack with &mdash; sits readable in 56px. Nothing "
      "is hidden; everything is proportioned."],
     [("Space goes where the decision is", 0), ("Largest cards where it matters", 0),
      ("Layout moves under you", 1), ("Needs a rule for when to auto-switch", 1)]),

    (7, "Clean Mat", c7, False,
     ["The cards <b>are</b> the interface. No plates, no rails, no panels. An arena is a hairline and a "
      "tracked word set in the gutter; every piece of state collapses into two near-transparent edge bars.",
      "Buys roughly 22% more card per pixel than any other layout here, and lets the card art and the "
      "aspect colours do the work the chrome is currently doing badly."],
     [("Biggest cards", 0), ("Art and aspects read clearly", 0),
      ("State is one glance further away", 1), ("Log/chat has to become a drawer", 1)]),

    (8, "Depth Table", c8, False,
     ["Model the table. Your half sits forward, full size, under the key light; the opponent&rsquo;s half "
      "recedes &mdash; smaller, cooler, dimmer, tipped away in Z &mdash; exactly as it does across a real "
      "table, with a lit table edge between you.",
      "Depth carries ownership, so the green/red glows can go entirely: <b>C8 is the only card here with "
      "no ownership tint at all</b>, and the aspect colours on the cards get their contrast back."],
     [("Ownership without colour", 0), ("Frees the whole colour budget", 0), ("Strong first impression", 0),
      ("Their cards are ~10% smaller", 1), ("3D transforms need a WebKit check", 1)]),
]


def sheet(n, title, fn, dealt, thesis, fixes):
    tags = "".join(f'<li class="{"risk" if r else ""}">{t}</li>' for t, r in fixes)
    body = "".join(f'<p class="thesis">{t}</p>' for t in thesis)
    return (f'<section class="sheet{" sheet--dealt" if dealt else ""}" id="c{n}">'
            f'<div class="sheet__head"><span class="sheet__n">{n}</span><div>'
            f'<h2>{title}</h2>{body}<ul class="fixes">{tags}</ul></div></div>'
            f'{fn()}</section>')


def main():
    nav = "".join(
        f'<a href="#c{n}" class="{"is-dealt" if d else ""}"><i>{n}</i>{t}</a>'
        for n, t, _f, d, _th, _fx in CONCEPTS)
    sheets = "".join(sheet(n, t, f, d, th, fx) for n, t, f, d, th, fx in CONCEPTS)

    doc = f"""<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SWUSim gameboard &mdash; eight desktop structures</title>
<!-- ⚠ MOCKUP, NOT SHIPPED UI. Generated by build.py — edit that, not this file.
     All eight boards render the SAME game state: SWUSim/Tests/Visual/MobileLayout_FullBoard.md,
     the fixture behind the reference screenshot. The visual world is the shipped Petranaki system
     (tokens.css + components.css + swusim-menu-2.css + petranaki-glass.css), unchanged; only the
     LAYOUT differs between the eight, because the layout is what is being chosen.
     The empty .home-header below is load-bearing — swusim-menu-2.css scopes the page ground, the
     milled-steel mesh and every --pane token to `body:has(.home-header)`. -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,400..700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/TCGEngine/SharedUI/css/tokens.css">
<link rel="stylesheet" href="/TCGEngine/SharedUI/css/components.css">
<link rel="stylesheet" href="/TCGEngine/SharedUI/Sites/SWUSim/css/swusim-menu-2.css">
<link rel="stylesheet" href="/TCGEngine/SharedUI/Sites/SWUSim/css/petranaki-glass.css">
<link rel="stylesheet" href="./board.css">
</head>
<body>
<div class="home-header"></div>
<div class="page">
  <header class="masthead">
    <h1>Gameboard &mdash; eight desktop structures</h1>
    <p>Eight materially different ways to arrange the desktop board, all inside the <b>shipped Petranaki
    world</b> &mdash; same tokens, same chamfer, same rationed gold, same steel. Nothing here proposes a new
    look; the look is settled. What is being chosen is the <b>arrangement</b>.</p>
    <p>Every board renders the <b>same game state</b>: Vader / Amnesty Housing against Luke / Data Vault,
    the board from the reference screenshot, down to Grogu&rsquo;s five damage and the seventh Battle Droid.
    Card sizes, wrapping and crowding are therefore directly comparable between the eight.</p>
    <p>Three card-level decisions are shared by concepts&nbsp;2&ndash;8 so the layouts stay the only variable:
    damage is a <b>wound meter</b> rather than a disc stamped over the art; each upgrade is a <b>named row</b>
    with the stat delta it grants; and ownership is <b>cool versus warm</b> rather than green versus red,
    which stops the board fighting the Command and Aggression aspects. Concept&nbsp;1 keeps the shipped
    treatment as the control.</p>
  </header>
  <nav class="jump" aria-label="Concepts">{nav}</nav>
  <main>{sheets}</main>
</div>
</body>
</html>
"""
    out = pathlib.Path(__file__).with_name("index.html")
    out.write_text(doc, encoding="utf-8")
    print(f"wrote {out}  ({len(doc):,} bytes)")


if __name__ == "__main__":
    main()
