# Advantage token (ASH_T02) — consumed when the host's ATTACK ends. A Marine (3/3) with 2 Advantage
# tokens (5 power) attacks the base for 5, then both tokens defeat → power back to 3, 0 tokens left.

## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: SOR_095:1:0          # Marine (3/3)
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP1GroundArenaUpgrade: 0:ASH_T02

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:EXHAUSTED
