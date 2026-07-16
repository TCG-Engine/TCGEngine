# WhenPlayed_CreatesCredit
#// LAW_262 Bank Job Fugitives (Unit, cost 6, neutral, 4/6) — When Played: Create a Credit token.

## GIVEN
CommonSetup: yyw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: LAW_262

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_262
P1CREDITCOUNT:1
P1NODECISION
