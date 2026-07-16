# ReadyFighter
#// JTL_179 Koiogran Turn (event) — Ready a Fighter or Transport unit with 6 or less power. The exhausted
#// Fighter SOR_237 (power 2) is readied.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_179
WithP1Resources: 3
WithP1SpaceArena: SOR_237:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:READY
