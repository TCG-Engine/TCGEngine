# ASH_010 Bo-Katan Kryze (deployed) — passive: other friendly Mandalorian units get +1/+0.
# The Mandalorian token (ASH_T01, 2/2) becomes 3/2.

## GIVEN
CommonSetup: ggw/brk/{
  myLeader:ASH_010:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: ASH_T01:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:2
