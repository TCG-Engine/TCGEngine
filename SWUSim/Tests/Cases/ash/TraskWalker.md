# OnAttack_ReturnToHand
#// ASH_133 Trask Walker (Ground, 5/9, cost 8) — the same modal ability also fires On Attack. Trask (in
#// play, ready) attacks SOR_046 (3/7) and survives; its On Attack returns SOR_095 from the discard to hand.
#// Verifies the On Attack dispatch path (the single discard card auto-resolves; only the mode is answered).
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

---

# WhenPlayed_BottomAndHeal
#// ASH_133 Trask Walker (Ground, 5/9, cost 8) — When Played: choose a unit in your discard pile costing 7
#// or less; either put it on the bottom of your deck and heal 3 from your base, OR return it to your hand.
#// Here the discarded SOR_095 (cost 2) is the only choice (auto-resolved); choosing "Bottom" heals P1's
#// base from 5 damage to 2 and clears the discard pile.
## GIVEN
CommonSetup: ggk/ggk/{myResources:8;handCardIds:ASH_133;discardCardIds:SOR_095;myBaseDamage:5}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Bottom
## EXPECT
P1BASEDMG:2
P1DISCARDCOUNT:0

---

# WhenPlayed_ReturnToHand
#// ASH_133 Trask Walker (Ground, 5/9, cost 8) — When Played, the "return it to your hand" mode. SOR_095
#// (cost 2) is returned from discard to hand (so the base is NOT healed — stays at 5 damage — and the
#// discard empties while the hand holds the returned card).
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
