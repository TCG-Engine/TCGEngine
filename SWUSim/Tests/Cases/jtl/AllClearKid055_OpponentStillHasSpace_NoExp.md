# JTL_055 You're All Clear, Kid (event) — the Experience rider only applies if the opponent controls NO
# space units afterward. P1 defeats SOR_225 (1 remaining HP), but JTL_069 (4/7, 7 remaining HP — not a
# legal target) remains, so no Experience is offered.

## GIVEN
P1LeaderBase: JTL_004/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_055
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:JTL_069
P1GROUNDARENAUNIT:0:POWER:3
P1NODECISION
