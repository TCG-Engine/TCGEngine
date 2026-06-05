## GIVEN
CommonSetup: grw/ggk/{myResources:6;handCardIds:SOR_189}

## WHEN
# Leia Organa: cost 2 + 2 Cunning penalty = 4 exhausted. 6 resources → 2 remain ready.
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:2
