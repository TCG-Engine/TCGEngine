# SEC_212 Libertine (Space, 3/7, Cunning/Cunning, cost 4) — When Played: choose an enemy unit and a
#   non-leader friendly unit; the enemy unit captures the friendly unit. SOR_046 captures SOR_095.

## GIVEN
CommonSetup: yyk/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_212

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_212
P1NODECISION
