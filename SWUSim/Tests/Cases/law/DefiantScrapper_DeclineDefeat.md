# LAW_106 Defiant Scrapper — the defeat is optional ("You may"). P1 declines; the enemy Credit survives.

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: LAW_106
WithP2Credits: 1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P2CREDITCOUNT:1
P1NODECISION
