# Create8ReadyCantAttackBases
#// JTL_092 Scramble Fighters (event) — Create 8 TIE Fighter tokens and ready them; they can't attack
#// bases for this phase. Eight readied TIEs are created, and a TIE attacking the enemy base is a no-op.

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_092
WithP1Resources: 7

## WHEN
- P1>PlayHand:0
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1SPACEARENACOUNT:8
P1SPACEARENAUNIT:0:READY
P2BASEDMG:0
