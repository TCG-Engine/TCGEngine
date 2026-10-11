# OpponentThreeSpace_CostMinus3
#// JTL_204 Home One — If an opponent controls 3 or more space units, this unit costs 3 resources less.
#// With P2 controlling 3 space units, the cost-9 Home One plays for 6, consuming exactly 6 resources.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_016;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_204
WithP1Resources: 6
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_204
P1RESAVAILABLE:0

---

# NotThreeEnemySpace_FullCost
#// JTL_204 Home One — the −3 cost only applies when an OPPONENT controls 3+ SPACE units. P2 has only 2
#// space units (friendly space units and enemy GROUND units are ignored), so Home One costs the full 9 —
#// with 9 resources provisioned it consumes all 9.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_016;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_204
WithP1Resources: 9
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:1:CARDID:JTL_204
P1RESAVAILABLE:0

---

# ThreeSeat_AFarOpponentHasThreeSpace_CostMinus3
#// "If AN OPPONENT controls 3 or more space units." Seat 3 holds the three; seat 2 holds none. The
#// discount must apply. `GetOpponent($player)` is seat 2 for a seat-1 caster — and NULL above seat 2 —
#// so a far seat's fleet was never counted.

## GIVEN
CommonSetup3P: byw/bbk/bbk/{
  myLeader:JTL_016;
  myBase:JTL_019
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: JTL_204
WithP1Resources: 6
WithP3SpaceArena: SOR_237:1:0
WithP3SpaceArena: SOR_225:1:0
WithP3SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:3
P1SPACEARENAUNIT:0:CARDID:JTL_204
P1RESAVAILABLE:0

---

# ThreeSeat_ThreeSpaceSPREADAcrossTwoOpponents_NoDiscount
#// ⚠ THE THRESHOLD IS PER OPPONENT, not a table total: "an opponent controls 3 or more". Seat 2 has two
#// space units and seat 3 has one — three in total, but no single opponent has three — so there is NO
#// discount and the cost-9 Home One is unaffordable on 6 resources (it stays in hand).
#// This is what stops the fix from becoming a flat union of every enemy unit.

## GIVEN
CommonSetup3P: byw/bbk/bbk/{
  myLeader:JTL_016;
  myBase:JTL_019
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: JTL_204
WithP1Resources: 6
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
WithP3SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:3
P1HANDCOUNT:1
P1RESAVAILABLE:6

---

# FourSeat_TheFarthestOpponentHasThreeSpace_CostMinus3
#// 4P sibling: the fleet is on SEAT 4, two bystander boards empty.

## GIVEN
CommonSetup4P: byw/bbk/bbk/bbk/{
  myLeader:JTL_016;
  myBase:JTL_019
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: JTL_204
WithP1Resources: 6
WithP4SpaceArena: SOR_237:1:0
WithP4SpaceArena: SOR_225:1:0
WithP4SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1SPACEARENAUNIT:0:CARDID:JTL_204
P1RESAVAILABLE:0
