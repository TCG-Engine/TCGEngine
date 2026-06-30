# JTL_260 Death Star Plans — "When attached unit is attacked: The attacking player takes control of this
# upgrade and attaches it to a unit they control." P1's SOR_046 attacks the enemy SEC_080 (which carries
# Death Star Plans); on attack P1 steals the upgrade onto SOR_046 (its only unit), then combat kills
# SEC_080 (SOR_046 is a 3/7 and survives the counter).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:JTL_260

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:JTL_260
P2GROUNDARENACOUNT:0
