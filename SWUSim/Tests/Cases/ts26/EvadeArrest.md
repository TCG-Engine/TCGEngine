# ExhaustNonUniqueUnits
#// TS26_82 Evade Arrest (Event, cost 3, Cunning) — Exhaust any number of non-unique units. Both
#// non-unique units chosen are exhausted.
## GIVEN
CommonSetup: yyk/rrk/{myResources:3;handCardIds:TS26_82}
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
