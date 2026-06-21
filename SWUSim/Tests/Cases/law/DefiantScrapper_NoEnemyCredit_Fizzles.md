# LAW_106 Defiant Scrapper — with no enemy Credit token in play, the When Played effect has no valid
#   target and fizzles cleanly (no decision presented).

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: LAW_106

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_106
P1NODECISION
