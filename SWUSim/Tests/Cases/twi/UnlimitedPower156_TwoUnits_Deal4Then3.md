# TWI_156 Unlimited Power (Event, cost 6, Aggression/Aggression, Force) — "Deal 4 damage to a unit, 3 to
# a second, 2 to a third, and 1 to a fourth. (All damage is dealt simultaneously.)" With only two enemy
# units, the player assigns 4 to the first pick and 3 auto-goes to the remaining unit; the 2 and 1 fizzle
# (no more units). Both SOR_046 (3/7) survive. Base r + leader rk cover both Aggression pips.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6;handCardIds:TWI_156}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:1:DAMAGE:3
