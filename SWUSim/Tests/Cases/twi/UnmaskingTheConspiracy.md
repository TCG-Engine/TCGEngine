# DiscardLookDiscard
#// TWI_223 Unmasking the Conspiracy (Event, Cunning) — "Discard a card from your hand. If you do, look at an
#// opponent's hand and discard a card from it." P1 discards SOR_095; then P2's only card is discarded.
## GIVEN
CommonSetup: yyk/bbw/{myResources:1;theirhandCardIds:SOR_128}
P1OnlyActions: true
WithP1Hand: [TWI_223 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirHand-0
## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:0
