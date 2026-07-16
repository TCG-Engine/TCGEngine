# GivesShield
#// SOR_073 Moment of Peace (Event, cost 1) — "Give a Shield token to a unit."
#// P1's only unit (Battlefield Marine) is the sole target → auto-receives a shield.

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;handCardIds:SOR_073}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
