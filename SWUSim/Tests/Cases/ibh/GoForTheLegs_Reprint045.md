# IBH_045 Go for the Legs (reprint of IBH_018) — exhaust an enemy ground unit. Confirms the duplicate.

## GIVEN
CommonSetup: yyk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: IBH_045
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION
