# Modal_BuffAndExhaust
#// SOR_203 Cunning (event, cost 4) — Give a unit +4/+0 this phase + Exhaust up to 2 units. SEC_080 (3/3)
#// gets +4/+0 (POWER 7) then is exhausted. Cunning is off-aspect for SOR_009 → cost 6.
#// COVERAGE: offer=BuffOffer_IncludesLeaderUnitAndEnemyUnits + ReturnOffer_PowerFourInSixOut +
#//           CannotReturnEnemyUnitWithLeaderPilotAttached (all pending SELECTABLEEXACT) ·
#//           reqboundary=all multi-answer sections (each mode pick / target answer is a separate
#//           request) · boundary pair=ReturnOffer_PowerFourInSixOut (power 4 in / power 6 out, at the
#//           printed "4 or less" line) + Exhaust "up to 2" upper bound in
#//           Exhaust_TwoEnemyUnits_ThenDiscard · decline=N/A ("Choose 2" is mandatory — both modes
#//           must be picked and each mode's effect is not a "you may"; the exhaust mode's "up to"
#//           lower bound is exercised implicitly by Modal_BuffAndExhaust picking a single unit) ·
#//           control=N/A (one-shot event; the phase buff carries no per-unit marker that could
#//           outlive a control change inside the same phase)

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_203
WithP1Resources: 8
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:BuffUnit
- P1>AnswerDecision:Exhaust
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:EXHAUSTED
P1DISCARDCOUNT:1

---

# Modal_ReturnUnitAndDiscard
#// SOR_203 Cunning — Opponent discards a random card + Return a ≤4-power non-leader unit to hand. P1
#// resolves Discard first (P2 holds exactly 1 card → deterministic), then ReturnUnit bounces SOR_128 (3
#// power) to P2's hand. P2 ends with 1 card in hand (the bounced unit) and 1 in discard.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_203
WithP1Resources: 8
WithP2Hand: SOR_095
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Discard
- P1>AnswerDecision:ReturnUnit

## EXPECT
P2HANDCOUNT:1
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1

---

# BuffOffer_IncludesLeaderUnitAndEnemyUnits
#// Intended: "Give a unit +4/+0" — ANY unit qualifies: deployed leader units and enemy units
#// included. P1's deployed leader and ground unit plus P2's unit are all offered; the target
#// decision is left pending so the offer itself is asserted.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021;
  myLeaderDeployed:true
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_203
WithP1Resources: 8
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:BuffUnit

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0

---

# ReturnOffer_PowerFourInSixOut
#// Intended: "Return a non-leader unit with 4 or less power" — the boundary sits at exactly 4:
#// Wampa (4/5) is IN the pool, AT-ST (6/7) is OUT. My own SEC_080 (3/3) is the second candidate
#// that keeps the pick interactive; the decision is left pending so the offer itself is asserted.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_203
WithP1Resources: 8
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:ReturnUnit

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# Exhaust_TwoEnemyUnits_ThenDiscard
#// Intended: the exhaust mode reaches ENEMY units across both arenas at its "up to 2" cap; the
#// discard mode then takes the opponent's only hand card (deterministic random). AT-ST and the
#// A-Wing both end exhausted.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_203
WithP1Resources: 8
WithP2Hand: SOR_095
WithP2GroundArena: SOR_232:1:0
WithP2SpaceArena: SOR_141:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Exhaust
- P1>AnswerDecision:theirGroundArena-0&theirSpaceArena-0
- P1>AnswerDecision:Discard

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:0:EXHAUSTED
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P1NODECISION

---

# CannotReturnEnemyUnitWithLeaderPilotAttached
#// Intended: a unit with a LEADER deployed on it as a Pilot IS a leader unit — even at 1 power the
#// enemy A-Wing hosting Asajj Ventress can't be bounced. The return pool holds only my two
#// ordinary units; the decision is left pending so the exclusion is asserted on the offer.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021;
  theirLeader:JTL_001;
  theirLeaderDeployedPilot:true
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_203
WithP1Resources: 8
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_164:1:0
WithP2SpaceArena: SOR_141:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:ReturnUnit

## EXPECT
P2SPACEARENAUNIT:0:ISLEADERUNIT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# SimulateRequestBoundary_ModeChainAndMultiTarget
#// SOR_203 Cunning — every step of the modal chain (first mode pick, its target answer, second mode pick)
#// is a separate request in production, so which modes were already chosen and how many exhaust picks
#// remain must be serialized, not parked in a transient global. Mirrors Exhaust_TwoEnemyUnits_ThenDiscard
#// with a boundary before each answer: both enemy units still end exhausted and the discard mode still
#// takes P2's only hand card.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_203
WithP1Resources: 8
WithP2Hand: SOR_095
WithP2GroundArena: SOR_232:1:0
WithP2SpaceArena: SOR_141:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:Exhaust
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0&theirSpaceArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:Discard

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:0:EXHAUSTED
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P1NODECISION
