# JTL_038 Corvus — "attach a friendly Pilot unit OR upgrade." Here the Pilot (JTL_046 Paige) is ALREADY
# an upgrade on the Vehicle SEC_214. Corvus relocates that pilot upgrade onto itself: SEC_214 stays as a
# unit but loses its pilot, and Corvus gains Paige as a pilot subcard. (The Vehicle SEC_214 in P1's
# ground arena represents "the pilot upgrade on it" in the choose.)

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: JTL_038
WithP1GroundArena: SEC_214:1:0
WithP1GroundArenaUpgrade: 0:JTL_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_038
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_046
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
