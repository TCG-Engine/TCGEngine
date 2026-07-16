# ExpToUpTo3Units
#// TS26_060 Take Charge (Event, cost 3, Command) — Give an Experience token to each of up to 3 units.
#// Two units are chosen; each gains 1 Experience (+1/+1).
## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:TS26_060}
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:POWER:4
