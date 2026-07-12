# TWI_056 Compassionate Senator (Unit 0/4, Ground, cost 1, Vigilance, Republic/Official) — "Action [2
# resources, Exhaust]: Heal 2 damage from a unit or base." Using the ability exhausts it, pays 2, and
# heals 2 from the chosen damaged unit (SOR_046 at 3 damage → 1).

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: TWI_056:1:0
WithP1GroundArena: SOR_046:1:3

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:0
