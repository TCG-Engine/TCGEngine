# VISUAL CHECK — THE EXTREME TILE: every piece of mini-unit chrome at once
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor and run the WHEN steps IN ORDER. Desktop layout.
#
# WHY THIS FILE EXISTS. The home tile grew one feature at a time — keyword badges, then overlays, then
# the upgrade badge, then live power/HP, then turn effects — and each was verified against the board as
# it stood at the time. That is exactly how a layout ends up broken only in the state nobody built:
# every layer passing alone while three of them land on the same 90px card in a real game. This board
# is that state, deliberately over-stuffed. It is NOT a realistic game (nobody holds this) but every
# piece of it is LEGAL, so it stays a reference for what the UI must show.
#
# ⚠ THE CORNER BUDGET IS FULL. top-left = turn effects · top-right = attached upgrades · centre =
#   damage · bottom-left / bottom-right = live power / HP · bottom edge = keyword badges (hanging
#   proud) · whole card = Sentinel / Hidden overlays. There is no seventh free spot: anything added
#   after this must SHARE, and this file is where that gets decided.
#
# ── P2 ground 0 — THE MAXIMAL UNIT (IBH_056 Ground Assault AT-AT, printed 5/7) ───────────────────
# One card carrying SIX layers simultaneously. If any pair of them ever collides, it shows here first:
#     purple "2"  top-left      2 distinct effect sources (Make an Opening, Benthic)
#     white  "8"  top-right     6 upgrades + 1 unit pilot + 1 captive
#     red    "5"  centre        damage
#     "10" / "12" bottom corners  LIVE power/HP — printed is 5/7, so BOTH stats are modified
#     3 keyword badges          Bounty · Raid · Restore  (Raid is the granted one, from Benthic)
#     EXHAUSTED                 rotate(8deg) scale(0.886) + the brightness dim, which every layer
#                               above inherits — check none of them is sliced by the tilt
#
# ── the rest of the board, each covering something the maximal unit cannot ───────────────────────
#   P2 ground 1  SOR_156 Benthic          2/2, exhausted, NOTHING else — the effect SOURCE carries no
#                                          badge of its own (a source is not a target)
#   P2 ground 2  TWI_106 Coruscant Guard  Coordinate ACTIVE (this seat controls 3+ units) + Ambush
#   P2 ground 3  SOR_229 Cell Block Guard SENTINEL overlay + damage, and NO keyword badge for it —
#                                          Sentinel is an Overlay in the schema, not a Counter
#   P2 ground 4  LOF_107 Village Tender   HIDDEN smoke overlay (it was PLAYED this phase — a unit
#                                          placed by a With… line has HiddenUnattackable 0 and draws
#                                          no smoke) + Hidden/Restore badges
#   P2 SPACE  0  ASH_083 Summa-verminoth  TWO-DIGIT damage (12) AND two-digit live stats (16/16),
#                                          printed 15/15, under a Sentinel overlay
#   P2 SPACE  1  SOR_090 Devastator       a PILOT LEADER — printed 10/10, live 16/14. Its panel must
#                                          show Asajj's _back (UNIT) side, not her leader front
#   P3 ground 0  TWI_106 Coruscant Guard  Coordinate INACTIVE — the same card as P2 ground 2 showing
#                                          the OTHER icon. They are mutually exclusive
#   P3 ground 1  SOR_095 Battlefield Marine  COMPLETELY PLAIN: ready, undamaged, no upgrades, no
#                                          effects, no keywords, live == printed 3/3.
#                                          ⚠ THE CONTROL. Without it, chrome that rendered
#                                          unconditionally would look correct on every card above.
#
# ── and the tile around the units ───────────────────────────────────────────────────────────────
#   • P2's base carries Fortify upgrades AND a captive, so the fx column beside it is populated.
#   • P2's zone counts are two digits (Deck 12, Discard 11) — the widest the row-2 chips ever get.
#   • Seat 2 holds the initiative and a seat has the turn, so both PILLS render on row 2.
#
# What to look at:
#   • On the maximal unit, all six layers readable and none overlapping another. The keyword badges and
#     the bottom-corner stat tokens are the closest pair by design — their BOXES may touch, but the
#     badges are circles that nest between the two tokens, so no ink should be hidden.
#   • Nothing sliced at a card's edge. The stat tokens sit deliberately proud of the bottom corners and
#     the keyword strip hangs below the card; .swu-mb-row's padding-inline / padding-bottom is the only
#     thing holding that spill, because the arena grid clips (overflow-x:auto forces overflow-y).
#   • No tile spills past its own rounded border — the tile height is fixed between the top of the view
#     and the midline, and it has zero slack.
#   • CLICK the purple badge and the white badge on the maximal unit: two different panels, one listing
#     the effect SOURCES, one listing the 8 attached cards.
#   • Then narrow the window: repeat the checks at a short viewport (the arena drops to one row) and at
#     ~1150px wide, where the cards approach their floor and every fraction-of-a-card size is smallest.
#
# NOT covered here, on purpose — each has its own file:
#   • the targeting / selectable state (TwinSuns_HomeView_MultiSelectFromPreview.md)
#   • the MOBILE seat rows, which have no unit thumbnails at all (HomeTile_MobileRowLayout.md,
#     HomeTile_MobileRowIsClickable.md)

## GIVEN
CommonSetup3P: rrk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithP1Resources: 9
WithP1Hand: [SOR_076]
WithP2Resources: 9
WithP2Hand: [LOF_107]
WithP2Deck: [SOR_095 SOR_046 SOR_143 SOR_157 SOR_051 SOR_097 SOR_065 SOR_032 SOR_141 SOR_044 SOR_237 SOR_102]
WithP2Discard: [SOR_095 SOR_046 SOR_143 SOR_157 SOR_051 SOR_097 SOR_065 SOR_032 SOR_141 SOR_044 SOR_237]
WithP2BaseUpgrade: SOR_070
WithP2BaseUpgrade: SHD_123
WithP2BaseCaptive: SOR_095
WithP2GroundArena: IBH_056:0:5
WithP2GroundArenaUpgrade: 0:SOR_070
WithP2GroundArenaUpgrade: 0:SHD_123
WithP2GroundArenaUpgrade: 0:LOF_053
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaPilot: 0:JTL_046
WithP2GroundArenaCaptive: 0:SOR_046
WithP2GroundArena: SOR_156:1:0
WithP2GroundArena: TWI_106:1:1
WithP2GroundArena: SOR_229:1:2
WithP2SpaceArena: ASH_083:1:12
WithP2SpaceArenaUpgrade: 0:SOR_070
WithP2SpaceArenaUpgrade: 0:SHD_123
WithP2SpaceArena: SOR_090:1:3
WithP2SpaceArenaPilot: 1:JTL_001
WithP3GroundArena: TWI_106:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p2GroundArena-0
- P2>AttackGroundArena:1:p1Base-0
- P3>Pass
- P1>Pass
- P2>PlayHand:0

## EXPECT
SEATCOUNT:3
P2GROUNDARENACOUNT:5
P2SPACEARENACOUNT:2
P3GROUNDARENACOUNT:2
