# SOR_248 Volunteer Soldier — "costs 1 less if you control a Trooper unit"
# (M9 PlayCostModifier). Volunteer Soldier is cost 3, no aspects (no penalty).
# P1 controls Battlefield Marine (SOR_095, Rebel/Trooper) → SOR_248 costs 2.
# With exactly 2 ready resources the play succeeds: it enters the ground arena
# (count 1 Trooper + 1 = 2) and both resources are spent (0 ready left).

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
