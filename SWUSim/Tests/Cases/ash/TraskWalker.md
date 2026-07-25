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

---

# OnAttack_BottomAndHeal
#// ASH_133 Trask Walker — the On Attack trigger, "Bottom + heal 3" mode. Trask (in play, ready) attacks
#// P2's base; its On Attack puts the discarded SOR_095 (cost 2, the only choice, auto-resolved) on the
#// bottom of the deck and heals 3 from P1's base (5 damage → 2), clearing the discard pile.
## GIVEN
CommonSetup: ggk/ggk/{discardCardIds:SOR_095;myBaseDamage:5}
WithP1GroundArena: ASH_133:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Bottom
## EXPECT
P1BASEDMG:2
P1DISCARDCOUNT:0

---

# OnlyFriendlyCheapUnitsSelectable
#// ASH_133 Trask Walker — the target must be a friendly UNIT in the discard costing 7 or less. With the
#// discard holding SOR_164 Wampa (unit cost 4, valid), SOR_078 Vanquish (event), SOR_136 Vader's Lightsaber
#// (upgrade), and SOR_088 Blizzard Assault AT-AT (unit cost 8), only Wampa is eligible — and the enemy's
#// discarded SOR_098 is never a candidate. Wampa auto-resolves as the lone legal target; returning it to
#// hand leaves the three ineligible P1 cards in the discard and the enemy discard untouched.
## GIVEN
CommonSetup: ggk/ggk/{discardCardIds:SOR_164,SOR_078,SOR_136,SOR_088;theirDiscardCardIds:SOR_098;myResources:8;handCardIds:ASH_133}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Return
## EXPECT
P1HANDCARD:0:SOR_164
P1DISCARDCOUNT:3
P2DISCARDCOUNT:1

---

# NoValidTargets_NoPrompt
#// ASH_133 Trask Walker — when the discard has no eligible unit (only SOR_078 Vanquish, an event, and
#// SOR_088 Blizzard Assault AT-AT, cost 8), the ability finds nothing to choose and does not prompt; the
#// discard is unchanged and no decision is left pending.
## GIVEN
CommonSetup: ggk/ggk/{discardCardIds:SOR_078,SOR_088;myResources:8;handCardIds:ASH_133}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P1DISCARDCOUNT:2
