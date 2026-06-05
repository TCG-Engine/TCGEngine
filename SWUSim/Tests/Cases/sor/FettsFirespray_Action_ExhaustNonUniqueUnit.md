# SOR_184 Fett's Firespray — Action [2 resources]: Exhaust a non-unique unit. Firespray (in play, no
# self-exhaust cost) pays 2 resources to exhaust the non-unique enemy SOR_046; Firespray stays READY.

## GIVEN
P1LeaderBase: SOR_016/SOR_025
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_184:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Resources: 3

## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:0:READY
P1RESAVAILABLE:1
