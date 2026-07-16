# VambraceFlamethrower_OnAttack_SplitDamage
#// SHD_177 Vambrace Flamethrower — attached unit gains "On Attack: You may deal 3 damage divided as you
#// choose among enemy ground units." Host (SOR_046 + SHD_177 +1/+1 = 4 power) attacks the base; its On
#// Attack deals all 3 to the lone enemy ground unit (SOR_046, 7 HP → 3 damage). Base still takes 4.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_177
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0:3

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:4
