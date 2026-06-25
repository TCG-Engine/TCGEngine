# JTL_135 Special Forces TIE Fighter — if the opponent does not control more space units, it stays
# exhausted. With no enemy space units, JTL_135 (P1's only space unit) does not ready.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_011;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_135
WithP1Resources: 2

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_135
P1SPACEARENAUNIT:0:EXHAUSTED
