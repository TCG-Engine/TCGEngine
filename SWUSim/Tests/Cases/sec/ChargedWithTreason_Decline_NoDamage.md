# SEC_182 Charged with Treason — decline the disclose → no damage dealt.

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SEC_182
WithP1Hand: SEC_133
WithP1Hand: SEC_133
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
