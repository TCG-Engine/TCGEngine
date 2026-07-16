# WhenPlayed_TwoShields
#// JTL_067 Cloaked StarViper — When Played: Give 2 Shield tokens to this unit.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_067
WithP1Resources: 4

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_067
P1SPACEARENAUNIT:0:SHIELDCOUNT:2
