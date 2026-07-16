# OnAttack_PutOnBottom
#// SOR_236 R2-D2 — OnAttack scry 1: put top card on bottom.

## GIVEN
CommonSetup: grw/grw
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

---

# WhenPlayed_KeepOnTop
#// SOR_236 R2-D2 — WhenPlayed scry 1: choose to keep top card on top.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1Hand: SOR_236
WithP1Resources: 2
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095|

## EXPECT
P1DECKTOPCARD:SOR_095

---

# WhenPlayed_PutOnBottom
#// SOR_236 R2-D2 — WhenPlayed scry 1: put top card on bottom.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1Hand: SOR_236
WithP1Resources: 2
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:|SOR_095

## EXPECT
P1DECKTOPCARD:SOR_128
