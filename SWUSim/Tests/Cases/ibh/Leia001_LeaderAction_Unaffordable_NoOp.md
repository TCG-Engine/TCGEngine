# IBH_001 Leia Organa — with no ready resource, the Action can't pay its 1-resource cost: full no-op
#   (Leia stays ready, the unit is not healed).

## GIVEN
P1LeaderBase: IBH_001/SOR_024
P2LeaderBase: SOR_010/SOR_020
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:2

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:READY
