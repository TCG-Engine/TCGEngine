# JTL_150 Biggs Darklighter (pilot) — If the attached unit is a Fighter, it gains Overwhelm. The Fighter
# host SOR_237 with the pilot gains Overwhelm.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_150

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Overwhelm
