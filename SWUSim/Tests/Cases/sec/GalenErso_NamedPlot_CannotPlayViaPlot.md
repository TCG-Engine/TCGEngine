# SEC_046 Galen Erso — naming a Plot card denies its Plot keyword, so the opponent can't play it from
# resources on a leader deploy. P2 holds SEC_111 Jar Jar Binks (Plot) as a resource. P1 names "Jar Jar
# Binks". When P2 deploys its leader, the Plot window does NOT open (no offer appears) — so P2 ends with
# only the deployed leader on the board and no pending decision.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2Resources: 1:SEC_111:1,7:SOR_095:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Jar Jar Binks
- P2>DeployLeader

## EXPECT
P2LEADER:DEPLOYED
P2GROUNDARENACOUNT:1
P2NODECISION
