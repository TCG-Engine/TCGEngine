# SHD_241 Kragan Gorr — "When an enemy unit attacks your base: Give a Shield token to a friendly unit in
# the same arena as the attacker." P1 passes; P2's SHD_095 (ground) attacks P1's base; Kragan (the only
# friendly ground unit) is shielded.

## GIVEN
CommonSetup: yyk/yyk
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: SHD_241:1:0
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
