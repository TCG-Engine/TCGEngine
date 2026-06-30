# SEC_040 Emergency Powers (Event, cost 1) — choose a non-leader unit and pay any number of resources;
#   give that many Experience tokens. Pay 2 → SOR_095 gets 2 Experience → 5/5.

## GIVEN
CommonSetup: bbk/grw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_040

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:2

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1NODECISION
