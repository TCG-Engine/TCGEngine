# Credit token core (CR 3.13): "While paying resources, you may defeat this token. If you do, pay 1 less."
#   P1 has 2 real resources + 1 Credit token (at myResources-2). P1 plays SOR_095 (cost 2, Command/Heroism)
#   and defeats the Credit to pay 1 less — only 1 resource is exhausted, the Credit is gone.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Credits: 1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-2

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1CREDITCOUNT:0
P1RESCOUNT:2
P1RESAVAILABLE:1
P1NODECISION
