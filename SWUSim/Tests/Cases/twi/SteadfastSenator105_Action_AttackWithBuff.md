# TWI_105 Steadfast Senator (Unit 0/4, Ground, cost 1) — "Action [2 resources, Exhaust]: Attack with a
# unit. It gets +2/+0 for this attack." Using the action (exhausts Senator, pays 2), the friendly
# SOR_095 (3/3) attacks P2's base with +2/+0 → deals 5.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: TWI_105:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:0
