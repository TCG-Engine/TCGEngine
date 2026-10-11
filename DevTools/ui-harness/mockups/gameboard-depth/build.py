#!/usr/bin/env python3
"""
build.py — THE TABLE v4: concept 8 of ../gameboard-8/, reworked against the owner's notes.

⚠ MOCKUP GENERATOR, NOT SHIPPED UI.

Same game state as every card on the eight-concept sheet — SWUSim/Tests/Visual/MobileLayout_FullBoard.md,
the fixture behind the reference screenshot — so this can be held against concept 8 and against the
live board with nothing else changed. The state block is a verbatim copy of that sheet's; it is
duplicated rather than imported so the eight-concept sheet stays frozen as the record of the
decision and cannot be broken by edits here.

Two values are NOT from the fixture and are marked where they appear: the Credit counts, because
Credits are not in this fixture and the gold pip row has to be shown holding something.

    python3 build.py     # writes ./index.html
    http://localhost:3400/TCGEngine/DevTools/ui-harness/mockups/gameboard-depth/
"""

import html
import pathlib

IMG = "/TCGEngine/AppCore/SWU/Images/WebpImages"       # whole cards, 450×628 / 628×450
SQ  = "/TCGEngine/AppCore/SWU/Images/concat"           # the SQUARE 450×450 renderings
BACKS = "/TCGEngine/Assets/CardBacks/SWUSim"   # every one of them square, and that is the point

ME, THEM = "me", "them"

SEATS = {
    ME: dict(
        name="Mariotee", kind="You", cardback="default",
        leader=("JTL_006", "Darth Vader — Victor Squadron Leader"),
        base=("SEC_025", "Amnesty Housing"), base_hp=30, base_dmg=16,
        res_avail=5, res_total=5,
        credits=3,                       # DEMONSTRATION VALUE — not in the fixture
        deck=38, discard=2, discard_top="JTL_125",
        initiative=True,
    ),
    THEM: dict(
        name="Drixx", kind="Opponent", cardback="force-fam",
        leader=("JTL_012", "Luke Skywalker — Hero of Yavin"),
        base=("JTL_024", "Data Vault"), base_hp=33, base_dmg=23,
        res_avail=5, res_total=5,
        credits=1,                       # DEMONSTRATION VALUE — not in the fixture
        deck=42, discard=3, discard_top="JTL_175",
        initiative=False,
    ),
}


def unit(cid, name, pow_, hp, dmg=0, subs=(), shield=False, exhausted=False):
    """`subs` are attachment CARDS, banded below the unit exactly as the live board stacks them.
    Shield is a marker on the art, because it is a state rather than a card."""
    return dict(id=cid, name=name, pow=pow_, hp=hp, dmg=dmg,
                subs=list(subs), shield=shield, exhausted=exhausted)


# An eight-card grip: real cards, a plausible late-round hand. This is the width the removed round
# indicator paid for, and with it in the board's own state the separate proof strip has no job.
HAND = ["LOF_091", "JTL_096", "JTL_102", "JTL_125", "JTL_175", "ASH_095", "SOR_237", "LOF_102"]

XP = "SOR_T01"   # Experience — a token upgrade, so it bands below like any other attachment

BOARD = {
    THEM: dict(
        space=[
            unit("ASH_159", "Alphabet Squadron U-Wing", 5, 6),
            unit("ASH_109", "T-6 Shuttle 1974", 2, 6, dmg=2),
        ],
        ground=[
            unit("LAW_145", "R2-D2", 3, 5, dmg=1, subs=[XP]),
            unit("ASH_155", "Grogu", 5, 7, dmg=5, subs=["LOF_102"], shield=True),
        ],
        hand=5,
    ),
    ME: dict(
        space=[
            unit("ASH_095", "Remnant Interceptor", 4, 4, subs=[XP]),
            unit("JTL_T01", "TIE Fighter", 1, 1, shield=True),
            unit("JTL_T01", "TIE Fighter", 1, 1),
            unit("ASH_241", "Marrok's Fiend Fighter", 7, 8, dmg=2, subs=["JTL_189"]),
        ],
        ground=[unit("TWI_T01", "Battle Droid", 1, 1, exhausted=(i in (2, 3))) for i in range(7)],
        hand=HAND,
    ),
}

ROUND, PHASE = 1, "Action"

STREAM = [
    ("sys", "Round <em>1</em> — Action Phase"),
    ("s2", "Drixx", "plays <em>Grogu</em> and attaches <em>Yoda&rsquo;s Lightsaber</em>"),
    ("sys", "<em>Marrok&rsquo;s Fiend Fighter</em> attacks <em>Data Vault</em> for 7"),
    ("s1", "Mariotee", "gl hf"),
    ("sys", "<em>Grogu</em> gains a Shield"),
    ("s2", "Drixx", "good luck"),
    ("sys", "Mariotee passes the action"),
]

SVG_SHIELD = ('<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" '
              'stroke-linejoin="round"><path d="M8 1.6 13.4 3.6v4.2c0 3.1-2.2 5.4-5.4 6.6'
              '-3.2-1.2-5.4-3.5-5.4-6.6V3.6Z"/></svg>')


def e(s):
    return html.escape(str(s), quote=True)


def art(cid, alt=""):
    """The whole card. Leaders and bases only — they are landscape and are shown entire."""
    return f'<img src="{IMG}/{cid}.webp" alt="{e(alt)}" loading="lazy" decoding="async">'


def sq(cid, alt=""):
    """The SQUARE rendering from concat/: the card with its rules box removed, 450×450, with the
    printed power and HP gems at fixed bottom corners. Everything that is square on this board —
    units, attachments, the hand, the discard — is one of these, which is what lets the live
    counters sit exactly on the printed ones instead of beside them."""
    return f'<img src="{SQ}/{cid}.webp" alt="{e(alt)}" loading="lazy" decoding="async">'


def back(side, alt="Card back"):
    """A seat's card back is a cosmetic it chose, so its deck and its hand both wear it."""
    return (f'<img src="{BACKS}/{SEATS[side]["cardback"]}.webp" alt="{e(alt)}" '
            f'loading="lazy" decoding="async">')


# ── COMPONENTS ───────────────────────────────────────────────────────────────────────────────────

def render_unit(u, cw=124):
    """The square concat rendering, attachments banded below it, and the board's three shipped
    counters. Power and HP are single resolved totals — the shipped board's CurrentPower and
    CurrentHP are ObjectPowerBadgeValue() / ObjectHPBadgeValue(), which already include every
    modifier, so nothing is ever split into a base and a `+N`."""
    cls = "u is-exhausted" if u["exhausted"] else "u"
    n = len(u["subs"])

    subs = ""
    if n:
        subs = ('<span class="u__subs">'
                + "".join(f'<span class="sub" style="--b:{(n - 1 - i) * 17}px">{sq(cid, "Attachment")}</span>'
                          for i, cid in enumerate(u["subs"]))
                + "</span>")

    marks = (f'<span class="marks"><span class="mark mark--shield" title="Shield">{SVG_SHIELD}</span></span>'
             if u["shield"] else "")
    dmg = f'<span class="dmg">{u["dmg"]}</span>' if u["dmg"] else ""

    return (f'<span class="{cls}" style="--cw:{cw}px;--subs:{n}">'
            f'<span class="cardw">{sq(u["id"], u["name"])}{marks}{dmg}'
            f'<span class="ct ct--pw">{u["pow"]}</span>'
            f'<span class="ct ct--hp">{u["hp"]}</span></span>'
            f'{subs}</span>')


def field(lst, cw=124):
    return '<div class="field">' + "".join(render_unit(u, cw) for u in lst) + "</div>"


def who(side):
    s = SEATS[side]
    return (f'<span class="who"><span class="who__k">{e(s["kind"])}</span>'
            f'<span class="who__n">{e(s["name"])}</span></span>')


def initiative(side):
    return ('<span class="init">Initiative</span>' if SEATS[side]["initiative"]
            else '<span class="init init--ghost">No initiative</span>')


def pips(side):
    """Resources in steel, Credits in gold, right-aligned so the two rows share an edge."""
    s = SEATS[side]
    res = "".join(f'<i class="{"on" if i < s["res_avail"] else ""}"></i>' for i in range(s["res_total"]))
    cred = '<i class="on"></i>' * s["credits"]
    return (f'<span class="meters">'
            f'<span class="pips"><span class="pips__row">{res}</span>'
            f'<span class="pips__n"><b>{s["res_avail"]}</b>/{s["res_total"]} Resources</span></span>'
            f'<span class="pips pips--credit"><span class="pips__row">{cred}</span>'
            f'<span class="pips__n"><b>{s["credits"]}</b> '
            f'{"Credit" if s["credits"] == 1 else "Credits"}</span></span></span>')


def pile(label, inner, count):
    """Label, image, count badge — a vertical cascade, and square, because the deck is card backs
    and the discard has to match the pile beside it."""
    return (f'<span class="pile"><span class="pile__k">{label}</span>'
            f'<span class="pile__img">{inner}</span>'
            f'<span class="pile__n">{count}</span></span>')


def piles(side):
    s = SEATS[side]
    out = pile("Deck", back(side, "Deck"), s["deck"])
    out += pile("Discard", sq(s["discard_top"], "Top of discard"), s["discard"])
    return f'<span class="piles">{out}</span>'


def their_hand(hw=104, room=620):
    """Face-down, same solved overlap as your own hand, with the count beside it. It sits in the
    band between their name and their resources, which was the emptiest part of the board."""
    n = BOARD[THEM]["hand"]
    ov = 12 if n < 2 else max(12, -(-(n * hw - room) // (n - 1)))
    cards = "".join(f'<span class="h">{back(THEM, "Face-down card")}</span>' for _ in range(n))
    return (f'<span class="rhand" style="--hw:{hw}px;--ov:{ov}px">{cards}'
            f'<span class="rhand__n">Hand <b>{n}</b></span></span>')


def seatbar(side):
    mid = their_hand() if side == THEM else ""
    return (f'<div class="seatbar">{who(side)}{initiative(side)}{mid}'
            f'<span class="state">{pips(side)}{piles(side)}</span></div>')


def hpbar(side):
    s = SEATS[side]
    remain = s["base_hp"] - s["base_dmg"]
    wound = round(100 * s["base_dmg"] / s["base_hp"])
    crit = " hpbar--critical" if remain <= 8 else ""
    return (f'<span class="hpbar{crit}" style="--wound:{wound}%"><span class="hpbar__fill"></span>'
            f'<b><span>Base</span><s>{remain}<i> / {s["base_hp"]}</i></s></b></span>')


def lbcard(kind, side):
    cid, name = SEATS[side][kind]
    return f'<span class="lbcard">{art(cid, name)}</span>'


def tabs(side):
    """Fortified / Arrested live on the LEADER side of the base. Nothing is fortified or arrested in
    this state, so the row renders as nothing — but it exists, and the geometry allows for it."""
    return '<span class="tabs"></span>'


def centre(side):
    """Bases face each other across the table edge, leaders outboard. The health bar takes the
    midline side of the base; the tabs take the leader side."""
    if side == THEM:
        parts = (lbcard("leader", THEM), tabs(THEM), lbcard("base", THEM), hpbar(THEM))
    else:
        parts = (hpbar(ME), lbcard("base", ME), tabs(ME), lbcard("leader", ME))
    return '<div class="lb">' + "".join(parts) + "</div>"


def row(side):
    return (f'<div class="row">{field(BOARD[side]["space"])}{centre(side)}'
            f'{field(BOARD[side]["ground"])}</div>')


HAND_ROOM = 880   # px left of the action buttons, measured off the 1600-wide board


def hand(cards, hw=132, room=HAND_ROOM):
    """Overlap is solved, not fixed: each card after the first slides left by however much it takes
    to fit `room`, and never less than 12px. Cards overlap RIGHTWARD, so every card's left edge —
    its cost pip and the start of its title — stays visible under the one in front of it."""
    n = len(cards)
    ov = 12 if n < 2 else max(12, -(-(n * hw - room) // (n - 1)))
    return (f'<div class="hand" style="--hw:{hw}px;--ov:{ov}px">'
            + "".join(f'<span class="h is-playable">{sq(c, "Hand card")}</span>' for c in cards)
            + "</div>")


def stream():
    out = []
    for item in STREAM:
        if item[0] == "sys":
            out.append(f'<p class="sys">{item[1]}</p>')
        else:
            out.append(f'<p><b class="{item[0]}">{e(item[1])}</b> {item[2]}</p>')
    return "".join(out)


def sidebar():
    return (f'<aside class="side pa-glass">'
            f'<div class="side__top"><span class="turn">'
            f'<span class="turn__r"><span>Round</span><b>{ROUND}</b></span>'
            f'<span class="turn__p">{e(PHASE)} phase</span></span>'
            f'<button class="btn act act--sm" type="button">Undo</button></div>'
            f'<span class="lbl">Game log &amp; chat</span>'
            f'<div class="log">{stream()}</div>'
            f'<div class="chatbar"><input placeholder="Message&hellip;" aria-label="Message">'
            f'<button class="btn act act--sm" type="button">Send</button></div></aside>')


def acts():
    return ('<div class="acts"><button class="btn act" type="button">Keep initiative</button>'
            '<button class="btn act act--go" type="button">Pass</button></div>')


BOARDS = "/TCGEngine/Assets/Boards/SWUSim"      # the `background` cosmetic
MATS   = "/TCGEngine/Assets/Playmats/SWUSim"    # the `playmat` cosmetic, per seat, default none


def board(background="default", mat_me=None, mat_them=None):
    """`background` is the viewer's own cosmetic; the two playmats are PER SEAT, so a board can show
    two different mats at once. Default cosmetics = the default background and no mats at all."""
    bg = f' style="--board-art:url(&quot;{BOARDS}/{background}.webp&quot;)"'
    m = lambda a: (f'<span class="mat" style="--mat:url(&quot;{MATS}/{a}.webp&quot;)"></span>'
                   if a else "")
    return (f'<div class="board"{bg}>'
            f'<div class="far">{m(mat_them)}{seatbar(THEM)}{row(THEM)}</div>'
            f'<div class="edge"></div>'
            f'<div class="near">{m(mat_me)}{row(ME)}{seatbar(ME)}</div>'
            f'<div class="handrow">{hand(BOARD[ME]["hand"])}{acts()}</div>'
            f'{sidebar()}</div>')



CHANGES = [
    ("kept", "Kept", "Leaders and bases <em>centred</em>; the base <em>health bar</em>; "
                     "<em>resource pips</em>; player <em>names off to the side</em>; initiative as a "
                     "<em>chamfered chip</em>; <em>action buttons bottom right</em>; full card art "
                     "for units in play."),
    ("", "Square slots", "Card backs in <em>Assets/CardBacks/SWUSim/</em> are square (400&times;400) "
                         "and player-chosen, so every slot that can hold a face-down card is square "
                         "or the back distorts: the hand, the deck, and the discard beside it. Their "
                         "hand is now that same square slot holding the real back. Units in play are "
                         "never face down, so they keep the whole portrait card."),
    ("", "Shipped counters", "The black stat bar is gone. Power reads from the red chip at the "
                             "bottom-left, HP from the blue chip at the bottom-right, damage from the "
                             "centred disc: the three counters the board already uses, where players "
                             "already look, with the <em>+N</em> modifier under each number."),
    ("", "Subcards below", "Attachments band out <em>under</em> the unit, as they do on the live "
                           "board, and the power and HP chips belong to the bottom of the whole group "
                           "rather than to the card. Shield stays a marker on the art, because it is a "
                           "state and not a card."),
    ("", "Bar above base", "The health bar moved to the <em>midline side</em> of the base on both "
                           "halves, which leaves the leader side free for the Fortified and Arrested "
                           "tabs. Nothing is fortified here, so that row renders as nothing; the "
                           "space is reserved, not advertised."),
    ("", "Credits", "A gold pip row under the resources: same pip, different metal, so the two "
                    "economies are one shape you glance at rather than two widgets you decode. Gold "
                    "is now spent on exactly three things: initiative, the primary action, and "
                    "credits."),
    ("", "Pile cascade", "Deck and discard read <em>label, image, count badge</em>, stacked, under "
                         "the resources and above the actions. The right edge of the board is one "
                         "column: resources, credits, piles, actions."),
]


def main():
    changes = "".join(f'<li class="{c}"><b>{t}</b><span>{body}</span></li>' for c, t, body in CHANGES)

    doc = f"""<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>The Table &mdash; SWUSim gameboard</title>
<!-- ⚠ MOCKUP, NOT SHIPPED UI. Generated by build.py — edit that, not this file.
     Concept 8 of ../gameboard-8/, reworked against the owner's notes. Same game state as that
     sheet: SWUSim/Tests/Visual/MobileLayout_FullBoard.md, the fixture behind the reference
     screenshot. The visual world is the shipped Petranaki system, unchanged.
     The empty .home-header below is load-bearing — swusim-menu-2.css scopes the page ground, the
     milled-steel mesh and every --pane token to `body:has(.home-header)`. -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,400..700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/TCGEngine/SharedUI/css/tokens.css">
<link rel="stylesheet" href="/TCGEngine/SharedUI/css/components.css">
<link rel="stylesheet" href="/TCGEngine/SharedUI/Sites/SWUSim/css/swusim-menu-2.css">
<link rel="stylesheet" href="/TCGEngine/SharedUI/Sites/SWUSim/css/petranaki-glass.css">
<link rel="stylesheet" href="./depth.css">
</head>
<body>
<div class="home-header"></div>
<div class="page">
  <header class="masthead">
    <h1>The Table</h1>
    <p>Concept&nbsp;8, fourth pass. It renders the same board as before (Vader and Amnesty Housing
    against Luke and Data Vault, seven Battle Droids, Grogu on five damage), so it sits directly
    against the eight-concept sheet and against the live board with nothing else changed.</p>
    <p><b>The Z-axis recession is gone.</b> Concept&nbsp;8 originally tipped the opponent's half
    away, smaller and dimmer, so depth carried ownership. Both halves are now the same board at the
    same scale under the same light, and ownership rests on position across the lit table edge plus
    the two labelled seat rails. No seat tint has been added back &mdash; say the word if you want
    the cool/warm pair from the concept sheet instead.</p>
    <ul class="changes">{changes}</ul>
  </header>
  <p class="caption"><b>Every square on this board is a <code>concat/</code> rendering</b> &mdash; the
  card with its rules box removed, 450&times;450, which is what the live board already uses for units
  in play. Because those are square, the printed power and HP gems land at fixed bottom corners on
  every card, so the live counters sit <em>on</em> them rather than beside them. Leaders and bases
  stay whole, because they are landscape. Units run at 124px, hand cards at 132px. The centre column is the only fixed width; both fields take everything
  else, which is what lets seven Battle Droids sit in two clean rows instead of clipping.
  <b>Credit counts are demonstration values</b>: Credits are not in this fixture, and the gold row
  has to be shown holding something.</p>
  {board()}
  <p class="caption"><b>The same board under a different set of cosmetics.</b> SWUSim has three
  cosmetic slots and this design had only accounted for two. Below: the <em>Death Star</em>
  background instead of the default, and a <em>playmat</em> on each half &mdash; Home One for you,
  SOR key art for Drixx. Playmats are per seat, default to none, and the viewer can switch them off
  entirely, so every one of these four combinations has to read.</p>
  {board("death-star", mat_me="home-one", mat_them="sor-key-art")}
</div>
</body>
</html>
"""
    out = pathlib.Path(__file__).with_name("index.html")
    out.write_text(doc, encoding="utf-8")
    print(f"wrote {out}  ({len(doc):,} bytes)")


if __name__ == "__main__":
    main()
