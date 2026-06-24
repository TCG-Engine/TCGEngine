# ASH_172 Razor Crest (Space, 3/5, cost 4) — declining the On Attack discard leaves the unit at base
# power. Razor Crest attacks P2's base and P1 declines (NO), so the base takes only 3 and the hand card
# is kept.
## GIVEN
CommonSetup: rrk/rrk/{handCardIds:SOR_095}
WithP1SpaceArena: ASH_172:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:NO
## EXPECT
P2BASEDMG:3
P1HANDCOUNT:1
