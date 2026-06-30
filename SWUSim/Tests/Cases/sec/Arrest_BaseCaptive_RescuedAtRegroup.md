# SEC_195 Arrest — the base captive is rescued by its owner at the start of the regroup phase.
# P1 captures P2's SOR_095 (it leaves play), then both players pass to reach the regroup phase. At
# RegroupPhaseStart, SOR_095 returns to P2's control (in its arena). Net: P2 has SOR_095 back.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SEC_195
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
