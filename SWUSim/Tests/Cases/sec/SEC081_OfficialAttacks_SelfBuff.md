# SEC_081 Major Partagaz (Ground, 0/6) — Overwhelm + "When another friendly Official unit attacks:
#   this unit gets +2/+2 for this phase." P1's SEC_041 (an Official, power 1) attacks P2's base →
#   SEC_081 reacts and becomes 2/8 for the phase.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_081:1:0
WithP1GroundArena: SEC_041:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:1
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:8
