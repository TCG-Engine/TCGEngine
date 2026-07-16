# NoTrooper_FullCost
#// SOR_248 Volunteer Soldier — no Trooper controlled → full cost 3.
#// P1 controls only Restored ARC-170 (SOR_044, Rebel/Vehicle — NOT a Trooper), so
#// the discount does not apply. With only 2 ready resources the cost-3 play is a
#// silent no-op: SOR_248 stays in hand, no new ground unit, resources untouched.
#// (Contrast with VolunteerSoldier_TrooperDiscount: the trait check is what matters.)

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
