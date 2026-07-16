# WhenAttacked_Droid
#// TWI_083 General's Guardian (Unit 4/4, Ground, cost 4, Separatist/Droid) — "When this unit is attacked:
#// Create a Battle Droid token." (On Defense.) P2's SOR_095 attacks it; P1 creates a Battle Droid (TWI_T01).
#// TWI_083 survives (4 HP vs 3) and its counter defeats SOR_095.

## GIVEN
CommonSetup: rrk/bbw/{}
WithActivePlayer: 2
WithP1GroundArena: TWI_083:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
P2GROUNDARENACOUNT:0
