# JTL_105 The Starhawk — While paying costs, you pay half as many resources, rounded up. P1 controls the
# Starhawk and has only 2 ready resources, yet plays SOR_046 (printed cost 4, on-aspect via SOR_005's
# Vigilance+Heroism). The halving makes it both AFFORDABLE (need ceil(4/2)=2) and PAID at 2 → 0 left.

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_046
WithP1SpaceArena: JTL_105:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1RESAVAILABLE:0
