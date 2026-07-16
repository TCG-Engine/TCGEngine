# OnAttack_OpponentChoosesDraw
#// LOF_065 Watto — On Attack: an opponent chooses one: you give an Experience token to a friendly unit,
#// or you draw a card. Watto attacks the base; P2 picks "Draw", so P1 draws a card.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_065:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:Draw

## EXPECT
P1HANDCOUNT:1
P2BASEDMG:1
