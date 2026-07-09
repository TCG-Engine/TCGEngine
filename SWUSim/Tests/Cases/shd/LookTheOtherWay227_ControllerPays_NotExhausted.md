# SHD_227 Look the Other Way (0-cost event) — "Exhaust a unit unless its controller pays 2 resources." P1
# targets the enemy SOR_046; its controller P2 has 2 ready resources and chooses to pay, so SOR_046 stays
# ready and P2's resources drop to 0.

## GIVEN
CommonSetup: yyk/yyk/{theirResources:2}
WithActivePlayer: 1
WithP1Hand: SHD_227
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:READY
P2RESAVAILABLE:0
