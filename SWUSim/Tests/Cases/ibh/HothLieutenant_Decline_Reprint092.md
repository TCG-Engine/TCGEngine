# IBH_092 Hoth Lieutenant (reprint of IBH_064) — the attack is optional. Decline → no attack happens.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: IBH_092
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:READY
P1NODECISION
