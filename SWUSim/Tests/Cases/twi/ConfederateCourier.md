# WhenDefeated_Droid
#// TWI_079 Confederate Courier (Unit 2/1, Space, cost 2, Separatist/Vehicle/Fighter) — "When Defeated:
#// Create a Battle Droid token." It attacks JTL_069 (4/7) and dies to the 4 counter-damage, creating a
#// Battle Droid (TWI_T01, a ground unit).

## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1SpaceArena: TWI_079:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
