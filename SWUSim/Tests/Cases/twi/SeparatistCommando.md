# Raid2WhileSeparatist
#// TWI_180 Separatist Commando (Unit 2/3, Ground) — "While you control another Separatist unit, this
#// unit gains Raid 2." With a friendly Battle Droid (Separatist), attacking P2's base deals 2+2 = 4.

## GIVEN
CommonSetup: yyk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_180:1:0
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
