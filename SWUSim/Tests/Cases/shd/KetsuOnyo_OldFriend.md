# Ketsu_CombatDamageBase_DefeatUpgrade
#// SHD_147 Ketsu Onyo — "When this unit deals combat damage to a base: You may defeat an upgrade that
#// costs 2 or less." Ketsu attacks the base; then defeats SOR_069 (cost 1) on the enemy SOR_046.

## GIVEN
CommonSetup: rrw/rrw
P1OnlyActions: true
WithP1GroundArena: SHD_147:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2BASEDMG:3
