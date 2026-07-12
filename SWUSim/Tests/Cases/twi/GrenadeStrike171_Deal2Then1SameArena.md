# TWI_171 Grenade Strike (Event, cost 2, Aggression, Tactic) — "Deal 2 damage to a unit. You may deal 1
# damage to another unit in the same arena." Two enemy ground units: 2 to the first, then (option taken) 1
# to the second (same arena).

## GIVEN
CommonSetup: rrk/bbw/{myResources:2;handCardIds:TWI_171}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:1
