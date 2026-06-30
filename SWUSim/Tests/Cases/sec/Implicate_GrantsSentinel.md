# SEC_231 Implicate (event) — Choose a unit; for this phase it gains Sentinel (and a granted "when
#   attacked, create a Spy"). P1 plays Implicate on its SOR_046 → SOR_046 gains Sentinel.

## GIVEN
CommonSetup: yyk/grk/{myResources:2;handCardIds:SEC_231}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
