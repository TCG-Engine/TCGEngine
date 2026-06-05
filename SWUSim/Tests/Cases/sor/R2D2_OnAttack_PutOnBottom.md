# SOR_236 R2-D2 — OnAttack scry 1: put top card on bottom.

## GIVEN
P1LeaderBase: SOR_014/SOR_024
P2LeaderBase: SOR_014/SOR_024
SkipPreGame: true
WithP1GroundArena: SOR_236:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:|SOR_095

## EXPECT
P1DECKTOPCARD:SOR_128
P2BASEDMG:1
