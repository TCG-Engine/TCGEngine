# ASH_010 Bo-Katan Kryze (deployed) — On Attack fizzle: with no friendly space unit, no
# Mandalorian token is created (ground count stays 1).

## GIVEN
CommonSetup: ggw/brk/{
  myLeader:ASH_010:1:1:1
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:1
