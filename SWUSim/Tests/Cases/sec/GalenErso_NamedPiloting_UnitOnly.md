# SEC_046 Galen Erso — naming a Piloting card denies the keyword, so it can only be played as a unit
# (no Unit/Pilot choice). P2 controls a Vehicle (JTL_069), so normally playing JTL_034 (Interceptor Ace,
# Piloting) would prompt Unit-or-Pilot. P1 names "Interceptor Ace"; P2 plays it and it enters as a ground
# unit directly, with no Unit/Pilot decision.

## GIVEN
CommonSetup: bbw/bbk
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2Resources: 8
WithP2Hand: JTL_034
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Interceptor Ace
- P2>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:JTL_034
P2NODECISION
