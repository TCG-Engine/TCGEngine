## GIVEN
CommonSetup: grw/ggk/{myResources:4;handCardIds:SOR_189}

## WHEN
# Leia Organa: cost 2 + 2 Cunning penalty = 4 total. Exactly 4 resources → succeeds.
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1RESAVAILABLE:0
