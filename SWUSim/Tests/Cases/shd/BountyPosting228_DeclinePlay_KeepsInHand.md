# SHD_228 Bounty Posting — the play is a "may": declining keeps the drawn upgrade in hand, unattached.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_228
WithP1Deck: [SHD_173 SOR_095 SEC_080 SOR_128]
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SHD_173
- P1>AnswerDecision:NO

## EXPECT
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1
