# TWI_154 Mister Bones — condition guard: with a card in hand, the On Attack deal is NOT offered. No
# decision is pending and the enemy ground unit is undamaged; only combat hits the base.

## GIVEN
CommonSetup: rrk/bbw/{myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: TWI_154:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:3
