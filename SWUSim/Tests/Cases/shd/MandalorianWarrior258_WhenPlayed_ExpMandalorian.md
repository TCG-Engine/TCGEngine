# SHD_258 Mandalorian Warrior (3-cost ground) — "When Played: You may give an Experience token to another
# Mandalorian unit." P1 gives the token to the friendly Mandalorian SHD_150.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_258
WithP1GroundArena: SHD_150:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_150
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
