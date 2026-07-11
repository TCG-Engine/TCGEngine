# SHD_052 Sugi — "While an enemy unit is upgraded, this unit gains Sentinel." Guard test for the
# existing HasConditionalKeyword_Sentinel case (implemented, previously untested). Enemy marine
# wears an upgrade → Sugi has Sentinel.

## GIVEN
CommonSetup: bbw/bbw
WithP1GroundArena: SHD_052:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
