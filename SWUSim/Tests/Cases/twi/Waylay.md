# Reprint
#// TWI_226 Waylay — reprint shares same OnPlayEvent case as SOR_222

## GIVEN
CommonSetup: ybk/grw/{myResources:3;handCardIds:TWI_226}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
