# SOR_211 Gamorrean Guards (4/4) guard — "While you control another Cunning unit,
# this unit gains Sentinel." P2 controls Gamorrean Guards + another Cunning unit
# (SOR_217), so the Guards have Sentinel. P1's base-attack is force-redirected onto
# the Guards (only valid target — SOR_217 is non-Sentinel and can't be attacked
# while a Sentinel unit is present). Combat uses printed HP: P1's 3/3 attacker deals
# 3 to the 4/4 Guards (survive); the Guards deal 4 back (attacker dies). Base 0.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # attacker (3/3)
WithP2GroundArena: SOR_211:1:0    # Gamorrean Guards (4/4, Cunning)
WithP2GroundArena: SOR_217:1:0    # another Cunning unit

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:2
