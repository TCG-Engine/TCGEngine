# TWI_066 Multi-Troop Transport (Unit 3/6, Ground) — "Exploit 2. On Attack: Create a Battle Droid
# token." Attacking P2's base creates 1 Battle Droid (P1 ground 1 → 2).

## GIVEN
CommonSetup: bbk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_066:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
