# OnAttack_NoTraitMatch_NoExhaust
#// SEC_220 Hired Slicer — no trait match: when no unit in play shares a trait with either revealed card,
#// the (optional) exhaust is not offered at all; the 2 cards are still bottomed. SEC_220 (Fringe) attacks
#// alone; the revealed top 2 are SOR_095 (Rebel/Trooper). Fringe shares nothing with Rebel/Trooper, so no
#// unit is eligible — no exhaust decision appears, the cards return to the bottom, and the attack resolves.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_220:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:You

## EXPECT
P2BASEDMG:3
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P1DECKCOUNT:2
P1NODECISION

---

# OnAttack_RevealExhaustTraitMatch
#// SEC_220 Hired Slicer (Unit, 3/4, cost 3, Cunning, Fringe, Ground)
#//   "On Attack: Reveal the top 2 cards of a deck. If you do, you may exhaust a unit that shares a Trait
#//    with one of those cards. Put those cards on the bottom of that deck in a random order."
#// SEC_220 attacks P2's base. On Attack: P1 reveals the top 2 of its OWN deck (both SOR_095 = Rebel/Trooper),
#// then exhausts the friendly SOR_095 (a Rebel/Trooper, sharing a trait). The 2 revealed cards go back to the
#// bottom (deck count returns to 2). SEC_220 itself is Fringe, so it is NOT a legal exhaust target — only the
#// Rebel/Trooper SOR_095 is offered.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_220:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:You
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
P1DECKCOUNT:2
P1NODECISION

---

# OnAttack_OpponentDeck_ExhaustEnemyTraitMatch
#// SEC_220 Hired Slicer — "Reveal the top 2 cards of a DECK" lets you choose the opponent's deck. SEC_220
#// attacks P2's base and reveals the top 2 of P2's deck (SOR_225 TIE/ln Fighter + SOR_132 Imperial
#// Interceptor, both Imperial). The exhaust may then hit ANY unit sharing a Trait with a revealed card —
#// the enemy SOR_128 Death Star Stormtrooper is Imperial, so it is the only eligible target (friendly
#// Creature SOR_164 and enemy Underworld SHD_219 share nothing). The 2 cards return to the bottom of P2's
#// deck (count back to 2).

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_220:1:0
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SHD_219:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_225 SOR_132]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Opponent
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:EXHAUSTED
P2DECKCOUNT:2
P1NODECISION

---

# OnAttack_DeclineExhaust_WithValidTarget
#// SEC_220 Hired Slicer — the exhaust is optional ("you may"). Even when a valid trait-sharing unit
#// exists, P1 may decline: the friendly SOR_095 (Rebel/Trooper) shares a Trait with the revealed
#// SOR_095s, but P1 passes → nothing is exhausted. The 2 revealed cards are still bottomed (deck back to 2).

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_220:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:You
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:1:READY
P1DECKCOUNT:2
P1NODECISION
