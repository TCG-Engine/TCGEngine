# JTL_070 U-Wing Lander — "When this unit completes an attack (and survives): You may attach an upgrade
# on this unit to another eligible friendly Vehicle unit." U-Wing (with Academy Training SOR_120 +2/+2)
# attacks the enemy base, survives, then moves the upgrade to the friendly Alliance X-Wing (SOR_237).
# Dest is the only other friendly Vehicle, so its pick auto-resolves; only the upgrade pick is answered.

## GIVEN
P1LeaderBase: SOR_005/SOR_021
P2LeaderBase: SOR_002/SOR_021
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
