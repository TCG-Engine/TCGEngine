# ExpThenDamage
#// LOF_239 Consumed by the Dark Side — Give 2 Experience tokens to a unit, then deal 2 damage to it. SOR_046
#// (3/7) becomes 5/9 from the Experience, then takes 2 damage.

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_239}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:DAMAGE:2
