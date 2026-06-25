# JTL_191 Invincible — If you control a unique Separatist card, this unit costs 1 resource less. With
# the unique Separatist SOR_038 in play, the cost-6 Invincible plays for 5. (The "when you deploy a
# leader" bounce rider is deferred.)

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:JTL_015;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_191
WithP1Resources: 5
WithP1GroundArena: SOR_038:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_191
P1RESAVAILABLE:0
