# SOR_057 Protector grants Sentinel to its host (upgrade keyword-grant guard)
# P2 has a vanilla Battlefield Marine (SOR_095, 3/3, no innate Sentinel) with
# Protector (SOR_057, +1/+1) attached → it becomes a 4/4 and gains Sentinel. While a
# Sentinel unit is in the arena, P1 cannot attack P2's base — the base-attack is
# force-redirected onto the only valid target (the Sentinel host).
# Combat lethality uses CURRENT HP: the attacker (3/3) takes the host's 4 power and dies;
# the host (4/4) takes the attacker's 3 power and SURVIVES at 3 damage. P2's base takes 0.
# (Contrast: without Protector the same attack would deal 3 to P2's base and leave
# both units alive — proving the redirect comes from the granted Sentinel.)

## GIVEN
CommonSetup: yrw/yrw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # Battlefield Marine (3/3), ready
WithP2GroundArena: SOR_095:1:0    # Battlefield Marine (3/3), ready
WithP2GroundArenaUpgrade: 0:SOR_057   # Protector → host gains Sentinel

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENACOUNT:0
