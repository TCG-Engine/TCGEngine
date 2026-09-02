# VISUAL CHECK — the new Hand chip still FITS: three desktop tiles side by side at four seats
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor. Desktop layout — resize the window narrow as well as wide.
#
# WHY THIS FILE EXISTS. Adding the Hand chip made row 2 FIVE elements wide (Res · Hand · Deck ·
# Discard · pills). Four seats is the worst case for that: the home view puts THREE opponent tiles
# side by side, so each tile gets roughly a third of the window and row 2 has the least room it will
# ever have. A chip that overflows or wraps here is the regression this change is most likely to
# cause, and it will not show on the two-opponent boards the other checks use.
#
# The tiles are also a COMPARISON view — the reason the Res chip has a fixed-width value box and the
# turn/initiative pills were moved off row 1. So the failure to look for is not only "it overflows"
# but "it no longer lines up": if one seat's chips sit at different x positions from another's, the
# row has started sizing to its content and the comparison is broken.
#
#   seat 2   Res 6/6  Hand 9  Deck 12  Disc 7     <- the widest case: two-digit deck, big hand
#   seat 3   Res 1/1  Hand 0  Deck 1   Disc 0     <- the narrowest case, same row shape
#   seat 4   Res 3/3  Hand 4  Deck 5   Disc 2
#
# What to look at:
#   • Every tile shows all four chips plus the pills, on ONE line, with nothing clipped at the tile's
#     right edge and no horizontal scrollbar on the tile.
#   • The chips line up ACROSS the three tiles — Res starts at the same offset in each, and Hand
#     starts at the same offset in each, despite seat 2 holding two-digit counts and seat 3 holding
#     single digits. Two-digit Deck 12 is there precisely to push on that.
#   • Seat 3 shows "Hand 0" and "Disc 0" rather than hiding them; an empty opponent is information.
#   • Then narrow the browser window and watch row 2 down to the point the sidebar clamps. The chips
#     may shrink, but they must not wrap to a second line or slide under the Zoom-in button.

## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithP2Resources: 6
WithP2Hand: [SOR_095 SOR_046 SOR_143 SOR_157 SOR_051 SOR_097 SOR_065 SOR_032 SOR_141]
WithP2Deck: [SOR_095 SOR_046 SOR_143 SOR_157 SOR_051 SOR_097 SOR_065 SOR_032 SOR_141 SOR_044 SOR_237 SOR_102]
WithP2Discard: [SOR_095 SOR_046 SOR_143 SOR_157 SOR_051 SOR_097 SOR_065]
WithP2GroundArena: SOR_157:1:0
WithP3Resources: 1
WithP3Deck: [SOR_095]
WithP4Resources: 3
WithP4Hand: [SOR_095 SOR_046 SOR_143 SOR_157]
WithP4Deck: [SOR_095 SOR_046 SOR_143 SOR_157 SOR_051]
WithP4Discard: [SOR_046 SOR_143]
WithP4SpaceArena: SOR_141:1:0

## WHEN

## EXPECT
SEATCOUNT:4
P2HANDCOUNT:9
P2DECKCOUNT:12
P2DISCARDCOUNT:7
P3HANDCOUNT:0
P3DECKCOUNT:1
P3DISCARDCOUNT:0
P4HANDCOUNT:4
P4DECKCOUNT:5
P4DISCARDCOUNT:2
