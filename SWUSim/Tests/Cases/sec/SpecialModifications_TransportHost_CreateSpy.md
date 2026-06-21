# SEC_227 Special Modifications (Upgrade, cost 2) — Attach to a Vehicle unit. When Played: if the
#   attached unit is a Transport, you may create a Spy token. Host JTL_069 (Capital Ship) is a Vehicle
#   but NOT a Transport... so use a Transport host. SOR_237 Alliance X-Wing is a Fighter (not Transport);
#   instead use a Transport vehicle. Here the host is a Transport → may create a Spy.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1SpaceArena: SEC_083:1:0
WithP1Hand: SEC_227

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_T01
P1NODECISION
