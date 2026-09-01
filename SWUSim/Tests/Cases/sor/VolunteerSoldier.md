# NoTrooper_FullCost
#// SOR_248 Volunteer Soldier — no Trooper controlled → full cost 3.
#// P1 controls only Restored ARC-170 (SOR_044, Rebel/Vehicle — NOT a Trooper), so
#// the discount does not apply. With only 2 ready resources the cost-3 play is a
#// silent no-op: SOR_248 stays in hand, no new ground unit, resources untouched.
#// (Contrast with VolunteerSoldier_TrooperDiscount: the trait check is what matters.)
#// COVERAGE: offer=N/A (a passive play-cost modifier — it queues no decision and has no target
#//           pool to inspect) · decline=N/A (the reduction is not optional and there is nothing
#//           to pay for) · control=EnemyTrooperOnly_NoDiscount ("if YOU control a Trooper" — the
#//           opponent's Trooper does not discount) · boundary=NoTrooper_FullCost vs
#//           TrooperDiscount (cost 3 vs 2 off the same 2 resources) and
#//           TwoTroopers_DiscountStillOnlyOne (1 vs 2 Troopers → the same flat -1) ·
#//           reqboundary=N/A (the modifier is evaluated inside the single play request; no
#//           decision splits the cost determination)

## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:SOR_248}
P1OnlyActions: true
WithP1SpaceArena: SOR_044:1:0    # Restored ARC-170 — a unit, but not a Trooper

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:2

---

# TrooperDiscount
#// SOR_248 Volunteer Soldier — "costs 1 less if you control a Trooper unit"
#// (M9 PlayCostModifier). Volunteer Soldier is cost 3, no aspects (no penalty).
#// P1 controls Battlefield Marine (SOR_095, Rebel/Trooper) → SOR_248 costs 2.
#// With exactly 2 ready resources the play succeeds: it enters the ground arena
#// (count 1 Trooper + 1 = 2) and both resources are spent (0 ready left).

## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:SOR_248}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0    # Battlefield Marine — a friendly Trooper

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:0
P1HANDCOUNT:0

---

# EnemyTrooperOnly_NoDiscount
#// SOR_248 Volunteer Soldier — "if YOU control a TROOPER unit". The negative that proves the
#// controller half of the gate: the ONLY Trooper on the board is P2's Battlefield Marine, so P1
#// pays the full 3. With 2 ready resources the play is a silent no-op — SOR_248 stays in hand,
#// no new friendly ground unit, resources untouched. (Contrast TrooperDiscount, where the same
#// 2 resources buy the discounted play off a FRIENDLY Trooper.)

## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:SOR_248}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0    # the only Trooper in play — and it is the OPPONENT'S

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:2
P2GROUNDARENACOUNT:1

---

# TwoTroopers_DiscountStillOnlyOne
#// SOR_248 Volunteer Soldier — quantity discrimination: the reduction is a flat "costs 1 less",
#// not 1 per Trooper. P1 controls TWO friendly Troopers (Imperial Dark Trooper + Death Star
#// Stormtrooper) and has 3 resources; the cost is 2 (3 − 1), so exactly one resource is left
#// ready. A per-Trooper stack would have made it cost 1 and left 2 ready.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:SOR_248}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0    # friendly Trooper #1
WithP1GroundArena: SOR_128:1:0    # friendly Trooper #2

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1RESAVAILABLE:1
P1HANDCOUNT:0
