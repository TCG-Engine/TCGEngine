# LOF_123 Directed by the Force — decline branch: P1 plays the event (gains the Force) but declines the
# optional "play a unit" — the unit stays in hand and no unit enters play.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6;handCardIds:LOF_123,SOR_095}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HASFORCE
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
