# LAW_106 Defiant Scrapper (Unit, cost 3, Vigilance/Heroism, 3/4) — When Played: You may defeat an
#   enemy Credit token. P2 has one Credit token (at theirResources-0 from P1's frame). P1 defeats it.

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: LAW_106
WithP2Credits: 1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_106
P2CREDITCOUNT:0
P1NODECISION
