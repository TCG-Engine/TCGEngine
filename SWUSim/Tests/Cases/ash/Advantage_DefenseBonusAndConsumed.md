# Advantage token (ASH_T02) — applies on DEFENSE and is consumed when the host's defense ends. P1 Marine
# (3/3) attacks P2's 1/5 wall (ASH_036) that has 1 Advantage token. The wall defends at 1+1 = 2 power, so
# the attacker takes 2 counter damage (proves the +1 applied on defense). After combat the wall's token
# defeats → 0 tokens left, wall took 3 combat damage and survives (5 HP).

## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: SOR_095:1:0          # Marine attacker (3/3)
WithP2GroundArena: ASH_036:2:0          # 1/5 wall (defender)
WithP2GroundArenaUpgrade: 0:ASH_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P2GROUNDARENAUNIT:0:POWER:1
