# LOF_234 Darth Malak — Overwhelm + When Played: if you control a Sith leader unit, may ready this unit.
# P1 has Darth Vader (Sith) deployed as a ground-arena leader unit (index 0), so playing Malak
# (index 1) lets P1 ready him.

## GIVEN
CommonSetup: rrk/rrw/{myResources:5;handCardIds:LOF_234;myLeaderDeployed:1}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:1:READY
