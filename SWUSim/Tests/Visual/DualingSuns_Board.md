# VISUAL CHECK — Dualing Suns board controls (desktop and phone), 2026-11-13
#
# Visual-only schema. Dualing Suns (docs/superpowers/specs/2026-11-12-swusim-dualing-suns-design.md) is a 1v1 Twin Suns game:
# each round has two counters, Initiative and Plan, taking one ends your round, and there is no Pass while one is there.
# Stealing the initiative (the opponent holds it) costs 1 damage to your own base. The board shows this through the
# server's actions data (planAvailable, blastAvailable, passAvailable, initiativeToll), on a TWO-seat layout that never
# showed Plan before. PHONE: &swuLayout=mobile. Check Chromium, Firefox AND Safari/WebKit.
# Automated: DevTools/ui-harness/swusim-dualingsuns-board-xbrowser.mjs (measures the four points below, saves screenshots).
#
# ── WHAT TO LOOK AT (P1's view, P1 to act, P2 holds the initiative) ─────────────────────────────────────────────
#   1. "Take Initiative" and "Plan" are both showing in P1's control cluster, and nothing overlaps them. (Wrong: Plan
#      missing — the 2-seat board hid the Twin Suns counters before Dualing Suns.)
#   2. NO Blast button. Dualing Suns has no Blast counter.
#   3. NO Pass button: a counter is still there to take.
#   4. Hovering Take Initiative says "Take the initiative — costs 1 damage to your base (your opponent holds it)".
#      (Wrong: the plain "Take the Initiative (I)" — the toll would come as a surprise.)
#   5. The rest of the board is the ordinary 1v1 board: two leaders per seat, bases facing each other, no Twin Suns
#      seat strip.
#
# ── BOARD SHAPE ──────────────────────────────────────────────────────────────────────────────────────────────────
#   Two leaders per seat (a Twin Suns deck runs two). One unit per side so the arenas are not empty. P2 holds the
#   initiative so P1's take is a STEAL — the case the tooltip exists for.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithCardPool: dualingsuns
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: false
WithP1Hand: SOR_095
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN

## EXPECT
SEATCOUNT:2
TURNPLAYER:1
P1PLANAVAIL:1
P1BLASTAVAIL:0
P1PASSAVAIL:0
