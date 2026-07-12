# TWI_246 Tranquility — "On Attack: Each of the next 3 Republic cards you play this phase costs 1 resource
# less." Attacking the base arms the discount; the Republic unit TWI_109 (cost 3, Command on-aspect to
# base g) then plays for 2 with only 2 resources.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2;handCardIds:TWI_109}
P1OnlyActions: true
WithP1SpaceArena: TWI_246:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_109
P1RESAVAILABLE:0
