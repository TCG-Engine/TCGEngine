# LOF_243 Caretaker Matron — Action [Exhaust]: if you played a Force card this phase, draw a card. P1
# plays the Force unit Youngling Padawan, then activates the Matron to draw.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2;handCardIds:LOF_193}
P1OnlyActions: true
WithP1GroundArena: LOF_243:1:0
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
