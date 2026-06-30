# SOR_016 Grand Admiral Thrawn — Leader Action: choose own deck (top = SOR_095, cost 2).
# Only one valid target (theirGroundArena-0, cost 2 <= 2) → auto-exhausted via PASSPARAMETER.

## GIVEN
CommonSetup: yyk/grw/{myResources:1}
P1OnlyActions: true
WithP1Deck: SOR_095
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:EXHAUSTED
P1RESCOUNT:1
P1RESAVAILABLE:0
