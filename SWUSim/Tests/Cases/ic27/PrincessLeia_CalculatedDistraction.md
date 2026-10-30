# WhenPlayed_ExhaustsTwoGroundUnits_CombinedFour
#// IC27_159 Princess Leia — Calculated Distraction (5/5 Ground, Cunning/Heroism, Rebel/Official, unique)
#// "When Played/On Attack: Exhaust up to 2 units in the same arena with combined cost 4 or less."
#// One closure on both triggers. Two optional picks in a row: the first over every READY unit of printed cost 4
#// or less (an exhausted unit is never offered — the _SWUBudgetExhaustWeights rule); the second over the ready
#// units in the FIRST pick's arena whose cost fits what is left of the 4. "Same arena" ties the two picks to
#// each other, not to Leia. Friendly, enemy and leader units are all legal ("units", no qualifier); cost is the
#// PRINTED cost (CR 15.5.e), so a token unit costs 0. Both are exhausted together once the picks are made.
#//
#// COVERAGE: positive=WhenPlayed_ExhaustsTwoGroundUnits_CombinedFour (boundary: exactly 4) ·
#//           offer=FirstOffer_ReadyUnitsCostFourOrLess (friendly, both arenas; exhausted / cost 8 / Leia out) +
#//                 SecondOffer_SameArenaWithinWhatIsLeft (space excluded, 2+2 in, 2+3 out = the boundary pair) ·
#//           decline=DeclineFirst_NothingExhausted + StopAfterOne_OnlyTheFirstExhausted ·
#//           no-target=NoLegalUnit_NoPrompt ·
#//           value-class=TokenUnitCostsZero_FitsAfterAFour + DeployedLeaderUnitIsLegal ·
#//           arena=SpacePair_LeiaIsGround ·
#//           dispatch=OnAttack_ExhaustsTwo (the second trigger half) ·
#//           reqboundary=AnsweredAcrossRequestBoundary ·
#//           control=N/A (exhausts in place; no owner-scoped zone) ·
#//           modes=2P only (no player reference, no friendly/enemy wording — "units" is the whole table)

## GIVEN
CommonSetup: yyw/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_159
WithP2GroundArena: [SOR_095:1:0 SOR_247:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
P1NODECISION

---

# FirstOffer_ReadyUnitsCostFourOrLess
#// The first pick: P1's own Stormtrooper (c1), P2's Marine (c2), Consular Security Force (c4 — exactly the
#// budget) and X-Wing in space (c2). NOT the exhausted Swoop Racer (c3), NOT the Industrious Team (c8), NOT
#// Leia herself (enters exhausted, and c5). Left pending.

## GIVEN
CommonSetup: yyw/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_159
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0 LAW_124:1:0 SOR_210:0:0]
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0

---

# SecondOffer_SameArenaWithinWhatIsLeft
#// First pick the Marine (c2, ground) — 2 left. Second pool: the Stormtrooper (c1) and the Underworld Thug (c2,
#// 2+2 = 4: in). NOT the Swoop Racer (c3, 2+3 = 5: out — the boundary pair with the Thug), NOT the X-Wing (c2,
#// fits the cost but is in SPACE), NOT the Marine again. Left pending.

## GIVEN
CommonSetup: yyw/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_159
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_247:1:0 SOR_210:1:0]
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-1
P2GROUNDARENAUNIT:0:READY

---

# DeclineFirst_NothingExhausted
#// "Up to 2" — zero is fine.

## GIVEN
CommonSetup: yyw/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_159
WithP2GroundArena: [SOR_095:1:0 SOR_247:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:READY
P1NODECISION

---

# StopAfterOne_OnlyTheFirstExhausted
#// Declining the second pick still exhausts the first.

## GIVEN
CommonSetup: yyw/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_159
WithP2GroundArena: [SOR_095:1:0 SOR_247:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:EXHAUSTED
P1NODECISION

---

# NoLegalUnit_NoPrompt
#// Nothing ready of cost 4 or less: an exhausted Marine and a ready Industrious Team (c8). No prompt at all.

## GIVEN
CommonSetup: yyw/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_159
WithP2GroundArena: [SOR_095:0:0 LAW_124:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:1:READY

---

# TokenUnitCostsZero_FitsAfterAFour
#// A cost-4 first pick leaves 0 — and a Battle Droid token costs 0, so it is still offered and exhausted. The
#// Stormtrooper (c1) is not (4+1 = 5).

## GIVEN
CommonSetup: yyw/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_159
WithP2GroundArena: [SOR_046:1:0 TWI_T01:1:0 SOR_128:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:2:READY
P1NODECISION

---

# DeployedLeaderUnitIsLegal
#// "units" includes leader units: P2's deployed Sabine Wren (leader cost 4) is offered next to the Marine, and
#// exhausting her leaves 0 — nothing else fits, so there is no second prompt.

## GIVEN
CommonSetup: yyw/rrk/{myResources:5;theirLeader:SOR_014:1:1:0:0:0}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_159
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_014
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY
P1NODECISION

---

# SpacePair_LeiaIsGround
#// The two units share an arena with EACH OTHER — space here — while Leia is a ground unit.

## GIVEN
CommonSetup: yyw/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_159
WithP2SpaceArena: [SOR_225:1:0 SOR_237:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:theirSpaceArena-1

## EXPECT
P2SPACEARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:1:EXHAUSTED
P1NODECISION

---

# OnAttack_ExhaustsTwo
#// The On Attack half: Leia (ready, in play) attacks P2's base; she exhausts the Marine and the Thug, then
#// deals her 5.

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_159:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_247:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
P2BASEDMG:5
P1NODECISION

---

# AnsweredAcrossRequestBoundary
#// WhenPlayed_ExhaustsTwoGroundUnits_CombinedFour with a request boundary before each pick — the first pick
#// rides the second decision's handler param, not memory.

## GIVEN
CommonSetup: yyw/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_159
WithP2GroundArena: [SOR_095:1:0 SOR_247:1:0]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
P1NODECISION
