# LOF_068 Luthen Rael — On Attack: search the top 5 for an Item upgrade, reveal and draw it. Luthen
# attacks the base and draws the lone Item upgrade (SOR_053) from the top 5.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_068:1:0
WithP1Deck: SOR_053
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:SOR_053

## EXPECT
P1HANDCOUNT:1
