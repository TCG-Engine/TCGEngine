# VISUAL CHECK — Saboteur keyword icon on units (printed + granted)
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor to confirm the Saboteur unit icon.
#
# The saboteur.webp icon shows (bottom, like Coordinate) on any arena unit that has the
# Saboteur keyword — printed OR granted. P1's ground arena holds, in order:
#   0  SOR_194 Rogue Operative      printed Saboteur            -> icon SHOWS (also has XP + shield tokens)
#   1  SOR_095 Battlefield Marine   none                        -> NO icon
#   2  SOR_095 + SOR_166 Infiltrator     upgrade grants Saboteur -> icon SHOWS
#   3  SOR_095 + LOF_215 Ascension Cable upgrade grants Saboteur -> icon SHOWS
#   4  SOR_049 Obi-Wan Kenobi (Force unit)  none yet             -> NO icon until LOF_152 is played
#
# P1's hand holds LOF_152 Focus Determines Reality: "Each friendly Force unit gains Raid 1
# and Saboteur for this phase." Play it to visualize the Force unit (#4) gaining the icon.
#
# What to look at:
#   • Units 0, 2, 3 show the saboteur icon at the bottom (printed + two upgrade grants).
#   • Unit 1 shows nothing; unit 4 shows nothing until you play LOF_152 from hand.
#   • No WHEN steps — the initial GIVEN state is the whole check (then play LOF_152 manually).

## GIVEN
CommonSetup: bbk/grw
WithP1GroundArena: SOR_194:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_049:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArenaUpgrade: 2:SOR_166
WithP1GroundArenaUpgrade: 3:LOF_215
WithP1Hand: LOF_152
WithP1Resources: 10

## WHEN

## EXPECT
P1GROUNDARENACOUNT:5
P1GROUNDARENAUNIT:1:CARDID:SOR_194
P1HANDCOUNT:1
