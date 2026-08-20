# VISUAL CHECK — Twin Suns: every base/leader state on ONE seat at once
#   the Force · 3 fortifications · 2 arrests · Epic Action used · 16 base damage (DOUBLE DIGIT)
#
#   Open as playerID=2, so P1 (the loaded seat) is a preview TILE rather than your own board:
#   http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=N&playerID=2
#
# WHAT TO LOOK AT — P1's tile, on the BASE thumbnail specifically. Three things share that one small
# card and they must not collide:
#   • The BASE THUMBNAIL carries only card-intrinsic state: damage CENTRED (16 here — double digit,
#     the case that overflows a token sized for one), the FORCE token top-RIGHT, and EPIC-ACTION-USED
#     bottom-RIGHT. Same corners the full board's base card uses.
#   • EFFECT COUNTS sit in a separate THREE-ROW COLUMN between the base and the Zoom-in button, filled
#     top-down in arrival order: grey = Fortify, goldenrod = Arrest/Capture. The third row stays empty
#     as headroom for a future effect.
#     ⚠ That column is a FIXED 26x46 on EVERY tile, empty or not (seats with nothing show three blank
#     rows). If it collapsed when a seat had no effects, row 1 would differ per tile.
#   • Row 1 must stay EVEN across tiles — the pips are overlaid, never beside the base, so the first
#     leader's offset must be equal on every tile (29px at 1700x1100). A loaded seat whose row 1 is
#     wider than the others is the bug this guards.
#   • P3/P4 carry none of it: no pips, and P3's base shows a single-digit 5 as the contrast case.
#
# ⚠ TWO OF THE FIVE STATES ARE NOT SHOWN ON TILES, deliberately — do not read their absence as a bug:
#   • Every one of these five now shows ON THE TILE: the base's Force (top-right) and epic-used
#     (bottom-right), an epic-used icon on EACH of the two leaders (bottom-right of each), the damage,
#     and the effect counts in the column. Nothing about this fixture is full-board-only any more.
#   • ⚠ Mini-board cards are CSS background-image spans, not Card() renders, so none of these icons
#     come for free — each is emitted explicitly. A new card-state icon on the full board will NOT
#     appear here until it is added to swuRenderMiniBoard as well.
#   • ⚠ SIZES: leader thumbnail 63x46 with a 13x13 icon; base icons 16x16. The icon must read as a
#     corner marker, not a lid — this file's sibling documents a regression where an override inflated
#     that same icon to 75x75 on an 84px card. Re-measure if you retune the thumbnails.
#
# ⚠ Arrests are SEEDED (WithP{n}BaseCaptive writes the same SWU_BASECAPTIVE GlobalEffects flag the card
# writes). A seat gets ONE action per turn in Twin Suns, and letting the others pass to come back round
# reaches REGROUP, which rescues every captive — so a played multi-arrest board cannot be built here.
#
# ⚠ THIS FIXTURE FOUND AND FIXED A REAL BUG — keep it. The two-leader sizing block in
# GameLayoutShared.php matched `[data-mzid] img`, i.e. EVERY <img> inside a leader card, and forced
# width:100% !important. That is right for the card ART but also caught the overlays Card() stacks on
# top, beating their inline 22px: on an 84px two-leader card the Epic-Action-Used icon rendered 75x75
# and all but covered the leader. Now scoped to `img[id$="-img"]` — Card() gives the art that id and
# the overlays have none. 2P never showed it: the block only matches a wrapper holding a SECOND leader.
# ⚠ REGRESSION CHECK: leader card 84x84, epic icon 22x22 (was 75x75), in BOTH engines. If a future
# overlay (a new counter, a status pip) is added to a leader, re-measure — a bare `img` selector here
# will silently inflate it again.
#
# VERIFIED 2026-08-19, Chromium + Firefox at 1700x1100.
# WebKit NOT covered: it does not launch on this machine.

## GIVEN
#// Two leaders + a base per seat, per the Twin Suns fixture rule. The trailing 1 on BOTH leader specs
#// (CARDID:ready:deployed:epicUsed) marks each leader's Epic Action used, and myBaseEpicUsed does the
#// same for the base — so all three of P1's epic actions are spent.
#// ⚠ Base damage via myBaseDamage — myBase:ID:damage silently drops it.
CommonSetup: rrk/bbw/{myLeader:IBH_053:1:0:1; myLeader2:SHD_011:1:0:1; theirLeader:SHD_007; theirLeader2:SHD_010; myBaseEpicUsed:true; myBaseDamage:16}
WithSeatOrder: 1234
P1OnlyActions: true
WithP1Force: true
WithP1BaseUpgrade: HMW_081
WithP1BaseUpgrade: HMW_171
WithP1BaseUpgrade: HMW_205
WithP1BaseCaptive: SOR_095
WithP1BaseCaptive: SOR_046
WithP3Leader:  SHD_014
WithP3Leader2: SHD_015
WithP3Base:    SOR_026:5
WithP4Leader:  TWI_009
WithP4Leader2: TWI_010
WithP4Base:    SOR_026:8

## WHEN

## EXPECT
SEATCOUNT:4
P1BASEUPGRADECOUNT:3
P1BASECAPTIVECOUNT:2
P1BASEDMG:16
