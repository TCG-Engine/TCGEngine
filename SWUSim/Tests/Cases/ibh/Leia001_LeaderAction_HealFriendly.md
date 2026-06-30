# IBH_001 Leia Organa — Leader Action [1 resource, Exhaust]: heal 1 damage from a friendly unit. A
#   damaged 3/3 (2 damage) heals to 1; Leia exhausts and 1 resource is spent.

## GIVEN
CommonSetup: ggw/brk/{
  myLeader:IBH_001
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SOR_095:1:2

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
