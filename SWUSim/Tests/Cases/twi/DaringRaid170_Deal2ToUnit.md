# TWI_170 Daring Raid (Event, cost 1, Aggression, Tactic) — "Deal 2 damage to a unit or base." Targeting
# the enemy unit SOR_046 (3/7) deals it 2.

## GIVEN
CommonSetup: rrk/bbw/{myResources:1;handCardIds:TWI_170}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
