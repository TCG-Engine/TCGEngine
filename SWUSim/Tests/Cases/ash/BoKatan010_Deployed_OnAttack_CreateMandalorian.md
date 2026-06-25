# ASH_010 Bo-Katan Kryze (deployed) — On Attack: if you control a unit in each arena, create a
# Mandalorian token. Bo-Katan (ground) + an X-Wing (space) → one unit in each arena → token.

## GIVEN
CommonSetup: ggw/brk/{
  myLeader:ASH_010:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
