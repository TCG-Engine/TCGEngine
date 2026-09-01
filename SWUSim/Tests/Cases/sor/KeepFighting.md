# NoEligibleTargets
#// SOR_169 Keep Fighting (Event, cost 2, [Aggression], Tactic) — "Ready a unit with 3 or less power."
#// COVERAGE: offer=Offer_UnqualifiedTargetSpansBothSides (menu asserted on a PENDING decision; the
#//           4-power unit is the excluded control) + TokenUnitIsALegalTarget (token units are in the
#//           pool) · boundary pair=Boundary_PowerThree_Readies_PowerFour_DoesNot (printed 3 vs 4) +
#//           Boundary_CurrentPowerNotPrinted_ExperienceLiftsUnitOutOfRange (current 3 vs 4 via a token)
#//           + NoEligibleTargets (zero legal targets) · decline=N/A — the printed text carries no
#//           "you may" and readying is not a cost, so the choose is a mandatory MZCHOOSE with no
#//           decline branch to take · control change=N/A — the target word is an unqualified "a unit"
#//           with no controller clause, and readying is applied to the OBJECT (Status), never resolved
#//           against a seat; Offer_UnqualifiedTargetSpansBothSides proves no controller filter exists,
#//           which is the whole of what an owner≠controller board could change here · request
#//           boundary=N/A — a single-request event: the ready lands inside the same action as the play
#//           and nothing pends across a serialize (the only pending state is the target choice, which
#//           Offer_UnqualifiedTargetSpansBothSides holds open and asserts directly)
#// SOR_148 (Guerilla Attack Pod, printed 4/6 with Grit) has power 4 > 3; Keep Fighting fizzles.

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_169}
WithP1GroundArena: SOR_148:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:READY

---

# ReadiesUnit
#// SOR_169 Keep Fighting — readies the only eligible unit (power ≤ 3).
#// SOR_095 (Battlefield Marine, 3/3) is exhausted; Keep Fighting auto-picks it and readies it.

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_169}
WithP1GroundArena: SOR_095:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:READY

---

# TokenUnitIsALegalTarget
#// The Open Fire family fix: "Ready a UNIT with 3 or less power" is unqualified, so an exhausted Clone
#// Trooper token (TWI_T02, 2/2) is a legal target and readies. Its exhausted real neighbour proves the
#// choice was genuinely offered (two legal targets — no auto-resolve) — and being the FIRST section to
#// offer two targets, this also caught the block-ordering bug where the READY_UNIT continuation
#// (block 0) jumped the MZCHOOSE (block 1) and the pick readied nothing.

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_169}
P1OnlyActions: true
WithP1GroundArena: [TWI_T02:0:0 SOR_095:0:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_T02
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# Boundary_PowerThree_Readies_PowerFour_DoesNot
#// SOR_169 Keep Fighting — "Ready a unit with 3 or less power." THE BOUNDARY PAIR, both halves on one
#// board: SOR_095 Battlefield Marine is power 3 (legal, the N side) and SOR_148 Guerilla Attack Pod is
#// power 4 (illegal, the N+1 side). Both are exhausted, so if the 4-power unit were wrongly in the pool
#// the choice would stall on a two-target prompt instead of auto-resolving. Exactly one legal target →
#// auto-resolve is the assertion: the 3-power unit readies, the 4-power unit stays exhausted, and no
#// decision is left pending.
#// ⚠ SOR_148 is printed 4/6 with Grit (+1/+0 per damage) — undamaged its power is 4, one over the line.

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_169}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:0:0 SOR_148:0:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:CARDID:SOR_148
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:EXHAUSTED
P1NODECISION

---

# Boundary_CurrentPowerNotPrinted_ExperienceLiftsUnitOutOfRange
#// SOR_169 Keep Fighting reads CURRENT power, not printed power. Two identical Battlefield Marines
#// (printed 3/3); the first carries an Experience token (SOR_T01, +1/+1) so its CURRENT power is 4 —
#// one over the "3 or less" line — while the second is still 3. Only the plain Marine is a legal
#// target, so the pick auto-resolves onto it: index 0 stays exhausted at power 4, index 1 readies.
#// The mirror of Boundary_PowerThree_Readies_PowerFour_DoesNot: there the printed value crossed the
#// line, here a token does.

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_169}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:0:0 SOR_095:0:0]
WithP1GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:READY
P1NODECISION

---

# Offer_UnqualifiedTargetSpansBothSides
#// THE OFFER CELL — asserted as a MENU, not an outcome. "Ready A UNIT with 3 or less power" names no
#// controller, so per the unqualified-target rule the pool is every unit on the table that is at or
#// under the power line: P1's 3-power Marine AND P2's 3-power Consular Security Force. P1's 4-power
#// Guerilla Attack Pod is the EXCLUDED control that proves the power gate is load-bearing rather than
#// the pool simply being "everything".
#// The decision is deliberately left PENDING (no answer) — assertions evaluate at end state, so an
#// answered choice has no offer left to inspect. The resolution lives in the sections above.

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_169}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:0:0 SOR_148:0:0]
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
