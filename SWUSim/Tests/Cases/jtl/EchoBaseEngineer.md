# NoDamagedVehicle_NoOp
#// JTL_044 Echo Base Engineer — the Shield only targets a DAMAGED Vehicle. With an undamaged Vehicle in
#// play, there is no legal target and no Shield is granted (no decision pending).

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_044
WithP1Resources: 2
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:SHIELDCOUNT:0
P1NODECISION

---

# WhenPlayed_ShieldDamagedVehicle
#// JTL_044 Echo Base Engineer — When Played: You may give a Shield token to a damaged Vehicle unit. The
#// only damaged Vehicle (SOR_237, 2/3 with 1 damage) gets a Shield.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_044
WithP1Resources: 2
WithP1SpaceArena: SOR_237:1:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
