# JTL_238 Sith Trooper — On Attack: +1/+0 for each damaged unit the defending player controls. P2 has
# two damaged units, so the Sith Trooper (power 3) attacks for 3+2=5 to the enemy base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_238:1:0
WithP2GroundArena: SOR_046:1:3
WithP2GroundArena: SOR_095:1:1

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
