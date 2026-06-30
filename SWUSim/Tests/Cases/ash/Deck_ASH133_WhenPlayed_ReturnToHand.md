# ASH_133 Trask Walker (Ground, 5/9, cost 8) — When Played, the "return it to your hand" mode. SOR_095
# (cost 2) is returned from discard to hand (so the base is NOT healed — stays at 5 damage — and the
# discard empties while the hand holds the returned card).
## GIVEN
CommonSetup: ggk/ggk/{myResources:8;handCardIds:ASH_133;discardCardIds:SOR_095;myBaseDamage:5}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Return
## EXPECT
P1BASEDMG:5
P1DISCARDCOUNT:0
P1HANDCOUNT:1
