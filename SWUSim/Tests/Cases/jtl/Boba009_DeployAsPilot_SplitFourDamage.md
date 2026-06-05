# JTL_009 Boba Fett (leader) — "When deployed as an upgrade: Deal up to 4 damage divided as you choose
# among any number of units." Boba deploys as a Pilot onto SOR_225, then splits 4 damage as 3 + 1 across
# two enemy ground units (both survive: SOR_046 is 3/7, SOR_063 is 2/4).

## GIVEN
P1LeaderBase: JTL_009/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:theirGroundArena-0:3,theirGroundArena-1:1

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:DAMAGE:1
