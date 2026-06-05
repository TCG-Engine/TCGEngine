# JTL_007 Admiral Holdo (deployed leader unit) — On Attack: You may give ANOTHER Resistance unit (or a
# unit with a Resistance upgrade) +2/+2 this phase. P1 deploys Holdo (free epic, 6-resource threshold),
# attacks P2's base, and buffs the other Resistance unit JTL_099 (2/1 → 4/3). "Another" excludes Holdo.

## GIVEN
P1LeaderBase: JTL_007/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: JTL_099:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_099
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:3
P2BASEDMG:3
P1LEADER:DEPLOYED
