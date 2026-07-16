# WhenDefeated_Droid
#// TWI_032 Wartime Trade Official (Unit 1/3, Ground, cost 2, Separatist/Official) — "When Defeated: Create
#// a Battle Droid token." It attacks SOR_046 (3/7) and dies to the 3 counter-damage, creating a Battle
#// Droid (TWI_T01) in its place.

## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_032:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
