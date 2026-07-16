# OnAttack_ExhaustDefender
#// SHD_183 Kintan Intimidator (1-cost ground) — "On Attack: Exhaust the defender." Kintan attacks the ready
#// SOR_046 (7 HP), which survives combat but is left exhausted by the On Attack rider.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SHD_183:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:EXHAUSTED
