# VISUAL CHECK — Overwhelm keyword icon on a unit
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor to confirm the Overwhelm unit icon.
#
# The overwhelm.webp icon shows (bottom, like Coordinate/Saboteur) on any arena unit
# that has the Overwhelm keyword (printed or granted). P1's ground arena holds:
#   SOR_164 Wampa             (Overwhelm)   -> icon SHOWS, bottom
#   SOR_095 Battlefield Marine (none)        -> NO icon
#
# What to look at:
#   • SOR_164 shows the animated overwhelm icon at the bottom of the card.
#   • SOR_095 shows no overwhelm icon.
#   • No WHEN steps — the initial GIVEN state is the whole check.

## GIVEN
CommonSetup: bbk/grw
WithP1GroundArena: SOR_164:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_164
