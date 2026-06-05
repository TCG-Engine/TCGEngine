# SOR_116 Steadfast Battalion — Unit 5/5, Ground, Overwhelm.
# "On Attack: If you control a leader unit, give a friendly unit +2/+2 for this phase."
# P1's leader is deployed (controls a leader unit) → condition met.
# Steadfast Battalion is the only valid friendly (non-leader) target → auto-buffs itself +2/+2 = 7/7.
# The buff applies BEFORE combat damage, so its attack on the enemy base deals 7.

## GIVEN
P1LeaderBase: SOR_009:1:1:1/SOR_024
P2LeaderBase: SOR_014/SOR_024
SkipPreGame: true
WithP1GroundArena: SOR_116:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_116
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:7
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:7
