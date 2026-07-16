# WhenPlayed_SelfDamage3
#// JTL_248 Dilapidated Ski Speeder — When Played: Deal 3 damage to this unit. The 3/7 speeder enters and
#// immediately takes 3 (mandatory, no choice), surviving at 3 damage.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_248
WithP1Resources: 3

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_248
P1GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
