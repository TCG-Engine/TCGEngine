# Coordinate_Captures
#// TWI_213 Sanctioner's Shuttle (Unit 2/3, Space, cost 3) — "Coordinate - When Played: This unit
#// captures an enemy non-leader unit that costs 3 or less." Played with 2 friendly Clone tokens already
#// (→ 3 incl. the shuttle → Coordinate active); the only enemy ≤3-cost unit (SEC_080, cost 3) is captured
#// facedown under the shuttle.

## GIVEN
CommonSetup: yyk/grw/{myResources:3;handCardIds:TWI_213}
P1OnlyActions: true
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:TWI_213
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
