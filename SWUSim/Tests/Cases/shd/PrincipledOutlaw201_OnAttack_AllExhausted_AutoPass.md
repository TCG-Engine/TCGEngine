# SHD_201 Principled Outlaw — every ground unit already exhausted (including the attacker itself
# after attacking) → nothing valid to exhaust → the "may" auto-passes with no decision.

## GIVEN
CommonSetup: gyw/gyw
P1OnlyActions: true
WithP1GroundArena: SHD_201:1:0
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1NODECISION
