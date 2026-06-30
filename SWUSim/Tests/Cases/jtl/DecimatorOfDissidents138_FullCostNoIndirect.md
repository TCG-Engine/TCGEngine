# JTL_138 Decimator of Dissidents — without having dealt indirect damage this phase, it plays at its
# full cost of 4 (the -1 discount applies only after indirect damage; that path mirrors SHD_182 Bravado
# and is exercised by the Phase 21 indirect cards).

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_011;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_138
WithP1Resources: 4

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_138
P1RESAVAILABLE:0
