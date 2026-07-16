# VehicleAttackBuff
#// JTL_231 Punch It — Attack with a Vehicle unit; it gets +2/+0 for this attack. SOR_237 (2 power) gets
#// +2 → 4 and hits the enemy base for 4.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_231
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:4
