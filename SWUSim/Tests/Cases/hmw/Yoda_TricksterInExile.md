# WhenDefeated_PutOnTopOfDeck_HealTwo
#// HMW_056 Yoda, Trickster In Exile (Unit, Ground, cost 3, 4/4, [Cunning][Vigilance][Heroism],
#// Force/Fringe/Jedi, unique) — "Hidden / When Defeated: You may put this card from your discard pile on
#// top of your deck. If you do, heal 2 damage from your base."
#//
#// COVERAGE: offer=WhenDefeated_OfferIsAYesNo (the optional half has no target, so the "offer" is the
#//           pending YESNO itself — asserted by tooltip) · decline=WhenDefeated_Decline_StaysInDiscard ·
#//           boundary=HealClampsAtZero + UndamagedBase_StillOffered_HealsNothing (heal 2 vs 1 and 0
#//           damage) · control=StolenYoda_LandsInItsOWNERsDiscard_NothingHappens (owner ≠ controller: the
#//           card is not in the resolver's pile, and the resolver's OWN older Yoda must not stand in) ·
#//           reqboundary=RequestBoundary_BeforeTheAnswer ·
#//           modes=2P only — every reference is "your" (self-scoped); no player reference, no
#//           friendly/enemy wording.
#//
#// Hidden needs no code (HMW_056 is in $Hidden_Cards; generic coverage in keywords/Hidden.md).
#// This section: Yoda attacks Industrious Team (4/7) and dies to the 4 counter-damage — the attacker
#// self-defeat path, so the When Defeated resolves inside P1's own action. YES → Yoda goes on TOP of the
#// deck (2 → 3 cards) and the base heals 2 (3 → 1).

## GIVEN
CommonSetup: ybw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: HMW_056:1:0
WithP1Deck: [SOR_095 SOR_046]
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:4
P1DECKCOUNT:3
P1DECKTOPCARD:HMW_056
P1DISCARDCOUNT:0
P1BASEDMG:1
P1NODECISION

---

# WhenDefeated_OfferIsAYesNo
#// HMW_056 — the OFFER cell. "You may put…" is optional with no target, so it is a YESNO; leave it
#// pending and read it back by tooltip. The Marine (ground 1) slides to ground 0 once Yoda is gone.

## GIVEN
CommonSetup: ybw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: HMW_056:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_046]
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1DECISIONTOOLTIP:Put_Yoda_on_top_of_your_deck?_If_you_do,_heal_2_damage_from_your_base.
P1DISCARDUNIT:0:CARDID:HMW_056

---

# WhenDefeated_Decline_StaysInDiscard
#// HMW_056 — the DECLINE: Yoda stays in the discard, the deck is untouched and — "If you do" — the base
#// is NOT healed.

## GIVEN
CommonSetup: ybw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: HMW_056:1:0
WithP1Deck: [SOR_095 SOR_046]
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:HMW_056
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_095
P1BASEDMG:3

---

# HealClampsAtZero
#// HMW_056 — BOUNDARY: heal 2 on a base with only 1 damage ends at exactly 0 (never negative).

## GIVEN
CommonSetup: ybw/rrk/{myBaseDamage:1}
P1OnlyActions: true
WithP1GroundArena: HMW_056:1:0
WithP1Deck: [SOR_095 SOR_046]
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1DECKTOPCARD:HMW_056
P1BASEDMG:0

---

# UndamagedBase_StillOffered_HealsNothing
#// HMW_056 — the heal is a RIDER on the put, not the reason for it: with an undamaged base the offer still
#// appears (recurring Yoda is worth it on its own), the card still goes on top, and the heal does nothing.

## GIVEN
CommonSetup: ybw/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_056:1:0
WithP1Deck: [SOR_095 SOR_046]
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1DECKCOUNT:3
P1DECKTOPCARD:HMW_056
P1BASEDMG:0

---

# DefenderPath_OpponentAttacksYoda
#// HMW_056 — second DEFEAT ROUTE: Yoda is the DEFENDER, killed on the opponent's turn. (He is seeded, not
#// played this phase, so Hidden does not stop the attack.) The When Defeated sits on P1's queue as an
#// undispatched trigger, so P1 drains BEFORE answering — answering first would cancel it.

## GIVEN
CommonSetup: ybw/rrk/{myBaseDamage:3}
WithActivePlayer: 2
WithP1GroundArena: HMW_056:1:0
WithP1Deck: [SOR_095 SOR_046]
WithP2GroundArena: LAW_124:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P1DECKTOPCARD:HMW_056
P1DECKCOUNT:3
P1BASEDMG:1

---

# EffectDefeat_Vanquish
#// HMW_056 — third DEFEAT ROUTE: an ability defeat (SOR_078 Vanquish, "Defeat a non-leader unit") on the
#// opponent's turn, not combat. Yoda is the only non-leader unit, so Vanquish's target auto-resolves.

## GIVEN
CommonSetup: ybw/bbw/{myBaseDamage:3;theirResources:5}
WithActivePlayer: 2
WithP2Hand: SOR_078
WithP1GroundArena: HMW_056:1:0
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P2>PlayHand:0
- P1>Drain
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P1DECKTOPCARD:HMW_056
P1DECKCOUNT:3
P1BASEDMG:1

---

# StolenYoda_LandsInItsOWNERsDiscard_NothingHappens
#// ⚠ HMW_056 — CONTROL. P2 controls a Yoda that P1 OWNS. When he dies he goes to his OWNER's (P1's)
#// discard, while his CONTROLLER (P2) resolves the When Defeated — and "this card from YOUR discard pile"
#// is not in P2's pile, so nothing happens: no prompt, no heal.
#// The sharp half: P2 already has an OLD Yoda of their own in their discard. A "find a Yoda in the
#// resolver's discard" reading grabs that card instead — it is not "this card". P2's deck must stay
#// empty and P2's discard must still hold exactly that one card.

## GIVEN
CommonSetup: ybw/rrk/{theirBaseDamage:3;theirDiscardCardIds:HMW_056}
WithActivePlayer: 2
WithP2GroundArenaControlled: HMW_056:1
WithP1GroundArena: LAW_124:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:HMW_056
P2DISCARDCOUNT:1
P2DECKCOUNT:0
P2BASEDMG:3
P2NODECISION
P1NODECISION

---

# EmptyDeck_YodaBecomesTheWholeDeck
#// HMW_056 — an empty deck is not an obstacle to putting a card on top of it: the deck becomes just Yoda.

## GIVEN
CommonSetup: ybw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: HMW_056:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1DECKCOUNT:1
P1DECKTOPCARD:HMW_056
P1BASEDMG:1

---

# AnOlderCopyInTheDiscard_OnlyOneMoves
#// HMW_056 — QUANTITY: an older Yoda is already in P1's discard when this one dies. "This card" moves ONE
#// copy — the discard keeps one Yoda, the deck gains one.

## GIVEN
CommonSetup: ybw/rrk/{myBaseDamage:3;discardCardIds:HMW_056}
P1OnlyActions: true
WithP1GroundArena: HMW_056:1:0
WithP1Deck: [SOR_095 SOR_046]
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:HMW_056
P1DECKCOUNT:3
P1DECKTOPCARD:HMW_056
P1BASEDMG:1

---

# LostAbilities_NoOffer
#// HMW_056 — a Yoda that has lost his abilities (SOR_138 Force Lightning's marker) has no When Defeated:
#// he dies, stays in the discard, and nothing is offered.

## GIVEN
CommonSetup: ybw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: HMW_056:1:0:SOR_138
WithP1Deck: [SOR_095 SOR_046]
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDUNIT:0:CARDID:HMW_056
P1DECKCOUNT:2
P1BASEDMG:3
P1NODECISION

---

# RequestBoundary_BeforeTheAnswer
#// HMW_056 — the REQUEST-BOUNDARY cell: the first section with a boundary between the offer and the YES.
#// Nothing about "which card to move" may be parked in memory across it.

## GIVEN
CommonSetup: ybw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: HMW_056:1:0
WithP1Deck: [SOR_095 SOR_046]
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES

## EXPECT
P1DECKCOUNT:3
P1DECKTOPCARD:HMW_056
P1DISCARDCOUNT:0
P1BASEDMG:1

---

# CloneCopyOfYoda_PutsTheCloneCardOnTop
#// HMW_056 — DISPATCH PATH: a TWI_116 Clone that entered as a copy of Yoda has his When Defeated, and
#// "this card" is the CLONE card (it reverts to TWI_116 when it leaves play). P1 plays a Clone copying
#// P2's Yoda; P2 Vanquishes the Clone (P2's own Yoda is the other legal target, so the pick is real);
#// P1 answers YES → TWI_116 goes on top of P1's deck and P1 heals 2.

## GIVEN
CommonSetup: ggw/bbw/{myResources:7;myBaseDamage:3;theirResources:5}
WithActivePlayer: 1
WithP1Hand: TWI_116
WithP1Deck: [SOR_095 SOR_046]
WithP2Hand: SOR_078
WithP2GroundArena: HMW_056:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Drain
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P1DECKTOPCARD:TWI_116
P1DECKCOUNT:3
P1BASEDMG:1
