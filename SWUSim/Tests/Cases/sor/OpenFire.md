# Deals4ToUnit
#// SOR_172 Open Fire (Event, cost 3) — "Deal 4 damage to a unit." P1 plays it and
#// targets the enemy Consular Security Force (SOR_046, 3/7), which takes 4 and
#// survives at 4 damage.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:SOR_172}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENACOUNT:1
