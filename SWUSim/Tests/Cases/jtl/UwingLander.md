# CompleteAttack_Decline
#// JTL_070 U-Wing Lander — the move is a "may": declining leaves the upgrade on the U-Wing.
#// Same setup as the move test; P1 declines the upgrade MZMAYCHOOSE, so SOR_120 stays on JTL_070.

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_070:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:4
P1SPACEARENAUNIT:0:CARDID:JTL_070
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:SOR_120
P1SPACEARENAUNIT:1:CARDID:SOR_237
P1SPACEARENAUNIT:1:UPGRADECOUNT:0

---

# CompleteAttack_MovesUpgrade
#// JTL_070 U-Wing Lander — "When this unit completes an attack (and survives): You may attach an upgrade
#// on this unit to another eligible friendly Vehicle unit." U-Wing (with Academy Training SOR_120 +2/+2)
#// attacks the enemy base, survives, then moves the upgrade to the friendly Alliance X-Wing (SOR_237).
#// Dest is the only other friendly Vehicle, so its pick auto-resolves; only the upgrade pick is answered.

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_070:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myTempZone-0

## EXPECT
P2BASEDMG:4
P1SPACEARENAUNIT:0:CARDID:JTL_070
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:1:CARDID:SOR_237
P1SPACEARENAUNIT:1:UPGRADECOUNT:1
P1SPACEARENAUNIT:1:UPGRADE:0:CARDID:SOR_120
P1SPACEARENAUNIT:1:POWER:4

---

# CompleteAttack_NoUpgrade_NoOffer
#// JTL_070 U-Wing Lander — with no upgrade on it, completing an attack has nothing to move, so no
#// decision is offered (a clean fizzle). A friendly Alliance X-Wing is present to prove the no-op is
#// from the absent upgrade, not the absent destination.

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_070:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:2
P1NODECISION
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:1:UPGRADECOUNT:0

---

# CompleteAttack_NoVehicleDest_NoOffer
#// JTL_070 U-Wing Lander — the destination must be another friendly VEHICLE. With only a friendly
#// non-Vehicle unit (SOR_095 Battlefield Marine, a Trooper) available, there is no eligible destination,
#// so completing the attack offers no decision and the upgrade stays on the U-Wing.

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_070:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:4
P1NODECISION
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:SOR_120
P1GROUNDARENACOUNT:1

---

# WhenPlayed_ThreeExperience
#// JTL_070 U-Wing Lander — When Played: Give 3 Experience tokens to this unit. The 2/2 lander gains
#// +3/+3 (5/5) and carries 3 token upgrades. (The complete-attack move-upgrade rider is implemented with
#// the Phase 16/18 upgrade-move work.)

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_070
WithP1Resources: 5

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_070
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:5
P1SPACEARENAUNIT:0:UPGRADECOUNT:3
