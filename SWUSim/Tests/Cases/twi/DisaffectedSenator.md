# Action_Deal2ToBase
#// TWI_157 Disaffected Senator (Unit 0/4, Ground, cost 1, Aggression, Separatist/Official) — "Action [2
#// resources, Exhaust]: Deal 2 damage to a base." Using the ability exhausts the unit, pays 2 resources,
#// and deals 2 to the chosen (enemy) base.

## GIVEN
CommonSetup: rrk/bbw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: TWI_157:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:0
