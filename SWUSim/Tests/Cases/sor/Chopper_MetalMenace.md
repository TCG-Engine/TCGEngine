# OnAttack_MillEvent_ExhaustResource
#// SOR_188 Chopper (1/3) — "On Attack: Discard a card from the defending player's deck. If it's an
#// event, exhaust a resource that player controls." Chopper (alone, no Raid) attacks the base; the
#// milled card is an EVENT → exhaust one of P2's resources. Base takes Chopper's 1 power (no Raid).
#// COVERAGE: offer=Offer_ExhaustPoolIsReadyResourcesOnly (the pick auto-resolves, so the POOL is what
#//           is assertable: a mixed ready/exhausted resource zone proves only a READY resource can be
#//           taken) + Offer_AllResourcesExhausted_NothingLeftToExhaust (empty pool fizzles cleanly);
#//           there is no menu to assert with P1SELECTABLEEXACT because ready resources are
#//           interchangeable and SWUSim auto-resolves them — pinned by P1NODECISION in both ·
#//           decline=N/A (nothing on this card is optional — no "you may" on either clause, and Raid
#//           is a granted keyword) · boundary pair=MillEvent (event → exhaust) vs
#//           MillUnit_NoExhaust_NoRaidAlone (non-event → no exhaust) + EmptyDeck_NoMill_
#//           NoPenaltyDamage (zero cards to discard); Raid's own pair is RaidWhileAnotherSpectre (1)
#//           vs MillUnit_NoExhaust_NoRaidAlone (0) with Raid_NonSpectreAllyDoesNotGrantIt and
#//           Raid_AnEnemySpectreDoesNotGrantIt as the two disqualifying variants ·
#//           reqboundary=N/A (single-request attack; nothing pends across a serialize — the mill, the
#//           rider and combat all resolve inside the one AttackGroundArena request) ·
#//           control=ControlChange_RaidReadsTheCONTROLLERSOtherSpectres (owner P2 / controller P1: the
#//           Raid gate and the defending-seat mill both resolve for the CONTROLLER)
#// Scope cells: Raid_AnotherSpectreInTheSPACEArenaStillCounts (the Spectre may be in either arena),
#// Raid_DoesNotApplyWhileChopperIsDEFENDING (Raid is attack-only), and
#// OnAttack_AttackingAUNITStillMillsThatUnitsController ("the defending player" is read off the
#// attack, not off a base target).

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

---

# Offer_ExhaustPoolIsReadyResourcesOnly
#// THE OFFER CELL, read as a POOL rather than a menu. "Exhaust a resource that player controls" has
#// no visible picker — every ready resource is interchangeable, so SWUSim resolves it without a prompt
#// — but the pool it resolves against is still assertable, and it is READY resources only: an already
#// exhausted resource cannot be exhausted again, so it must not be consumed as the pick.
#// P2's resource zone is seeded MIXED: index 0 exhausted, indexes 1-2 ready. The milled card is an
#// event, so exactly one READY resource is exhausted — 3 total, 2 ready → 3 total, 1 ready.
#// If the pool were "any resource" the engine could satisfy the effect by re-exhausting index 0 and
#// P2 would still show 2 ready, which is the failure this section discriminates.

## GIVEN
CommonSetup: yyw/rrk/{}
SkipPreGame: true
P1OnlyActions: true
WithP2Resources: 1:SOR_095:0,2:SOR_095
WithP1GroundArena: SOR_188:1:0
WithP2Deck: SOR_172

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2RESCOUNT:3
P2RESAVAILABLE:1
P2DISCARDCOUNT:1
P2BASEDMG:1
P1NODECISION

---

# Offer_AllResourcesExhausted_NothingLeftToExhaust
#// The EMPTY-POOL half of the same cell. Every one of P2's resources is already exhausted, so the
#// event rider has nothing legal to take: it must fizzle quietly — no crash, no dangling decision, no
#// substitute cost, and the resource zone untouched at 2 total / 0 ready. The mill itself still
#// happens (the discard is not conditional on the rider), so the card is not simply skipped whole.

## GIVEN
CommonSetup: yyw/rrk/{}
SkipPreGame: true
P1OnlyActions: true
WithP2Resources: 2:SOR_095:0
WithP1GroundArena: SOR_188:1:0
WithP2Deck: SOR_172

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2RESCOUNT:2
P2RESAVAILABLE:0
P2DECKCOUNT:0
P2DISCARDCOUNT:1
P2BASEDMG:1
P1NODECISION

---

# Raid_AnotherSpectreInTheSPACEArenaStillCounts
#// "While you control ANOTHER SPECTRE UNIT, this unit gains Raid 1" names no arena, so a Spectre in
#// the SPACE arena grants the ground Chopper his Raid just as a ground one does. SOR_050 The Ghost
#// (Rebel/Vehicle/Transport/SPECTRE) is seated in P1's space arena — seated, so its own When Played
#// never fires — and Chopper attacks the base for 1+1 = 2.
#// The scope cell: an implementation that scanned only the attacker's own arena would leave the base
#// on 1 and pass every other Raid section in this file.

## GIVEN
CommonSetup: yyw/rrk/{}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_188:1:0
WithP1SpaceArena: SOR_050:1:0
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
P2DECKCOUNT:0
P1NODECISION

---

# Raid_NonSpectreAllyDoesNotGrantIt
#// The NEGATIVE that proves the SPECTRE trait gate is load-bearing rather than "any other friendly
#// unit". Chopper is NOT alone — P1 also controls SOR_095 Battlefield Marine (Rebel, Trooper; no
#// Spectre trait) — and the base still takes only his printed 1.
#// Distinct from MillUnit_NoExhaust_NoRaidAlone, which tests an EMPTY board: there the count is zero,
#// here the count is one and the TRAIT is what disqualifies it.

## GIVEN
CommonSetup: yyw/rrk/{}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_188:1:0 SOR_095:1:0]
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:1
P2DECKCOUNT:0
P1NODECISION

---

# Raid_AnEnemySpectreDoesNotGrantIt
#// "While YOU control another Spectre unit" — the opponent's Spectres are not yours. P2 controls
#// SOR_047 Kanan Jarrus (Force/Jedi/Rebel/SPECTRE) and Chopper still hits the base for his printed 1.
#// Read against RaidWhileAnotherSpectre (the same Kanan, on P1's side, base takes 2) this isolates the
#// CONTROLLER half of the gate from the trait half.

## GIVEN
CommonSetup: yyw/rrk/{}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_188:1:0
WithP2GroundArena: SOR_047:1:0
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2DECKCOUNT:0
P1NODECISION

---

# Raid_DoesNotApplyWhileChopperIsDEFENDING
#// Raid is an ATTACK-only bonus (CR: "while attacking, this unit gets +N/+0"), so a defending Chopper
#// counters at his printed 1 even with the Raid condition fully satisfied — Kanan, another friendly
#// Spectre, is on the board. P2's TWI_057 Warrior Drone (1/4, vanilla) attacks Chopper: the Drone
#// takes exactly 1 back, not 2, and Chopper survives on 1 damage.
#// The On Attack mill must also stay silent — Chopper is not the attacker — so P1's deck is untouched
#// at 2 and no resource of P1's is exhausted.

## GIVEN
CommonSetup: yyw/rrk/{}
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 3
WithP1GroundArena: [SOR_188:1:0 SOR_047:1:0]
WithP2GroundArena: TWI_057:1:0
WithP1Deck: [SOR_172 SOR_172]

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:CARDID:SOR_188
P1GROUNDARENAUNIT:0:DAMAGE:1
P1DECKCOUNT:2
P1RESAVAILABLE:3

---

# OnAttack_AttackingAUNITStillMillsThatUnitsController
#// "The defending player" is whoever controls the DEFENDER, not whoever owns the base — so the mill
#// and the event rider fire on a unit attack exactly as they do on a base attack. Chopper attacks
#// P2's SOR_046 Consular Security Force (3/7): the On Attack window resolves first (top card is
#// SOR_172 Open Fire, an event → one of P2's ready resources is exhausted), then combat kills the 1/3
#// Chopper on the counter while the defender takes his 1.
#// Distinct from the base-attack sections: it proves the trigger reads the ATTACK, not the base target.

## GIVEN
CommonSetup: yyw/rrk/{theirResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_188:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Deck: SOR_172

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2DECKCOUNT:0
P2DISCARDCOUNT:1
P2RESAVAILABLE:1
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENACOUNT:0
P2BASEDMG:0

---

# ControlChange_RaidReadsTheCONTROLLERSOtherSpectres
#// OWNER ≠ CONTROLLER. Chopper sits in P1's arena but is OWNED by P2 (the end state of a take-control
#// effect), and the other Spectre — SOR_047 Kanan — is P1's own. "While YOU control another Spectre
#// unit" resolves against the CONTROLLER, so the grant is on and the stolen Chopper attacks for
#// 1+1 = 2. His On Attack also has to resolve for the controller: the card is milled off the DEFENDING
#// player's deck (P2's), not off the owner's.
#// ⚠ Controlled units seat AFTER every plain WithP1GroundArena line regardless of declaration order,
#// so Kanan is index 0 and the stolen Chopper is index 1.

## GIVEN
CommonSetup: yyw/rrk/{theirResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_047:1:0
WithP1GroundArenaControlled: SOR_188:2
WithP2Deck: SOR_172

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_188
P2BASEDMG:2
P2DECKCOUNT:0
P2DISCARDCOUNT:1
P2RESAVAILABLE:1
P1NODECISION
