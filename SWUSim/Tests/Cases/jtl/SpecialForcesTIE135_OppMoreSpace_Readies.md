# JTL_135 Special Forces TIE Fighter — When Played: If an opponent controls more space units than you,
# ready this unit. P2 has 2 space units; after JTL_135 enters (P1 has 1), 2 > 1 so it readies.

## GIVEN
P1LeaderBase: JTL_011/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_135
WithP1Resources: 2
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_135
P1SPACEARENAUNIT:0:READY
