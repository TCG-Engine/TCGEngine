# SEC_128 Convene the Senate (Event, cost 3, Command) — "Search the top 8 of your deck for up to 2
#   Official units, reveal+draw them. Create a Spy token." Deck top: SEC_041 + SEC_043 (Official units)
#   + a filler; draw both Officials, create a Spy.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SEC_128
WithP1Deck: [SEC_041 SEC_043 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_041,SEC_043

## EXPECT
P1HANDCOUNT:2
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_T01
P1NODECISION
