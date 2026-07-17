# ReadyThenDeal3
#// TS26_72 Fervor (Event, cost 5) — Ready a unit. Deal 3 damage to a unit.
#// Ready the friendly exhausted SEC_080, then deal 3 to the enemy LAW_124 (4/7, survives at 3 damage).
## GIVEN
CommonSetup: rrk/rrk/{myResources:5;handCardIds:TS26_72}
WithP1GroundArena: SEC_080:0:0
WithP2GroundArena: LAW_124:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:DAMAGE:3
