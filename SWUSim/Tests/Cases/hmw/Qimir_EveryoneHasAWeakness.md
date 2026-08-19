# DiscardNonVillainy_GivesWeaknessToAnEnemy
#// HMW_196 Qimir - Everyone Has a Weakness (Cunning/Villainy, Force, cost 1, 3/1 Ground, unique) —
#// "When Defeated: You may discard the top card of your deck. If it's not Villainy, give a Weakness
#// token to an enemy unit."
#// COVERAGE: offer=WeaknessOfferIsEnemyOnly (the pool, left pending: enemies only, friendlies excluded) ·
#//           negative=DiscardVillainy_NoWeakness (the aspect gate) + the friendly exclusion in the offer
#//           section · boundary=WeaknessCanBeLethalViaShrinkDefeat (a 1-HP body hits exactly 0) ·
#//           control=ControlChanged_NewControllerDiscardsTheirOwnDeck (BOTH decks asserted) ·
#//           reqboundary=RequestBoundary_AcrossTheDiscardDecision ·
#//           decline=Decline_TopCardRetained, and separately EmptyDeck_NoPromptAtAll — decline and
#//           cannot-do are DIFFERENT branches
#// ⚠ The "may" attaches to the DISCARD only; the Weakness itself is mandatory once the condition holds,
#//   so its target is an MZCHOOSE and not an MZMAYCHOOSE.
#// ⚠ "an ENEMY unit" — unlike its sibling HMW_059 Clone X Assassin, whose "a unit" spans both sides.
#//   Two cards, one set, one word apart; the offer section is what keeps them from being copy-pasted.
#// Weakness = HMW_T02, a -1/-1 Token Upgrade, so a 3/7 Security Force reads 2/6 afterwards.
#// Qimir is driven to its own defeat by ATTACKING into a bigger body: the attacker's When Defeated then
#// resolves inline in P1's own action (a defender's would sit pending on the other seat's queue).

## GIVEN
CommonSetup: yyk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: HMW_196:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:6
P2GROUNDARENAUNIT:0:DAMAGE:3
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_128
P1DISCARDCOUNT:2
P1NODECISION

---

# DiscardVillainy_NoWeakness
#// HMW_196 — the aspect gate, and the whole point of the card's name. Identical board, but the top of
#// the deck is a VILLAINY card (Imperial Dark Trooper, Command/Villainy): the discard still happens
#// (it is not gated on anything), and the Weakness does not. Without this section an implementation
#// that never checks the aspect passes every other positive.

## GIVEN
CommonSetup: yyk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: HMW_196:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SEC_080 SOR_128]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1DECKCOUNT:1
P1DISCARDCOUNT:2
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:7
P1NODECISION

---

# Decline_TopCardRetained
#// HMW_196 — the decline branch. "You may discard" means NO is a real answer, and it must leave the
#// deck alone: the top card is still on top, the discard holds only Qimir himself, and no Weakness was
#// handed out. Asserting DECKTOPCARD as well as the count is what proves the card was retained rather
#// than discarded and replaced.

## GIVEN
CommonSetup: yyk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: HMW_196:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_095
P1DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# EmptyDeck_NoPromptAtAll
#// HMW_196 — CANNOT-DO is a different branch from DECLINE. With an empty deck there is nothing to
#// discard, so the question must not be asked at all: no decision is left pending and nothing happens.
#// Note this section deliberately supplies NO answer — if the ability prompted anyway, the pending
#// YESNO would fail P1NODECISION.

## GIVEN
CommonSetup: yyk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: HMW_196:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1NODECISION
P1DISCARDCOUNT:1
P1DECKCOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2BASEDMG:0

---

# WeaknessOfferIsEnemyOnly
#// HMW_196 — the pool, left PENDING. "an enemy unit" excludes the caster's own board, and with a
#// friendly survivor on the field an unqualified implementation would offer it. Two enemies so nothing
#// auto-resolves and there is a real offer to inspect.
#// (Qimir dies in the attack, so P1's arena compacts to Battlefield Marine at index 0.)

## GIVEN
CommonSetup: yyk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: HMW_196:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
P1HASDECISION

---

# WeaknessCanBeLethalViaShrinkDefeat
#// HMW_196 — -1/-1 is HP reduction, not damage, so a 1-HP body drops to 0 remaining HP and is swept by
#// the shrink-defeat check. Qimir attacks the 3/7 (which kills him) while the 3/1 Stormtrooper stands
#// untouched, then the Weakness lands on the Stormtrooper and defeats it outright.

## GIVEN
CommonSetup: yyk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: HMW_196:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# NoEnemyUnit_TheDiscardStillHappens
#// HMW_196 — the RULING this card turns on, pinned deliberately. Qimir trades with a 3/3, so by the
#// time the When Defeated resolves there is no enemy unit left and the Weakness has nowhere to go.
#// The discard is NOT a cost paid for the Weakness: the sentences are joined by "If it's NOT VILLAINY",
#// a condition on the discarded CARD, never by "If you do". So the offer stands and a player who says
#// YES discards their top card for no board effect — filling the discard pile, which several HMW cards
#// care about. (Contrast LAW_257, where the optional half spends a RESOURCE and is correctly withheld.)

## GIVEN
CommonSetup: yyk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: HMW_196:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_128
P1DISCARDCOUNT:2
P1NODECISION

---

# ControlChanged_NewControllerDiscardsTheirOwnDeck
#// HMW_196 — the control cell, in its sharp form: "YOUR deck" and "an ENEMY unit" are both read from
#// whoever CONTROLS Qimir when he dies, not from his owner. P1 owns him, P2 controls him, and P2 swings
#// him into P1's Security Force. P2's deck loses the top card, P1's deck is UNTOUCHED (both seats
#// asserted — the thief's loss alone is half a test), and the Weakness lands on a P1 unit, which is
#// only "enemy" from P2's side. Qimir himself still goes to his OWNER's discard pile.

## GIVEN
CommonSetup: bbw/yyk/{}
WithActivePlayer: 2
WithP1GroundArena: SOR_046:1:0
WithP2GroundArenaControlled: HMW_196:1
WithP1Deck: [SOR_095 SOR_046]
WithP2Deck: [SOR_095 SOR_128]

## WHEN
- P2>AttackGroundArena:0:0
- P2>AnswerDecision:YES

## EXPECT
P2DECKCOUNT:1
P1DECKCOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1
P2GROUNDARENACOUNT:0

---

# RequestBoundary_AcrossTheDiscardDecision
#// HMW_196 — the request-boundary cell. The YESNO ends the request in production, so everything the
#// continuation needs to finish (which card got discarded, its aspect, the enemy pool) must be derived
#// AFTER the answer rather than parked in memory when the question was asked. Same flow and same
#// assertions as the first section, with the boundary inserted before the answer.

## GIVEN
CommonSetup: yyk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: HMW_196:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P2GROUNDARENAUNIT:0:POWER:2
P1DECKCOUNT:1
P1DISCARDCOUNT:2
P1NODECISION
