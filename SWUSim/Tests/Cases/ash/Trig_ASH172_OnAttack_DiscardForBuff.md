# ASH_172 Razor Crest (Space, 3/5, Saboteur, cost 4) — On Attack: you may discard a card from your hand;
# if you do, this unit gets +2/+0 for this attack. Razor Crest attacks P2's base; P1 accepts (YES) and
# discards its one hand card, so the base takes 3+2 = 5 and the hand empties.
## GIVEN
CommonSetup: rrk/rrk/{handCardIds:SOR_095}
WithP1SpaceArena: ASH_172:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:5
P1HANDCOUNT:0
