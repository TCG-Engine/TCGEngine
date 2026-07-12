# TWI_195 Sabine Wren — while READY she CAN be attacked (the protection is exhausted-only). P2's
# SOR_046 (3 power) attacks the ready Sabine (4 HP) → she takes 3 damage.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: TWI_195:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
