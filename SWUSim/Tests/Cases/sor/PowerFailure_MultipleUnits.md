# SOR_170 Power Failure — multiple units with upgrades, player chooses target unit
# P1 and P2 each have one upgrade. Player targets P2's unit; P1's upgrade survives.

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_170}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_215
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:LOF_215

## WHEN
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:1
