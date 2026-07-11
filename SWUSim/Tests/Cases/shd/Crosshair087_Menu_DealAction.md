# SHD_087 Crosshair — choosing the other action: "Action [Exhaust]: This unit deals damage equal to his
# power to an enemy ground unit." Crosshair (2 power) exhausts and deals 2 to the enemy SOR_046 (sole
# target, auto-selected).

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SHD_087:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:Deal

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:EXHAUSTED
