# JTL_173 Fight Fire With Fire (event) — choose a friendly unit and an enemy unit in the same arena;
# deal 3 to each. Both are SOR_046 (3/7) → each takes 3 and survives. Both choices auto-resolve.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_173
WithP1Resources: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:3
