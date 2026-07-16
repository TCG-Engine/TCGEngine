# RestoreWhileEnemyUpgraded
#// ASH_057 Lothal E-Wing (Space, 2/3) — While an enemy unit is upgraded, this unit gains Restore 2. With
#// the enemy SEC_080 carrying SOR_120, Lothal E-Wing has Restore.
## GIVEN
CommonSetup: bbw/ggk
WithP1SpaceArena: ASH_057:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_057
P1SPACEARENAUNIT:0:HASKEYWORD:Restore
