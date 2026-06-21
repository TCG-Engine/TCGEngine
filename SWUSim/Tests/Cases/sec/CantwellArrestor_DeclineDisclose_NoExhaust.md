# SEC_037 Cantwell Arrestor Cruiser — decline the optional disclose → no exhaust, no lock.
# Fodder is in hand (so disclose IS offered), but P1 declines (AnswerDecision:-); the enemy SOR_046
# stays READY and unlocked.

## GIVEN
CommonSetup: bbk/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SEC_037
WithP1Hand: SEC_054
WithP1Hand: SEC_080
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_037
P2GROUNDARENAUNIT:0:READY
P1NODECISION
