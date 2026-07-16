# OnAttack_NoReadyResources_Droid
#// TWI_183 Rush Clovis (Unit 3/5, Ground, cost 4, Separatist/Official) — Raid 2 + "On Attack: If the
#// defending player controls no ready resources, create a Battle Droid token." P2 has only exhausted
#// resources, so attacking the base creates a Battle Droid; combat deals 3 + Raid 2 = 5 to the base.

## GIVEN
CommonSetup: rrk/bbw/{theirResources:0:SOR_046:0}
P1OnlyActions: true
WithP1GroundArena: TWI_183:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
P2BASEDMG:5
