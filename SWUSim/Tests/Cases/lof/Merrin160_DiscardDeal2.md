# LOF_160 Merrin — On Attack: may discard a card from hand. If you do, deal 2 damage to a unit. Merrin
# attacks the base, discards a card, and deals 2 to the enemy 3/7.

## GIVEN
CommonSetup: rrk/ggw/{handCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: LOF_160:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1HANDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:2
