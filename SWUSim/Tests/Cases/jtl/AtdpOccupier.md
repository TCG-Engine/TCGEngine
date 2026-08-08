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

---

# DamagedSpaceUnits_NoDiscount
#// JTL_163 AT-DP Occupier — the discount counts only damaged GROUND units. With damaged space units on
#// both sides but no damaged ground units, the cost-4 Occupier plays at full cost 4 (no discount).

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
WithP1SpaceArena: JTL_033:1:1
WithP2SpaceArena: JTL_037:1:2

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_163
P1RESAVAILABLE:0

---

# DamagedUnitCountedByLiveArena_NotPrintedArena
#// JTL_163 AT-DP Occupier — "costs 1 resource less for each damaged GROUND unit". The count must key off
#// where a unit ACTUALLY is, not its printed arena. JTL_096 Blue Leader is printed a SPACE unit but its
#// When Played can move it to the ground arena, and once there it IS a damaged ground unit and must be
#// counted. Seated directly in the ground arena with 2 damage to model that post-move state.
#// The control is SOR_237 (a real space unit) damaged in the SPACE arena: it must NOT be counted.
#// So exactly ONE unit qualifies → the cost-4 Occupier plays for 3, leaving 1 of 4 resources ready.
#// An implementation gating on the printed arena (CardTargetArena) would count zero and charge 4.

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
WithP1GroundArena: JTL_096:1:2
WithP1SpaceArena: SOR_237:1:1

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1SPACEARENACOUNT:1
P1RESAVAILABLE:1
