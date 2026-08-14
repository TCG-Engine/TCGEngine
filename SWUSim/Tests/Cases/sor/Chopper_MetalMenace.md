# OnAttack_MillEvent_ExhaustResource
#// SOR_188 Chopper (1/3) — "On Attack: Discard a card from the defending player's deck. If it's an
#// event, exhaust a resource that player controls." Chopper (alone, no Raid) attacks the base; the
#// milled card is an EVENT → exhaust one of P2's resources. Base takes Chopper's 1 power (no Raid).
#// COVERAGE: offer=N/A (the only pick is which ready resource to exhaust; ready resources are
#//           interchangeable, so it auto-resolves even with several — pinned by
#//           MillEvent_TwoResources_OneExhausted's P1NODECISION) · decline=N/A (nothing optional) ·
#//           boundary pair=MillEvent (event → exhaust) vs MillUnit_NoExhaust_NoRaidAlone (non-event
#//           → no exhaust) + EmptyDeck_NoMill_NoPenaltyDamage (zero cards to discard) ·
#//           reqboundary=N/A (single-request attack; nothing pends) · control=N/A (Raid gate reads
#//           the live controller's other Spectres at attack time; no lingering marker)

## GIVEN
CommonSetup: yyw/rrk/{theirResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_188:1:0
WithP2Deck: SOR_172

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:1
P2DECKCOUNT:0
P2DISCARDCOUNT:1
P2RESAVAILABLE:0

---

# RaidWhileAnotherSpectre
#// SOR_188 Chopper — "While you control another SPECTRE unit, this unit gains Raid 1." With Kanan
#// (another Spectre) in play, Chopper attacks the base for 1+1(Raid)=2. (The milled top card is a
#// unit, so no resource is exhausted.)

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_188:1:0
WithP1GroundArena: SOR_047:1:0
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
P2DECKCOUNT:0

---

# MillUnit_NoExhaust_NoRaidAlone
#// Intended: the milled card is a UNIT, so the "if it's an event" rider does NOT fire — both of
#// P2's resources stay ready. Chopper is alone (no other Spectre), so no Raid either: the base
#// takes exactly his printed 1.

## GIVEN
CommonSetup: yyw/rrk/{theirResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_188:1:0
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:1
P2DECKCOUNT:0
P2DISCARDCOUNT:1
P2RESAVAILABLE:2
P1NODECISION

---

# EmptyDeck_NoMill_NoPenaltyDamage
#// Intended: with the defending player's deck EMPTY the discard simply fizzles — nothing is
#// milled, no resource is touched, and no empty-deck damage is dealt (that penalty belongs to the
#// regroup DRAW step, not to a deck discard). The base takes only Chopper's 1 combat damage.

## GIVEN
CommonSetup: yyw/rrk/{theirResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_188:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:1
P2DECKCOUNT:0
P2DISCARDCOUNT:0
P2RESAVAILABLE:2
P1NODECISION

---

# MillEvent_TwoResources_OneExhausted
#// Intended: the event rider with SEVERAL ready resources still exhausts exactly one — the pick
#// among fungible ready resources resolves without stalling the attack, and P2 ends one resource
#// down.

## GIVEN
CommonSetup: yyw/rrk/{theirResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_188:1:0
WithP2Deck: SOR_172

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:1
P2DISCARDCOUNT:1
P2RESAVAILABLE:1
P1NODECISION
