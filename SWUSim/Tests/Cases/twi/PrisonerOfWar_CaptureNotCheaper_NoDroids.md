# TWI_227 Prisoner of War — captor SEC_080 (cost 3) captures an enemy SEC_080 (cost 3). 3 is NOT
# less than 3 → the capture still happens but NO Battle Droids are created (the cost gate fails).

## GIVEN
CommonSetup: yyk/grw/{myResources:4;handCardIds:TWI_227}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
