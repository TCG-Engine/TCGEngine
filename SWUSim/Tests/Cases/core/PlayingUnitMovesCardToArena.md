## GIVEN
# Sabine (Aggression+Heroism) + Echo Base (Command). Leia (Cunning+Heroism) costs 2+2=4.
CommonSetup: grw/ggk/{myResources:4;handCardIds:SOR_189}

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_189
# Units enter play exhausted (CR 4.3.3)
P1GROUNDARENAUNIT:0:EXHAUSTED
