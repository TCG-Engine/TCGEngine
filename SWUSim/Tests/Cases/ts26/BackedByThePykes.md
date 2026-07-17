# ExpThenDamageEqualsExpCount
#// TS26_58 Backed by the Pykes (Event, cost 3, Command) — Give an Experience token to a friendly unit,
#// then you may deal damage to a unit equal to the number of Experience tokens on friendly units.
#// SEC_080 starts with 1 Experience; giving it a second makes 2 Experience on friendlies → deal 2 to the
#// enemy LAW_124.
## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:TS26_58}
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArena: LAW_124:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENAUNIT:0:DAMAGE:2
