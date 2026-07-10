# SHD_036 First Light — "Grit. Each other friendly non-leader unit gains Grit." With First Light in the
# space arena, the friendly SOR_046 (3/7, 2 damage) gains Grit → +2 power (5), and shows the keyword.

## GIVEN
CommonSetup: bbk/bbk
P1OnlyActions: true
WithP1SpaceArena: SHD_036:1:0
WithP1GroundArena: SOR_046:1:2

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:5
