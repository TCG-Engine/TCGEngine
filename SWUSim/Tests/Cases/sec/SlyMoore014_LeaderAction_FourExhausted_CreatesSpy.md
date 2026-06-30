# SEC_014 Sly Moore (leader) — Action [1 resource, Exhaust]: If there are 4 or more exhausted units in
# play, create a Spy token. Four exhausted units (2 P1 + 2 P2) → a Spy (SEC_T01) enters P1's ground.

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:SEC_014;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_095:0:0
WithP1GroundArena: SOR_095:0:0
WithP2GroundArena: SOR_095:0:0
WithP2GroundArena: SOR_095:0:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:SEC_T01
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED
