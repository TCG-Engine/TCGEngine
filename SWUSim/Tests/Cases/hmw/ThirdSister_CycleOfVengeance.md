# Chain_Link1Declined_NothingHappensAtAll
#// HMW_051 Third Sister - Cycle of Vengeance ([Aggression][Cunning][Villainy], cost 4, 6/3 Ground,
#// Force/Imperial/Inquisitor, unique)
#// Text: Overwhelm
#//       When Played: You may deal 2 damage to a unit. If you do, that unit's controller may deal
#//       3 damage to a unit. If they do, that unit's controller may deal 4 damage to a unit.
#//
#// A THREE-LINK ALTERNATING CHAIN, and the whole card is in who acts next: each link's actor is the
#// CONTROLLER OF THE UNIT the previous link damaged. Hit an enemy and they swing back; hit your own
#// unit and you keep the chain. Nothing here says "enemy" or "opponent" — an implementation that
#// reached for OtherPlayer() would be right only by coincidence.
#//
#// COVERAGE: offer=Link1_OffersEVERYUnitOnBothSides (P1SELECTABLEEXACT, includes Third Sister herself) ·
#//           negative=ShieldAbsorbsTheTwo_ChainSTOPS (the "if you do" gate must not fire when no damage
#//             landed) + the three decline sections ·
#//           boundary=N/A (fixed 2/3/4 — no threshold, no scaling count) ·
#//           control=Link1_TargetsYOUROwnUnit_SoYOUActNext (the actor is read off the damaged unit's
#//             CONTROLLER, which is the card's only control-sensitive read) ·
#//           reqboundary=FullChain_AcrossTheRequestBoundary ·
#//           decline=Link1/Link2/Link3 declined, all three, separately
#// ⚠ NO-VALID-TARGET is N/A BY CONSTRUCTION, not by omission: Third Sister is herself in play and a
#//   legal target of every link ("a unit", unqualified), so the pool can never be empty while the
#//   ability resolves. ThirdSisterHerselfIsALegalTarget pins that.
#//
#// This section: decline link 1 outright. Every unit is untouched and nobody is asked anything.

## GIVEN
CommonSetup: rrk/bbw/{myResources:8}
WithActivePlayer: 1
WithP1Hand: HMW_051
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:CARDID:HMW_051
P1GROUNDARENAUNIT:1:DAMAGE:0
P1NODECISION
P2NODECISION

---

# Link1_OffersEVERYUnitOnBothSides
#// ⚠ THE OFFER CELL. "A unit" carries no controller word, so the pool is EVERY unit in play on EITHER
#// side — including Third Sister herself, who is already in the arena when her own When Played
#// resolves. Left pending and asserted exactly.
#// ⚠ P1SELECTABLEEXACT is the only form that catches a pool that is too WIDE as well as too narrow;
#//   answering a target proves neither.
#// Board: one friendly (index 0), Third Sister (index 1), one enemy, and an enemy SPACE unit — "a unit"
#// is not arena-restricted either.

## GIVEN
CommonSetup: rrk/bbw/{myResources:8}
WithActivePlayer: 1
WithP1Hand: HMW_051
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0&theirSpaceArena-0

---

# ThirdSisterHerselfIsALegalTarget
#// She is in play by the time her own When Played resolves, and "a unit" includes her. Aiming the 2 at
#// herself leaves a 6/3 on 2 damage — and since P1 controls her, P1 also acts on link 2.
#// This is the section that makes "no valid target" unreachable for link 1, which is why the ledger
#// records that cell as N/A by construction rather than skipped.

## GIVEN
CommonSetup: rrk/bbw/{myResources:8}
WithActivePlayer: 1
WithP1Hand: HMW_051
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_051
P1GROUNDARENAUNIT:0:DAMAGE:2
P2NODECISION

---

# Link1_TargetsYOUROwnUnit_SoYOUActNext
#// ⚠ THE SHARPEST SECTION IN THE FILE. "That unit's controller" — P1 damages P1's OWN unit, so the
#// link-2 offer must land on P1's queue, NOT the opponent's. An implementation that hardcoded
#// OtherPlayer() passes every enemy-target section in this file and fails only here.
#// P1 hits their own Consular Security Force for 2, then takes link 2 and deals 3 to the enemy.

## GIVEN
CommonSetup: rrk/bbw/{myResources:8}
WithActivePlayer: 1
WithP1Hand: HMW_051
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# FullChain_EnemyTarget_ThenTheyStrikeBack
#// The full three links, bouncing sides. P1 deals 2 to P2's unit → P2 is the next actor and deals 3 to
#// P1's unit → P1 controls THAT one, so P1 acts again and deals 4.
#// The 4 finishes P1's own Consular Security Force (3/7, already on 3) — 3+4 = 7.

## GIVEN
CommonSetup: rrk/bbw/{myResources:8}
WithActivePlayer: 1
WithP1Hand: HMW_051
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_051
P1DISCARDCOUNT:1

---

# Link2_Declined_ChainStopsBeforeLink3
#// DECLINE, middle link. P1 takes link 1; P2 declines link 2; there is no link 3 and no further
#// decision for anyone. Only the first 2 damage exists on the board.

## GIVEN
CommonSetup: rrk/bbw/{myResources:8}
WithActivePlayer: 1
WithP1Hand: HMW_051
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
P2NODECISION

---

# Link3_Declined_OnlyTheFirstTwoLandDamage
#// DECLINE, last link. Links 1 and 2 resolve; the third actor declines. 2 and 3 damage are on the
#// board, no 4 anywhere.

## GIVEN
CommonSetup: rrk/bbw/{myResources:8}
WithActivePlayer: 1
WithP1Hand: HMW_051
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
P2NODECISION

---

# TargetDefeatedByTheTwo_ChainSTILLContinues
#// "If you do" is satisfied by DEALING the damage — the target dying to it does not break the chain,
#// and its controller is still the next actor. A Death Star Stormtrooper (3/1) is defeated outright by
#// the 2, and P2 still gets the link-2 offer.
#// ⚠ The controller must be captured BEFORE the damage: after the defeat the object is gone, and an
#//   implementation that reads the controller off the post-damage board finds nothing and stops.

## GIVEN
CommonSetup: rrk/bbw/{myResources:8}
WithActivePlayer: 1
WithP1Hand: HMW_051
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# ShieldAbsorbsTheTwo_ChainSTOPS
#// ⚠ THE "IF YOU DO" GATE, and the judgement call this card turns on. A Shield token PREVENTS the
#// instance — the shield is defeated instead — so no damage is dealt, so "if you do" is NOT satisfied
#// and the chain must stop dead. The outcome must be MEASURED, never assumed from having chosen a
#// target; that is the documented family (base-damage prevention, no-heal locks, can't-be-defeated all
#// make attempt-vs-outcome observable).
#// P1 shields the ENEMY's Marine with SOR_073 Moment of Peace (its offer is unit-unrestricted), P2
#// passes, then P1 plays Third Sister and aims the 2 at that shielded Marine.
#// ⚠ No P1OnlyActions: it would let P2's auto-pass swallow a link-2 prompt, and P2NODECISION is
#//   exactly what this section exists to assert — the fixture must leave P2 able to be asked.

## GIVEN
CommonSetup: rrk/bbw/{myResources:14}
WithActivePlayer: 1
WithP1Hand: [SOR_073 HMW_051]
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1NODECISION
P2NODECISION

---

# TwinSuns_DamagingSeatThreesUnit_MakesSEATTHREETheNextActor
#// ⚠ THE SEAT-COUNT CELL. "That unit's controller" is whatever seat actually controls the damaged
#// unit — in a four-seat game that can be seat 3, which no two-seat mapping can reach. P1 aims the 2
#// at P3's unit, so the link-2 offer must appear on P3's queue and nobody else's.
#// P3 then deals its 3 to P4's unit, which hands link 3 to P4 — two different far seats in one chain.

## GIVEN
CommonSetup: rrk/bbw/{myResources:8}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_051
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
SEATCOUNT:4
P3HASDECISION
P2NODECISION
P4NODECISION
P3GROUNDARENAUNIT:0:DAMAGE:2

---

# TwinSuns_SeatThreeTakesIt_ThenSeatFourActs
#// The continuation of the section above, driven to the end: P3 answers its link-2 offer by dealing 3
#// to P4's unit, which makes P4 the link-3 actor.

## GIVEN
CommonSetup: rrk/bbw/{myResources:8}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_051
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0
- P3>AnswerDecision:p4GroundArena-0

## EXPECT
P3GROUNDARENAUNIT:0:DAMAGE:2
P4GROUNDARENAUNIT:0:DAMAGE:3
P4HASDECISION

---

# FullChain_AcrossTheRequestBoundary
#// ⚠ THE REQUEST-BOUNDARY CELL, and a live path rather than a formality: this chain is THREE separate
#// interactive decisions on TWO different players' queues, so every link resumes in a fresh process.
#// Anything the ability holds in memory between links — the damaged unit, its controller, the next
#// amount — is gone by then and the chain silently stops one link short.
#// Same board and answers as FullChain_EnemyTarget_ThenTheyStrikeBack, with a boundary before each.

## GIVEN
CommonSetup: rrk/bbw/{myResources:8}
WithActivePlayer: 1
WithP1Hand: HMW_051
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0
- P2>SimulateRequestBoundary
- P2>AnswerDecision:theirGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_051
P1DISCARDCOUNT:1

---

# Overwhelm_ExcessDamageHitsTheBase
#// The keyword half. HMW_051 is in $Overwhelm_Cards (generated from the card text) — membership is a
#// LITERAL and a wrong one stays green for a whole set, so it gets its own section.
#// Third Sister (6 power) attacks a Death Star Stormtrooper (3/1): 1 kills it, 5 overflow to the base.
#// ⚠ Placed READY in the arena rather than played — a unit played this turn is exhausted, and this
#//   section is about the keyword, not the When Played.

## GIVEN
CommonSetup: rrk/bbw/{}
WithActivePlayer: 1
WithP1GroundArena: HMW_051:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2BASEDMG:5
P2GROUNDARENACOUNT:0
