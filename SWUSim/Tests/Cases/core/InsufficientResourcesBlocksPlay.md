## GIVEN
CommonSetup: grw/ggk/{myResources:2;handCardIds:SOR_049}

## WHEN
# Obi-Wan Kenobi: cost 6. Only 2 resources → blocked.
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:2
