# Guard: a NON-Force base (SOR_021) must not grant the Force even when a Force unit (LOF_112) attacks.
# Proves the trigger is scoped to the common-Force-base set, not "any base when a Force unit attacks."
# (Absence guard — passes pre-implementation.)

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_112:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NOFORCE
P2BASEDMG:2
