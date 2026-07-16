# OnAttackDrawDeal
#// LAW_051 Beilert Valance (3/6) — On Attack: draw a card; you may deal damage to a ground unit equal to
#// the number of cards you've drawn this phase. Attacks the base; draws 1 (1 drawn this phase) -> deal 1
#// to the enemy SOR_046.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_051:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:1
P1HANDCOUNT:1
