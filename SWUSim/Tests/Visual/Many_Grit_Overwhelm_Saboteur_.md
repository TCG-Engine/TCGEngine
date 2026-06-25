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
WithP1GroundArena: SOR_065:1:2
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:LOF_215
WithP1GroundArenaUpgrade: 0:ASH_181
WithP1GroundArenaUpgrade: 0:TWI_051
WithP1SpaceArena: SOR_066:0:0
WithP1SpaceArena: LOF_069:0:3
WithP1Resources: 1:SHD_089:0,1:SEC_036:1,1:SEC_034:1,5:SOR_095:1
WithP1Hand: SOR_095

## WHEN

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_065
