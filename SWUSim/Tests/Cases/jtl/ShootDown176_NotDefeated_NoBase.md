# JTL_176 Shoot Down (event) — the base damage only follows if the space unit is DEFEATED. JTL_069
# (4/7) survives the 3 damage, so no base option is offered.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_176
WithP1Resources: 2
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:3
P2BASEDMG:0
P1NODECISION
