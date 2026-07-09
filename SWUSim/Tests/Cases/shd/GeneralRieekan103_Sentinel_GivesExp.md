# SHD_103 General Rieekan — choosing a unit that ALREADY has Sentinel (SOR_063) gives it an Experience
# token instead of granting Sentinel.

## GIVEN
CommonSetup: ggw/ggw/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_103
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
