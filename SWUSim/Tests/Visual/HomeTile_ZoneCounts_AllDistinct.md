# VISUAL CHECK — home tile zone counts, every number DIFFERENT (the index-drift discriminator)
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor. Desktop layout.
#
# WHY THIS FILE EXISTS. swuReadSeatBlock reads a seat's zones by POSITION out of a stride-31 block of
# window.swuLastResponseArr — Deck=1, Hand=2, Discard=3, Resources=4, Leader=5, Base=6, Ground=7,
# Space=8. Hardcoded indexes into generated positional output rot silently the moment a zone is
# inserted, and the failure is not a crash: every chip still renders a plausible number, just the
# WRONG zone's. A board where two counts happen to be equal cannot show that. So here EVERY count on
# seat 2 is a different value, and seat 3 is a second, differently-shaped board:
#
#   seat 2   Res 5/5   Hand 7   Deck 4   Discard 3   Ground 2   Space 1
#   seat 3   Res 2/2   Hand 1   Deck 6   Discard 8   Ground 4   Space 3
#
# (`WithPnResources: N` seeds N resources ALL READY, so the Res chip reads N/N, not 0/N — the numbers
#  above are what the board actually renders, not what the directive looks like it should give.)
#
# What to look at:
#   • Read the six numbers off each tile and compare them to the table above. Any one of them being
#     another zone's number is an off-by-one in the offsets, not a rendering bug — fix it there.
#   • The two seats are deliberately UNLIKE each other: a renderer that read seat 2's block for both
#     tiles (the stride is (seat-1)*31 — an easy thing to get wrong) shows identical numbers twice.
#   • Hand=7 on seat 2 vs Hand=1 on seat 3 is the pair to check first; see the sibling
#     HomeTile_HandCounter_ParseShape check for why the hand is the fragile one.

## GIVEN
CommonSetup3P: rrk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP2Resources: 5
WithP2Hand: [SOR_095 SOR_046 SOR_143 SOR_157 SOR_051 SOR_097 SOR_065]
WithP2Deck: [SOR_095 SOR_046 SOR_143 SOR_157]
WithP2Discard: [SOR_095 SOR_046 SOR_143]
WithP2GroundArena: SOR_157:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_141:1:0
WithP3Resources: 2
WithP3Hand: [SOR_095]
WithP3Deck: [SOR_095 SOR_046 SOR_143 SOR_157 SOR_051 SOR_097]
WithP3Discard: [SOR_095 SOR_046 SOR_143 SOR_157 SOR_051 SOR_097 SOR_065 SOR_032]
WithP3GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_143:1:0
WithP3GroundArena: SOR_157:1:0
WithP3SpaceArena: SOR_141:1:0
WithP3SpaceArena: SOR_044:1:0
WithP3SpaceArena: SOR_237:1:0

## WHEN

## EXPECT
SEATCOUNT:3
P2HANDCOUNT:7
P2DECKCOUNT:4
P2DISCARDCOUNT:3
P2GROUNDARENACOUNT:2
P2SPACEARENACOUNT:1
P3HANDCOUNT:1
P3DECKCOUNT:6
P3DISCARDCOUNT:8
P3GROUNDARENACOUNT:4
P3SPACEARENACOUNT:3
