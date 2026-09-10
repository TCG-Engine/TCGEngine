# ReadyYourOwnUnit_HealItsCost
#// HMW_042 Dooku, Corruption Must Be Eradicated (Unit, Ground, cost 8, 8/7, [Vigilance][Aggression],
#// Force/Jedi/Republic, unique) — "Overwhelm / When Played: You may ready another unit. If you do, heal
#// damage from a base equal to that unit's cost."
#//
#// USER RULING (2026-09-10): "ready another unit" = ANY unit other than Dooku — ENEMY units included (ready
#// a high-cost, low-power enemy to heal more than it can swing back for).
#//
#// COVERAGE: offer=Offer_ExhaustedUnitsOtherThanDooku (SELECTABLEEXACT — a ready unit on each side and the
#//           just-played, exhausted Dooku are all excluded) + Offer_ADamagedBaseOnEitherSide (the base pick)
#//           · decline=Decline_NothingReadiedNoHeal · boundary=HealClampsAtZero + ATokenCostsZero_NoHeal
#//           (cost 8 against 3 damage; cost 0) · control=N/A (structural: "that unit's COST" is printed, "a
#//           base" is unqualified and "another unit" names no controller — nothing is scoped to an owner) ·
#//           reqboundary=RequestBoundary_AcrossBothPicks ·
#//           modes=2P,TwinSuns,TeamSuns — "another unit" and "a base" are unqualified, so every seat's units
#//           and bases are in play (TwinSuns_… / TeamSuns_… sections).
#//
#// Overwhelm needs no code ($Overwhelm_Cards). Reading of the rest:
#//   • the pool is EXHAUSTED units only — readying a ready unit changes nothing, so it is not offered;
#//   • "IF YOU DO" measures the outcome: the unit must actually go from exhausted to ready (a unit under a
#//     can't-ready effect can be chosen, stays exhausted, and heals nothing);
#//   • "that unit's COST" is the PRINTED cost (a token is 0, a deployed leader its leader cost);
#//   • "A base" is unqualified: any damaged base on any side; a lone damaged base is healed without a
#//     prompt, and no prompt at all when nothing is damaged or the cost is 0.
#//
#// This section: Dooku is played; P1's Industrious Team (cost 8, exhausted) is the only other exhausted
#// unit. Ready it → P1's base heals 8 (10 → 2); P2's base is undamaged, so P1's is healed without asking.

## GIVEN
CommonSetup: brk/rrk/{myResources:8;myBaseDamage:10}
WithActivePlayer: 1
WithP1Hand: HMW_042
WithP1GroundArena: LAW_124:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:CARDID:HMW_042
P1BASEDMG:2
P1NODECISION
TURNPLAYER:2
NOEXTRAACTION

---

# ReadyAnEnemyUnit_StillHealsItsCost
#// HMW_042 — the ruling's case: the only exhausted other unit is P2's Industrious Team (cost 8). Readying
#// it heals P1's base 8, and the ENEMY unit is now ready.

## GIVEN
CommonSetup: brk/rrk/{myResources:8;myBaseDamage:10}
WithActivePlayer: 1
WithP1Hand: HMW_042
WithP2GroundArena: LAW_124:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:READY
P1BASEDMG:2
P2BASEDMG:0

---

# Offer_ExhaustedUnitsOtherThanDooku
#// HMW_042 — the unit OFFER. Included: P1's exhausted Industrious Team (ground 0) and P2's exhausted Dark
#// Trooper (ground 0). Excluded: P1's READY Marine (ground 1), P2's READY Stormtrooper (ground 1), and
#// Dooku himself (ground 2) — who entered play EXHAUSTED, so "another" is what excludes him.

## GIVEN
CommonSetup: brk/rrk/{myResources:8;myBaseDamage:10}
WithActivePlayer: 1
WithP1Hand: HMW_042
WithP1GroundArena: LAW_124:0:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:0:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:2:CARDID:HMW_042
P1GROUNDARENAUNIT:2:EXHAUSTED
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# Offer_ADamagedBaseOnEitherSide
#// HMW_042 — the BASE pick. "A base" is unqualified: with both bases damaged, both are offered (left
#// pending after the unit is readied).

## GIVEN
CommonSetup: brk/rrk/{myResources:8;myBaseDamage:10;theirBaseDamage:5}
WithActivePlayer: 1
WithP1Hand: HMW_042
WithP1GroundArena: LAW_124:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1SELECTABLEEXACT:myBase-0&theirBase-0

---

# HealingTheOpponentsBaseIsLegal
#// HMW_042 — the same board resolved on the OPPONENT's base: P2's 5 damage heals to 0 (clamped), P1's
#// stays at 10.

## GIVEN
CommonSetup: brk/rrk/{myResources:8;myBaseDamage:10;theirBaseDamage:5}
WithActivePlayer: 1
WithP1Hand: HMW_042
WithP1GroundArena: LAW_124:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:0
P1BASEDMG:10

---

# HealClampsAtZero
#// HMW_042 — BOUNDARY: heal 8 on a base with 3 damage ends at exactly 0.

## GIVEN
CommonSetup: brk/rrk/{myResources:8;myBaseDamage:3}
WithActivePlayer: 1
WithP1Hand: HMW_042
WithP1GroundArena: LAW_124:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1BASEDMG:0

---

# QuantityIsTheReadiedUnitsCost_NotItsPower
#// HMW_042 — QUANTITY: the heal is the unit's COST, not its power or HP. Industrious Team is 4/7 at cost 8,
#// so a power-based reading heals 4 (10 → 6), an HP-based one 7, and the cost-based one 8 (10 → 2).
#// (Pinned in the first section too; this one names the discrimination.)

## GIVEN
CommonSetup: brk/rrk/{myResources:8;myBaseDamage:10}
WithActivePlayer: 1
WithP1Hand: HMW_042
WithP2GroundArena: LAW_124:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1BASEDMG:2

---

# ATokenCostsZero_NoHeal
#// HMW_042 — VALUE CLASS: a token unit's printed cost is 0. The Battle Droid is readied, the heal is 0, and
#// no base prompt is raised.

## GIVEN
CommonSetup: brk/rrk/{myResources:8;myBaseDamage:10}
WithActivePlayer: 1
WithP1Hand: HMW_042
WithP1GroundArena: TWI_T01:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1BASEDMG:10
P1NODECISION

---

# ADeployedLeaderUnitHealsItsLeaderCost
#// HMW_042 — VALUE CLASS: a deployed LEADER unit is "another unit" and its cost is the leader's printed
#// cost. P2's exhausted deployed Darth Vader (SOR_010, cost 7) is readied → P1 heals 7 (10 → 3).

## GIVEN
CommonSetup: brk/rrk/{myResources:8;myBaseDamage:10;theirLeader:SOR_010:0:1}
WithActivePlayer: 1
WithP1Hand: HMW_042

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_010
P2GROUNDARENAUNIT:0:READY
P1BASEDMG:3

---

# IfYouDo_ACantReadyUnitStaysExhausted_NoHeal
#// ⚠ HMW_042 — "IF YOU DO" measures the OUTCOME. P2's Industrious Team carries SHD_193 Frozen in Carbonite
#// ("attached unit can't ready"): it can be chosen, the ready is refused, it stays EXHAUSTED, and the base
#// is NOT healed.

## GIVEN
CommonSetup: brk/rrk/{myResources:8;myBaseDamage:10}
WithActivePlayer: 1
WithP1Hand: HMW_042
WithP2GroundArena: LAW_124:0:0
WithP2GroundArenaUpgrade: 0:SHD_193

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1BASEDMG:10
P1NODECISION

---

# Decline_NothingReadiedNoHeal
#// HMW_042 — the DECLINE: the unit stays exhausted and nothing is healed.

## GIVEN
CommonSetup: brk/rrk/{myResources:8;myBaseDamage:10}
WithActivePlayer: 1
WithP1Hand: HMW_042
WithP1GroundArena: LAW_124:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1BASEDMG:10
TURNPLAYER:2

---

# NoOtherExhaustedUnit_NoPrompt
#// HMW_042 — NO VALID TARGET: the only other unit is ready and Dooku (exhausted) is excluded — nothing is
#// offered, the action closes normally.

## GIVEN
CommonSetup: brk/rrk/{myResources:8;myBaseDamage:10}
WithActivePlayer: 1
WithP1Hand: HMW_042
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1BASEDMG:10
TURNPLAYER:2

---

# NoDamagedBase_ReadiesWithoutAHealPrompt
#// HMW_042 — the heal has nowhere to go when no base is damaged: the unit is still readied, and no base
#// prompt is raised.

## GIVEN
CommonSetup: brk/rrk/{myResources:8}
WithActivePlayer: 1
WithP1Hand: HMW_042
WithP1GroundArena: LAW_124:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1BASEDMG:0
P2BASEDMG:0
P1NODECISION

---

# RequestBoundary_AcrossBothPicks
#// HMW_042 — the REQUEST-BOUNDARY cell, twice: before the unit pick and before the base pick (the cost
#// must ride the base decision, not a global).

## GIVEN
CommonSetup: brk/rrk/{myResources:8;myBaseDamage:10;theirBaseDamage:5}
WithActivePlayer: 1
WithP1Hand: HMW_042
WithP1GroundArena: LAW_124:0:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myBase-0

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1BASEDMG:2
P2BASEDMG:5

---

# TwinSuns_AFarSeatsUnitAndBase
#// ⚠ HMW_042 — "another unit" and "a base" at three seats. Seat 3's Industrious Team is the only exhausted
#// other unit, and seat 3's base is the only damaged base — so both picks reach seat 3: its unit readies
#// and its base heals 8 (5 → 0). Cannot pass at two seats.

## GIVEN
CommonSetup: brk/rrk/{myResources:8}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_042
WithP3Base: SOR_024:5
WithP3GroundArena: LAW_124:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
SEATCOUNT:3
P3GROUNDARENAUNIT:0:READY
P3BASEDMG:0
P1BASEDMG:0

---

# TeamSuns_ATeammatesUnitIsAnotherUnit
#// ⚠ HMW_042 — a TEAMMATE's unit is "another unit" (unqualified). Seat 3's exhausted Industrious Team is
#// the only candidate; readying it heals P1's base 8.

## GIVEN
CommonSetup: brk/rrk/{myResources:8;myBaseDamage:10}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_042
WithP3Base: SOR_024
WithP4Base: SOR_024
WithP3GroundArena: LAW_124:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:READY
P1BASEDMG:2
