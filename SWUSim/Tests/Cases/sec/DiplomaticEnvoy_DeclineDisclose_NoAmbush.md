# SEC_109 Diplomatic Envoy — decline the disclose → the next unit does NOT gain Ambush.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SEC_109
WithP1Hand: SOR_095
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush
