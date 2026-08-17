# KashyyykBase_FriendlyUnitDealsItsPowerToAnEnemy
#// HMW_151 Overgrowth (Command, Disaster, 5-cost Event) —
#// "If you control a Kashyyyk base, a friendly unit deals damage equal to its power to an enemy unit.
#//  Resource this card."
#// COVERAGE: offer=OfferPool_DealerIsAnyFriendlyUnit + OfferPool_TargetIsAnyEnemyUnit (both picks
#//           asserted while pending) · negative=NoKashyyykBase_NoDamage_ButStillResourced (the gate) ·
#//           ⚠ unconditional-clause=NoKashyyykBase_* + NoEnemyUnit_* + NoFriendlyUnit_* — "Resource this
#//           card" is a SEPARATE SENTENCE and must happen even when the damage clause never runs ·
#//           value-class=DamageUsesCURRENTPower (an upgrade raises it) ·
#//           control=PlayedByP2_ResourcesToP2 · reqboundary=SurvivesTheRequestBoundary ·
#//           decline=N/A (mandatory — no "you may" in either clause)
#// P1 pays exactly 5, exhausting every resource, so RESCOUNT 6 / RESAVAILABLE 0 shows the spent event
#// arriving in the resource zone EXHAUSTED (there is no "ready" rider here, unlike HMW_123).
#// DISCARDCOUNT 0 is the other half of that: it went to resources INSTEAD of the discard.
#// SOR_095 (3 power) deals 3 to LAW_124 (4/7), which survives so the damage is readable.

## GIVEN
CommonSetup: ggw/bgw/{myBase:HMW_021;myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: HMW_151

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1RESCOUNT:6
P1RESAVAILABLE:0
P1DISCARDCOUNT:0

---

# NoKashyyykBase_NoDamage_ButStillResourced
#// HMW_151 — ⚠ THE KEY CELL. The damage clause is gated on a Kashyyyk base, but "Resource this card" is
#// a separate, UNCONDITIONAL sentence. With a non-Kashyyyk base the enemy takes nothing AND the card
#// still becomes a resource.
#// An implementation that wraps the whole ability in the gate passes every other section and fails here.

## GIVEN
CommonSetup: ggw/bgw/{myBase:SOR_029;myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: HMW_151

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1RESCOUNT:6
P1DISCARDCOUNT:0
P1NODECISION

---

# OfferPool_DealerIsAnyFriendlyUnit
#// HMW_151 — the FIRST pick. "A friendly unit" names no arena, so both friendly arenas are in and every
#// enemy unit is out. Left UNANSWERED so the pending pool is the assertion.

## GIVEN
CommonSetup: ggw/bgw/{myBase:HMW_021;myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: LAW_124:1:0
WithP2SpaceArena: SEC_213:1:0
WithP1Hand: HMW_151

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# OfferPool_TargetIsAnyEnemyUnit
#// HMW_151 — the SECOND pick, after the dealer is chosen. "An enemy unit" spans both enemy arenas and
#// excludes every friendly unit, including the dealer itself.

## GIVEN
CommonSetup: ggw/bgw/{myBase:HMW_021;myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: LAW_124:1:0
WithP2SpaceArena: SEC_213:1:0
WithP1Hand: HMW_151

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirSpaceArena-0

---

# DamageUsesCURRENTPower
#// HMW_151 — "equal to ITS POWER" is the dealer's CURRENT power, not its printed power. SOR_095 is
#// printed 3 and wears SOR_166 Infiltrator's Skill (+1/+0), so it deals 4.
#// The value-class cell: a printed-power implementation passes the first section and fails here.

## GIVEN
CommonSetup: ggw/bgw/{myBase:HMW_021;myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_166
WithP2GroundArena: LAW_124:1:0
WithP1Hand: HMW_151

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# NoEnemyUnit_ClauseFizzles_ButStillResourced
#// HMW_151 — the gate is satisfied and a dealer exists, but there is NO enemy unit, so the damage clause
#// has nothing to resolve against. "Resource this card" is unaffected.

## GIVEN
CommonSetup: ggw/bgw/{myBase:HMW_021;myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: HMW_151

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:6
P1DISCARDCOUNT:0
P1NODECISION

---

# NoFriendlyUnit_ClauseFizzles_ButStillResourced
#// HMW_151 — the mirror: a Kashyyyk base and an enemy unit, but NO friendly unit to deal the damage.
#// The clause fizzles and the card is still resourced.

## GIVEN
CommonSetup: ggw/bgw/{myBase:HMW_021;myResources:5}
P1OnlyActions: true
WithP2GroundArena: LAW_124:1:0
WithP1Hand: HMW_151

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1RESCOUNT:6
P1DISCARDCOUNT:0
P1NODECISION

---

# PlayedByP2_ResourcesToP2AndReadsP2sBase
#// HMW_151 — "you control a Kashyyyk base" and "resource this card" are both the CASTER's. P2 plays it
#// with the Kashyyyk base on P2's side; P2's resource zone grows and P1's is untouched.

## GIVEN
CommonSetup: bgw/ggw/{myBase:SOR_029;theirBase:HMW_021;theirResources:5}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SOR_095:1:0
WithP1GroundArena: LAW_124:1:0
WithP2Hand: HMW_151

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2RESCOUNT:6
P2DISCARDCOUNT:0

---

# SurvivesTheRequestBoundary
#// HMW_151 — the request-boundary cell. The dealer is chosen, the process is torn down, and the second
#// pick must still resolve against the right dealer: the damage carried across is the DEALER's power,
#// which only survives if it rides the decision rather than a global.

## GIVEN
CommonSetup: ggw/bgw/{myBase:HMW_021;myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: HMW_151

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1RESCOUNT:6
