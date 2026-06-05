# SOR_148 Guerilla Attack Pod (4/6) — Grit bonus applies to base attack damage.
# GAP is ready with 2 damage: Grit gives +2 power (4 + 2 = 6).
# Attacking P2's base should deal 6 damage, not 4.

## GIVEN
P1LeaderBase: SOR_014/SOR_024
P2LeaderBase: SOR_014/SOR_024
SkipPreGame: true
WithP1GroundArena: SOR_148:1:2

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6
