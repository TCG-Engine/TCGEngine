# ExhaustedUnit_BuffOnly
#// SHD_087 Crosshair — when the unit is exhausted, the [Exhaust] deal-power action is unavailable, so only
#// the [2 resources] buff remains: it resolves directly (no menu), giving +1/+0.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SHD_087:0:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1RESAVAILABLE:0

---

# Menu_BuffAction
#// SHD_087 Crosshair — two unit actions; with both affordable (2 ready resources + a ready unit) a menu is
#// offered. Choosing the buff: "Action [2 resources]: This unit gets +1/+0 for this phase." SHD_087 (4
#// power) becomes 5; 2 resources spent.

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

---

# Menu_DealAction
#// SHD_087 Crosshair — choosing the other action: "Action [Exhaust]: This unit deals damage equal to his
#// power to an enemy ground unit." Crosshair (2 power) exhausts and deals 2 to the enemy SOR_046 (sole
#// target, auto-selected).

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
