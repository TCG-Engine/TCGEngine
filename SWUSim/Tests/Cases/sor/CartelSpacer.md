# AnotherCunning_ExhaustsEnemy
#// SOR_178 Cartel Spacer (2/3, Space) — When Played: If you control another [Cunning] unit,
#// exhaust an enemy unit that costs 4 or less. P1 already controls Outer Rim Headhunter
#// (SOR_208, Cunning), so the condition holds; the enemy Battlefield Marine (cost 2) is the
#// only ≤4-cost enemy unit and is exhausted. Automatic (not optional).
#// COVERAGE: offer=Offer_EnemyUnitsCostFourOrLess_ExpensiveOnesAndFriendliesExcluded (pending
#//           SELECTABLEEXACT — a cost-8 enemy and P1's own Cunning enabler are the excluded targets) ·
#//           reqboundary=RequestBoundary_ExhaustLandsAfterTheAnswerCrossesTheBoundary ·
#//           control=ControlChange_AStolenCunningUnitStillSatisfiesTheGate ("if you CONTROL another
#//           Cunning unit" reads control, not ownership) · boundary pair=CostExactlyFour_IsExhausted
#//           (cost 4, inclusive edge) vs CostFive_NoLegalTarget_EnemyStaysReady (cost 5, one over);
#//           the gate's own positive/negative pair is AnotherCunning_ExhaustsEnemy vs NoCunning_NoOp ·
#//           decline=N/A — the printed text has no "you may"; the exhaust is mandatory once the
#//           Cunning gate opens, so there is no decline branch.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_178
WithP1SpaceArena: SOR_208:1:0     # another Cunning unit (condition) — idx 0
WithP2GroundArena: SEC_080:1:0    # enemy unit, cost 2 (≤4) — exhaust target

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# NoCunning_NoOp
#// SOR_178 Cartel Spacer — the exhaust is conditional on controlling ANOTHER Cunning unit.
#// Here P1's only other unit is Battlefield Marine (Command, not Cunning), so the condition
#// fails and the enemy unit stays ready. (Cartel Spacer is itself Cunning, but "another"
#// excludes it.) Absence guard.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_178
WithP1GroundArena: SEC_080:1:0    # friendly Command unit (NOT Cunning)
WithP2GroundArena: SEC_080:1:0    # enemy unit — must stay ready

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:READY

---

# Offer_EnemyUnitsCostFourOrLess_ExpensiveOnesAndFriendliesExcluded
#// Intended: "exhaust an ENEMY unit that COSTS 4 OR LESS" — two filters at once. With the Cunning
#// condition met, the pool is P2's Dark Trooper (cost 2) and Consular Security Force (cost 4) only:
#// P2's Blizzard Assault AT-AT (cost 8) is priced out and P1's own Outer Rim Headhunter (cost 2,
#// the Cunning enabler) is excluded for being friendly. Two legal targets keep the pick interactive,
#// so the decision is left PENDING and the offer itself is the assertion.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_178
WithP1SpaceArena: SOR_208:1:0     # another Cunning unit — satisfies the gate
WithP2GroundArena: SEC_080:1:0    # cost 2 — legal
WithP2GroundArena: SOR_046:1:0    # cost 4 — legal (the boundary value itself)
WithP2GroundArena: SOR_088:1:0    # cost 8 — priced out

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# CostExactlyFour_IsExhausted
#// SOR_178 — boundary N. "Costs 4 OR LESS" includes 4 exactly: the Consular Security Force (cost 4)
#// is the only enemy unit, so the pick auto-resolves onto it and it exhausts.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_178
WithP1SpaceArena: SOR_208:1:0
WithP2GroundArena: SOR_046:1:0    # cost 4 — the inclusive edge

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# CostFive_NoLegalTarget_EnemyStaysReady
#// SOR_178 — boundary N+1. The only enemy unit is a Cantina Bouncer (cost 5), one over the "4 or
#// less" line, so it is not a legal target: it stays READY, no decision is raised, and the Cartel
#// Spacer still enters play normally. This is the cell that proves the cost filter is load-bearing
#// rather than cosmetic.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_178
WithP1SpaceArena: SOR_208:1:0
WithP2GroundArena: SOR_202:1:0    # cost 5 — one over the edge

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_202
P2GROUNDARENAUNIT:0:READY
P1SPACEARENACOUNT:2
P1NODECISION

---

# RequestBoundary_ExhaustLandsAfterTheAnswerCrossesTheBoundary
#// SOR_178 — with two legal targets the pick is a real prompt, and in production that prompt ends the
#// request: the answer arrives in a fresh process. The chosen Security Force still exhausts, the
#// unchosen Dark Trooper stays ready, and the priced-out AT-AT is untouched.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_178
WithP1SpaceArena: SOR_208:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:CARDID:SOR_046
P2GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:2:READY

---

# ControlChange_AStolenCunningUnitStillSatisfiesTheGate
#// SOR_178 — "If you CONTROL another [Cunning] unit" reads control, not ownership. The Gamorrean
#// Guards P1 controls but P2 OWNS (the end state after a take-control effect) are a Cunning unit P1
#// controls, so the gate opens and the lone enemy Dark Trooper (cost 2) auto-exhausts. Compare
#// NoCunning_NoOp, where the gate is shut and the same enemy stays ready.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_178
WithP1GroundArenaControlled: SOR_211:2    # Cunning unit, owned by P2, controlled by P1
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_211
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION
