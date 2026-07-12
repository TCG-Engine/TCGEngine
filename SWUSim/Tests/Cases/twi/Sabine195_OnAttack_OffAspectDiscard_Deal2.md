# TWI_195 Sabine Wren — "On Attack: You may discard a card from your deck. If it doesn't share an
# aspect with your base, deal 2 damage to a ground unit." Base is Cunning (y). Sabine attacks P2's base
# (4 damage); on YES she discards the top card SOR_128 (Aggression/Villainy — no Cunning) → off-aspect →
# deal 2 to the enemy SOR_046.

## GIVEN
CommonSetup: yyw/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_195:1:0
WithP1Deck: SOR_128
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:DAMAGE:2
P1DISCARDCOUNT:1
