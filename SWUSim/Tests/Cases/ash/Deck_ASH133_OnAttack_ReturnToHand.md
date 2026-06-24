# ASH_133 Trask Walker (Ground, 5/9, cost 8) — the same modal ability also fires On Attack. Trask (in
# play, ready) attacks SOR_046 (3/7) and survives; its On Attack returns SOR_095 from the discard to hand.
# Verifies the On Attack dispatch path (the single discard card auto-resolves; only the mode is answered).
## GIVEN
CommonSetup: ggk/ggk/{discardCardIds:SOR_095}
WithP1GroundArena: ASH_133:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Return
## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:0
