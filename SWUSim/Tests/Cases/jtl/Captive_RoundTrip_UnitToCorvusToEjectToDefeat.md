# Captive round-trip: the captive rides the captor through unit → pilot-upgrade → unit. JTL_046 Paige
# captures SOR_095 (SHD_131); Corvus attaches Paige (captive tucked on the pilot subcard); Eject detaches
# her back to a ground unit. She comes back EXHAUSTED (proving Eject fired — which is only possible if
# Corvus first made her a pilot upgrade) and STILL holds SOR_095 as a captive (proving the captive
# survived both transitions). Corvus is left with no pilot. (Rescue-on-defeat of a captor is covered by
# shd/Capture_RescueOnCaptorDefeat.md — defeating this Paige would release SOR_095 to P2 exhausted.)

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 20
WithP1Hand: SHD_131 JTL_038 JTL_126
WithP1Deck: SOR_237
WithP1GroundArena: JTL_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_046
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_095
P1SPACEARENAUNIT:0:CARDID:JTL_038
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
