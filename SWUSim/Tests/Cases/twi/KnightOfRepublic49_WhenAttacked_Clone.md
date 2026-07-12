# TWI_049 Knight of the Republic (Unit 4/7, Ground, cost 6, Force/Jedi/Republic) — "When this unit is
# attacked: Create a Clone Trooper token." (On Defense window.) P2's SOR_095 attacks it; P1 creates a
# Clone Trooper (TWI_T02). TWI_049 survives (7 HP) and its counter defeats SOR_095.

## GIVEN
CommonSetup: bbw/rrk/{}
WithActivePlayer: 2
WithP1GroundArena: TWI_049:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TWI_T02
P2GROUNDARENACOUNT:0
