# SOR_148 Guerilla Attack Pod (4/6) — Grit: +1 power per damage on this unit.
# With 2 damage, base power 4 + Grit bonus 2 = 6.

## GIVEN
P1LeaderBase: SOR_014/SOR_024
P2LeaderBase: SOR_014/SOR_024
SkipPreGame: true
WithP1GroundArena: SOR_148:1:2

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:6
