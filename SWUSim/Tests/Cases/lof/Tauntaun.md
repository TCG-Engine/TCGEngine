# WhenDefeated_ShieldDamaged
#// LOF_064 Tauntaun (3/3) — When Defeated: may give a Shield token to a damaged non-Vehicle unit. It
#// attacks a 4/7 and dies; P1 shields its damaged friendly SOR_046.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_064:1:0
WithP1GroundArena: SOR_046:1:3
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
