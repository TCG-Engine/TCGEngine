# JTL_017 Han Solo (leader) — "When deployed as an upgrade: For each friendly unit or upgrade that has
# an odd cost, ready a resource." Han deploys as a Pilot onto SOR_237 (cost 2, even). Odd-cost friendly
# permanents = SOR_063 Cloud City Wing Guard (cost 3) + Han himself as a pilot upgrade (cost 5) = 2, so
# 2 of P1's 5 exhausted resources ready.

## GIVEN
P1LeaderBase: JTL_017/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5:SOR_095:0
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot

## EXPECT
P1LEADER:DEPLOYED
P1RESAVAILABLE:2
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
