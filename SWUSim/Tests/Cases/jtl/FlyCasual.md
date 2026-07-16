# ReadyVehicle_CantAttackBases
#// JTL_206 Fly Casual (event) — Ready a Vehicle unit; it can't attack bases for this phase. SOR_237 is
#// readied, and its subsequent attack on the base is a no-op (stays ready).

## GIVEN
CommonSetup: gyw/bbk/{
  myLeader:JTL_016;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_206
WithP1Resources: 1
WithP1SpaceArena: SOR_237:0:0

## WHEN
- P1>PlayHand:0
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:READY
P2BASEDMG:0
