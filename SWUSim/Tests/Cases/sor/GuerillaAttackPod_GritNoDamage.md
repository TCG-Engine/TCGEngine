# SOR_148 Guerilla Attack Pod (4/6) — Grit baseline: 0 damage means no Grit bonus.
# Power equals base 4.

## GIVEN
P1LeaderBase: SOR_001/SOR_024
P2LeaderBase: SOR_014/SOR_024
SkipPreGame: true
WithP1GroundArena: SOR_148:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
