# CostReductionAndDebuffOthers
#// TS26_036 Tribunal (Unit 6/8 space, cost 10) — costs 2 less per other card played this phase; When
#// Played: give each OTHER unit -2/-2 for this phase. P1 first plays a cheap event (Take Action, dealing
#// 3 to the enemy LAW_124), so Tribunal costs 10 - 2 = 8 — only affordable because of the discount (13
#// 11 resources - 3 Take Action = 8 left, exactly Tribunal's discounted cost — without the -2 discount
#// Tribunal (10) would be unaffordable, so it playing at all proves the discount). On entry every OTHER
#// unit gets -2/-2 (Tribunal itself is excluded): friendly SEC_080 (3/3) → power 1; enemy LAW_124 (4/7)
#// → power 2; Tribunal stays 6 power.
## GIVEN
CommonSetup: byk/rrk/{myResources:11}
WithP1Hand: [TS26_071 TS26_036]
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: LAW_124:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:POWER:6
