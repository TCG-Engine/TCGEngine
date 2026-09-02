# VISUAL CHECK — MOBILE home seat rows: arena counts on row A, zone counts on row B
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor, then switch to the mobile layout —
# append &swuLayout=mobile to the board URL (SWUSimIsMobileRequest is user-agent / that param only;
# it never keys off viewport width, so narrowing a desktop window will NOT show you this layout).
#
# WHY THIS FILE EXISTS. The mobile home view is a different renderer from the desktop tiles
# (swuRenderSeatRow vs swuRenderMiniBoard) with its own two-line shape, so nothing about the desktop
# check covers it. The rows are split by KIND:
#
#   row A   P{n} · leaders · base · fx · [Ground / Space, stacked] · … · pills · [Zoom]
#   row B   Res · Hand · Deck · Discard
#
# Row B previously carried all six counts and wrapped at phone width. Ground/Space moved up rather
# than anything being dropped, so every number survives and the row stops wrapping.
#
# What to look at:
#   • Ground and Space are STACKED as two chips on row A, clustered immediately RIGHT of the base's
#     fortify/arrest bubbles — NOT floated over against the magnifier. The gap on this row falls
#     between them and the turn/initiative pills, which keep the auto margin that right-aligns the
#     zoom button.
#   • They must be stacked, not side by side — at 430px a side-by-side pair pushes the zoom off the row.
#   • Row A must be no taller than it was: the stacked pair is the same height as the leader/base
#     thumbnails beside it, so it costs no vertical space. That was the whole point of the change.
#   • Row B must be ONE line — four chips, no wrap — at 430px AND at the narrowest phone you can set.
#     The Discard label is spelled in FULL here (it was abbreviated "Disc" while row B still carried
#     six chips); that full word is the widest thing on the row, so this is the check that guards it.
#   • The four seats' numbers are all different, so a row rendering another seat's block is obvious.
#   • Compare against the desktop tile for the same board: the two renderers read the SAME seat block
#     and must never disagree about a count.
#
#   seat 2   Res 4/4  Hand 3  Deck 5  Discard 1  Ground 2  Space 1
#   seat 3   Res 1/1  Hand 1  Deck 2  Discard 4  Ground 1  Space 0
#   seat 4   Res 3/3  Hand 0  Deck 1  Discard 2  Ground 0  Space 2
#
# (`WithPnResources: N` seeds N resources ALL READY, hence N/N.)

## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithP2Resources: 4
WithP2Hand: [SOR_095 SOR_046 SOR_143]
WithP2Deck: [SOR_095 SOR_046 SOR_143 SOR_157 SOR_051]
WithP2Discard: [SOR_046]
WithP2GroundArena: SOR_157:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_141:1:0
WithP3Resources: 1
WithP3Hand: [SOR_095]
WithP3Deck: [SOR_095 SOR_046]
WithP3Discard: [SOR_046 SOR_143 SOR_157 SOR_051]
WithP3GroundArena: SOR_095:1:0
WithP4Resources: 3
WithP4Deck: [SOR_095]
WithP4Discard: [SOR_046 SOR_143]
WithP4SpaceArena: SOR_141:1:0
WithP4SpaceArena: SOR_044:1:0

## WHEN

## EXPECT
SEATCOUNT:4
P2HANDCOUNT:3
P2DECKCOUNT:5
P2DISCARDCOUNT:1
P3HANDCOUNT:1
P3DECKCOUNT:2
P3DISCARDCOUNT:4
P4HANDCOUNT:0
P4DECKCOUNT:1
P4DISCARDCOUNT:2
