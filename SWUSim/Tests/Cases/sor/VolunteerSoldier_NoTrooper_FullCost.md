# SOR_248 Volunteer Soldier — no Trooper controlled → full cost 3.
# P1 controls only Restored ARC-170 (SOR_044, Rebel/Vehicle — NOT a Trooper), so
# the discount does not apply. With only 2 ready resources the cost-3 play is a
# silent no-op: SOR_248 stays in hand, no new ground unit, resources untouched.
# (Contrast with VolunteerSoldier_TrooperDiscount: the trait check is what matters.)

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
