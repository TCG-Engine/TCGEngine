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

---

# TwinSuns_MillsTheDEFENDINGSeatNotABystander
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-23 (Pass 1, DETERMINED). "the defending player" is named by the
#// BOARD, so this card must never prompt for a seat — adding a picker here would be its own bug.
#// The old line was `GetOpponent($player)`, the worst of the three legacy helpers (`1→2, 2→1, else NULL`).
#// TWO failures in one line:
#//   • a seat-1/2 Chopper attacking a FAR seat milled and exhausted an UNINVOLVED player, while the
#//     actual defender was untouched;
#//   • a Chopper CONTROLLED by seat 3 or 4 got null, which SWUMillTopCard(int $player) cannot coerce —
#//     a FATAL, not a quiet no-op, so those seats could not use the ability at all.
#// Now SWUCurrentDefendingSeat(), with an explicit no-op when no attack is in flight — deliberately NOT
#// a fallback to OtherPlayer()/GetOpponent(), because guessing a seat is the bug being fixed.
#//
#// P1's Chopper attacks SEAT 3's base. Seat 3's deck must lose its top card (an EVENT) and seat 3 must
#// have a resource exhausted. Seat 2 — whom the old code punished — must be completely untouched.
#// ⚠ A 2-player version CANNOT FAIL — with one opponent GetOpponent() is non-null and correct.
#// Mutation check: revert to GetOpponent(intval($player)) and this reds while all five 2-player sections
#// above stay green.

## GIVEN
CommonSetup: yyw/rrk/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1GroundArena: SOR_188:1:0
WithP2Deck: [SOR_172 SOR_172]
WithP3Deck: [SOR_172 SOR_172]
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>AttackGroundArena:0:P3B

## EXPECT
SEATCOUNT:4
P3BASEDMG:1
P3DECKCOUNT:1
P2DECKCOUNT:2
