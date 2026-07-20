# CostPerDamagedGround
#// JTL_163 AT-DP Occupier — This unit costs 1 resource less to play for each damaged ground unit. With
#// two damaged ground units in play (SOR_095, SOR_046), the cost-4 Occupier plays for 4-2=2, consuming
#// exactly 2 resources.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_163
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:1
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:JTL_163
P1RESAVAILABLE:0

---

# NoDamagedGround_FullCost
#// JTL_163 AT-DP Occupier — with no damaged ground units the discount is 0, so it plays at its full cost 4.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_163
WithP1Resources: 4

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_163
P1RESAVAILABLE:0

---

# Overwhelm_ExcessToBase
#// JTL_163 AT-DP Occupier has Overwhelm — attacking a weaker enemy unit spills the excess onto the base.
#// The Occupier (3 power, seated) attacks P2's SOR_128 (3/1): 1 defeats it, the other 2 overwhelm onto the
#// base.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_163:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:2
