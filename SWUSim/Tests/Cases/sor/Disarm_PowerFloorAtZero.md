# SOR_216 Disarm — −4/−0 cannot push power below 0.
# Battlefield Marine (3/3): power 3 − 4 floors at 0 (not −1). HP unchanged at 3.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1;handCardIds:SOR_216}
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:HP:3
