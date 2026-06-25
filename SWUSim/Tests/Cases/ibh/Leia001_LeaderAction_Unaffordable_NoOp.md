# IBH_001 Leia Organa — with no ready resource, the Action can't pay its 1-resource cost: full no-op
#   (Leia stays ready, the unit is not healed).

## GIVEN
CommonSetup: ggw/brk/{
  myLeader:IBH_001
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:2

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:READY
