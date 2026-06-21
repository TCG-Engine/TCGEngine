# SEC_120 Naboo Security Force — decline the When Played disclose → no Sentinel granted.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_120
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1NODECISION
