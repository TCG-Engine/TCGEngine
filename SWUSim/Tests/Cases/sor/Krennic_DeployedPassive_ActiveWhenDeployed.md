# Krennic's passive is active on both leader and leader-unit side.
# When Krennic is deployed in the arena, another friendly damaged unit still gets +1/+0.

## GIVEN
P1LeaderBase: SOR_001:1:1:1/SOR_024
P2LeaderBase: SOR_014/SOR_024
SkipPreGame: true
WithP1GroundArena: SOR_001:1:0
WithP1GroundArena: SOR_095:1:1

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:POWER:4
