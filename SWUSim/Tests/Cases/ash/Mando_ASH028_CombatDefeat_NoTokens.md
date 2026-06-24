# ASH_028 Paz Vizsla (Ground, 5/7, Sentinel) — When Defeated: if NOT defeated by combat damage, create
# 2 Mandalorian tokens. Here it IS defeated by combat (pre-damaged to 1 HP, attacks SEC_080 and dies to
# the counter), so NO tokens are created. (ASH_028 deals 5 → SEC_080 dies too.)

## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: ASH_028:1:6
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
