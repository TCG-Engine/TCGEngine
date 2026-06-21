# LOF_026 Fortress Vader — the trigger only fires for a friendly *Force* unit. A non-Force attacker
# (Battlefield Marine SOR_095, no Force trait) attacking past the same base must NOT create the Force.
# (Absence guard — passes pre-implementation; stays meaningful once the positive case works.)

## GIVEN
P1LeaderBase: SOR_002/LOF_026
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NOFORCE
P2BASEDMG:3
