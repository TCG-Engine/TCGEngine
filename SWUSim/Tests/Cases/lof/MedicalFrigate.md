# Heal2
#// LOF_250 Medical Frigate — On Attack: may heal 2 damage from another unit. It attacks the base and heals
#// 2 from the damaged friendly 3/7 (5 damage → 3).

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1SpaceArena: LOF_250:1:0
WithP1GroundArena: SOR_046:1:5

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
