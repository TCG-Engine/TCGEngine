# Action_ExhaustEnemy
#// SHD_196 Grogu (2-cost ground) — "Action [exhaust]: Exhaust an enemy unit." Using the action exhausts
#// Grogu (the cost) and the enemy SOR_046.

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1GroundArena: SHD_196:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_196
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:EXHAUSTED
