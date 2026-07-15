# TS26_028 Prime Minister Almec (Unit 2/4, cost 4) — Saboteur. When Played: give a friendly unit +2/+2
# for this phase, then exhaust each enemy unit in its arena with less power than it. Buffing SEC_080 to
# 5/5 exhausts the 3-power SOR_128 (< 5) but leaves the 6-power Fives (>= 5) ready.
## GIVEN
CommonSetup: gyk/rrk/{myResources:4;handCardIds:TS26_028}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: [SOR_128:1:0 TS26_034:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY
