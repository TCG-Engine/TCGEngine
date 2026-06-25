# VISUAL CHECK — Grit keyword icon on a unit
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor to confirm the Grit unit icon.
#
# The grit.webp icon shows (bottom, like the other keyword icons) on any arena unit that
# has the Grit keyword (printed or granted). P1's ground arena holds:
#   SOR_065 Baze Malbus        (Grit)   -> icon SHOWS, bottom
#   SOR_095 Battlefield Marine (none)    -> NO icon
#
# What to look at:
#   • SOR_065 shows the animated grit icon at the bottom of the card.
#   • SOR_095 shows no grit icon.
#   • No WHEN steps — the initial GIVEN state is the whole check.

## GIVEN
CommonSetup: bbk/grw
WithP1GroundArena: SOR_065:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_065
