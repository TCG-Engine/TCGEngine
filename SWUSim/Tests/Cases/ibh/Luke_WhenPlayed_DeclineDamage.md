# IBH_020 Luke Skywalker — the When Played damage is optional ("you may"). Decline → no damage.

## GIVEN
CommonSetup: ggw/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: IBH_020
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
