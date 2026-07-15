# TS26_081 Mislead (Event, cost 2, Cunning) — Give a Shield token to a unit; give a unit -3/-0 for this
# phase. Shield the friendly SEC_080, then debuff the enemy LAW_124 (power 4 → 1, HP unchanged).
## GIVEN
CommonSetup: yyk/rrk/{myResources:2;handCardIds:TS26_081}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: LAW_124:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:7
