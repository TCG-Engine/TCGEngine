# Advantage token (ASH_T02) — a +1/+0 Token Upgrade that stacks. Marine A has 1 token (3→4 power),
# Marine B has 2 tokens (3→5 power). HP is unaffected (+0).

## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: SOR_095:1:0          # Marine A (3/3, index 0)
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP1GroundArena: SOR_095:1:0          # Marine B (3/3, index 1)
WithP1GroundArenaUpgrade: 1:ASH_T02
WithP1GroundArenaUpgrade: 1:ASH_T02

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:2
