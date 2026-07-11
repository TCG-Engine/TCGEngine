# SHD_087 Crosshair — two unit actions; with both affordable (2 ready resources + a ready unit) a menu is
# offered. Choosing the buff: "Action [2 resources]: This unit gets +1/+0 for this phase." SHD_087 (4
# power) becomes 5; 2 resources spent.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SHD_087:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:Buff

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1RESAVAILABLE:0
