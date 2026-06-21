# LOF_234 Darth Malak — Overwhelm + When Played: if you control a Sith leader unit, may ready this unit.
# P1 has Darth Vader (Sith) deployed, so playing Malak lets P1 ready him.

## GIVEN
CommonSetup: rrk/rrw/{myResources:5;handCardIds:LOF_234;myLeaderDeployed:1}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:READY
