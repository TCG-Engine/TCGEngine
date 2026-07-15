# TS26_070 Backed by Black Sun (Event, cost 3) — the optional second damage is a "you may", so it
# can be declined (AnswerDecision:-). Only the mandatory 1 damage to the chosen enemy lands.
## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:TS26_070}
WithP2GroundArena: LAW_124:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
