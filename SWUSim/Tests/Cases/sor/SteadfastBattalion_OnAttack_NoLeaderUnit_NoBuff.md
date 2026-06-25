# SOR_116 Steadfast Battalion — absence guard for the conditional On Attack buff.
# P1's leader is NOT deployed (no leader unit controlled) → condition fails → NO buff.
# Steadfast Battalion stays 5/5 and its attack on the enemy base deals 5 (printed power).

## GIVEN
CommonSetup: ggw/grw
SkipPreGame: true
WithP1GroundArena: SOR_116:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_116
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:5
