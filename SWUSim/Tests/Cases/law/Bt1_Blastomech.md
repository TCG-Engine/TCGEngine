# OnAttackMillAggressionDeal
#// LAW_173 BT-1 (2/4) — On Attack: discard a card from your deck. If it's Aggression, you may deal 1 to
#// a ground unit. Mills SOR_128 (Aggression) -> deal 1 to the enemy SOR_046.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_173:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1DISCARDCOUNT:1
