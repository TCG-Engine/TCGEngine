# SOR_097 Admiral Ackbar — the When Played damage is optional ("You may"). Declining (AnswerDecision:-)
# deals nothing.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_097

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
