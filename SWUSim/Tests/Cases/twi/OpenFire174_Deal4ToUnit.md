# TWI_174 Open Fire (Event, cost 3, Aggression, Tactic) — "Deal 4 damage to a unit." Targeting SOR_046
# (3/7) deals it 4.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3;handCardIds:TWI_174}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
