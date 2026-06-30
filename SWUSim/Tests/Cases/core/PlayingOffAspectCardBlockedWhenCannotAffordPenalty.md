## GIVEN
CommonSetup: grw/ggk/{myResources:3;handCardIds:SOR_189}

## WHEN
# Leia Organa: cost 2 + 2 Cunning penalty = 4 total. 3 resources → blocked.
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:3
