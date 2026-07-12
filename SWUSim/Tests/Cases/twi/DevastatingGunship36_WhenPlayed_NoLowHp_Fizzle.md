# TWI_036 Devastating Gunship — with no enemy unit at ≤2 remaining HP (only the 7-HP SOR_046), the When
# Played finds no valid target and fizzles cleanly.

## GIVEN
CommonSetup: bbk/rrw/{myResources:5;handCardIds:TWI_036}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2GROUNDARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:TWI_036
