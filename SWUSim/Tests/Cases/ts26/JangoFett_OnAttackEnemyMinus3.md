# TS26_075 Jango Fett (Unit 5/5, cost 5) — On Attack: give an enemy unit -3/-0 for this phase. Jango
# attacks LAW_124 (4/7); the On-Attack debuff drops its power to 1, so it counters for only 1.
## GIVEN
CommonSetup: yyk/rrk
WithP1GroundArena: TS26_075:1:0
WithP2GroundArena: LAW_124:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:DAMAGE:1
