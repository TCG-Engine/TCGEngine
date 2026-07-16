# WhenPlayed_ReturnUpgrade
#// SHD_209 Criminal Muscle (1-cost, Cunning ground) — "When Played: You may return a non-unique upgrade to
#// its owner's hand." The non-unique SOR_120 on SEC_080 is returned to P1's hand.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_209
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1HANDCOUNT:1
