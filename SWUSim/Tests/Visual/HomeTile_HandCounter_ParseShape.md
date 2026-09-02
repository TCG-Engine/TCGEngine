# VISUAL CHECK — the Hand counter parses like the DISCARD, not like the DECK
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor. Desktop layout.
#
# WHY THIS FILE EXISTS. Hand and Deck are BOTH hidden zones, so the obvious assumption is that they
# serialize the same way and can be counted the same way. They do not:
#
#   Deck  -> ONE entry, "CardBack <count>"          => the count is FIELD 1 of a single entry
#   Hand  -> ONE ENTRY PER CARD, "CardBack 0 -"     => the count is the NUMBER OF ENTRIES
#
# So the deck idiom applied to the hand parses the literal `0` out of the first CardBack and reports
# EVERY opponent as holding zero cards. That bug is invisible on a board where hands happen to be
# empty, and it is invisible to the regression suite, which asserts engine state and never looks at
# the tile. Hence this board, where the two zones are deliberately mismatched in both directions:
#
#   seat 2   Hand 5 · Deck 1     <- a big hand and a nearly empty deck
#   seat 3   Hand 0 · Deck 9     <- an EMPTY hand and a big deck
#
# What to look at:
#   • Seat 2 must read "Hand 5", not "Hand 0" and not "Hand 1". A deck-style parse shows 0; reading
#     the deck's zone by mistake shows 1. The two wrong answers are distinguishable from each other
#     and from the right one, which is the point of these numbers.
#   • Seat 3 must read "Hand 0" — and still SHOW the chip. The counter has no ShowZero suppression;
#     "this opponent is empty-handed" is exactly the fact a Twin Suns player wants at a glance, and a
#     chip that vanishes at zero is indistinguishable from a chip that failed to render.
#   • Neither Hand chip is clickable. The Discard chip beside it opens that seat's pile because the
#     discard is PUBLIC; the hand is hidden and must never open.

## GIVEN
CommonSetup3P: rrk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP2Hand: [SOR_095 SOR_046 SOR_143 SOR_157 SOR_051]
WithP2Deck: [SOR_095]
WithP2Discard: [SOR_046 SOR_143]
WithP3Deck: [SOR_095 SOR_046 SOR_143 SOR_157 SOR_051 SOR_097 SOR_065 SOR_032 SOR_141]
WithP3Discard: [SOR_046]

## WHEN

## EXPECT
SEATCOUNT:3
P2HANDCOUNT:5
P2DECKCOUNT:1
P2DISCARDCOUNT:2
P3HANDCOUNT:0
P3DECKCOUNT:9
P3DISCARDCOUNT:1
