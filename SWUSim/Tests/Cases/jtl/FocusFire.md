# VehiclesDealPower
#// JTL_129 Focus Fire — Each friendly Vehicle in the chosen unit's arena deals its power to it. P1's two
#// space Vehicles (SOR_237 + SOR_225, power 2 each = 4) defeat the enemy SOR_044 (3 HP).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_129
WithP1Resources: 10
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0

---

# GroundVehiclesDealPower
#// JTL_129 Focus Fire — works in the GROUND arena too: each friendly Vehicle in the chosen unit's arena
#// deals its power to it. P1's AT-ST (SOR_232, a 6-power Vehicle) deals 6 to the enemy SOR_095 (3 HP),
#// defeating it.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_129
WithP1Resources: 10
WithP1GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0

---

# NoFriendlyVehicleInArena_NotSelectable
#// JTL_129 Focus Fire — a unit is only a legal target if a friendly Vehicle shares its arena (otherwise the
#// effect deals 0, which is disallowed). P1 has only a SPACE Vehicle (SOR_237) and no ground
#// Vehicle, so space units are selectable but the enemy GROUND unit (SOR_095) is NOT offered.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_129
WithP1Resources: 10
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEHAS:mySpaceArena-0
P1SELECTABLEHAS:theirSpaceArena-0
P1SELECTABLENOT:theirGroundArena-0
