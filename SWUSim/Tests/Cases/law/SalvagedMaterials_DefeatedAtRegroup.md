# LAW_245 Salvaged Materials — "At the start of the next regroup phase, defeat it." After attaching
# SOR_071, passing to regroup defeats the upgrade (UPGRADECOUNT back to 0, host power back to 3/3).

## GIVEN
CommonSetup: yyk/bgw/{myResources:2;discardCardIds:SOR_071}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_245

## WHEN
- P1>PlayHand:0
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3
