# FullCostNoIndirect
#// JTL_138 Decimator of Dissidents — without having dealt indirect damage this phase, it plays at its
#// full cost of 4 (the -1 discount applies only after indirect damage; that path mirrors SHD_182 Bravado
#// and is exercised by the Phase 21 indirect cards).

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

---

# Overwhelm_ExcessToBase
#// JTL_138 Decimator of Dissidents has Overwhelm — attacking a weaker enemy unit spills the excess onto the
#// base. Decimator (3 power, seated) attacks P2's SOR_225 (2/1): 1 damage defeats it, the other 2 overwhelm
#// onto P2's base.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_011;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_138:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:2
