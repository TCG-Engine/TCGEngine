# CostPerDamaged
#// LAW_110 Phoenix Squadron Fighters (6/6, space, cost 8) — costs 1 less per friendly damaged unit. With
#// 2 damaged friendly units it costs 6: plays with exactly 6 ready resources.
#// COVERAGE: offer=N/A (static cost modifier, no target picker) · decline=N/A (no "you may") ·
#//           control=N/A (cost is read at play time from live friendly membership; no persistent marker)
#//           · reqboundary=N/A (no post-decision state read; the whole effect is one cost computation) ·
#//           boundary pair=EnemyDamagedUnitDoesNotDiscount (full 8 with only enemy damage) +
#//           CostPerDamaged / LeaderAndSpaceCount_BaseAndEnemyDoNot (discounted plays that would no-op
#//           if the count were off by one either way, since resources are exact).

## GIVEN
CommonSetup: bbw/bgw/{myResources:6}
WithP1GroundArena: SEC_080:1:1
WithP1GroundArena: SEC_080:1:1
WithP1Hand: LAW_110

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_110
P1RESAVAILABLE:0

---

# EnemyDamagedUnitDoesNotDiscount
#// LAW_110 Phoenix Squadron Fighters — the discount counts FRIENDLY damaged units only. With both P1
#//   units undamaged and a DAMAGED enemy unit on the board, the cost stays 8: the play succeeds only
#//   because exactly 8 resources are ready, and all 8 are spent. (A leaked enemy-side discount would
#//   leave a ready resource and fail the RESAVAILABLE check.)

## GIVEN
CommonSetup: bbw/bgw/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_046:1:0]
WithP2GroundArena: SEC_080:1:1
WithP1Hand: LAW_110

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_110
P1RESAVAILABLE:0

---

# LeaderAndSpaceCount_BaseAndEnemyDoNot
#// LAW_110 Phoenix Squadron Fighters — the count includes a damaged deployed LEADER unit and a damaged
#//   friendly SPACE unit, but NOT the friendly base's damage, an undamaged friendly unit, or a damaged
#//   ENEMY space unit. Friendly damaged: SEC_080 (ground), deployed Luke (2 dmg), SOR_237 (space) = 3
#//   -> cost 5, paid with exactly 5 ready resources.

## GIVEN
CommonSetup: bbw/bgw/{myResources:5;myLeader:SOR_005:1:1:0:2;myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:1 SOR_095:1:0]
WithP1SpaceArena: SOR_237:1:1
WithP2SpaceArena: SOR_237:1:1
WithP1Hand: LAW_110

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:LAW_110
P1RESAVAILABLE:0
