# SOR_004 Chirrut Îmwe — interaction of his HP-survival rule with the regroup ordering.
# P2 deals 4 damage to the deployed Chirrut (3/5) with Open Fire, then shrinks him -2/-2 with
# Make an Opening (effective HP 3 → no remaining HP). During the action phase he survives (immune).
# At regroup the -2/-2 debuff is removed BEFORE the defeat sweep, so his HP is back to 5 and 5-4=1
# remaining HP — Chirrut LIVES. (Targeting him at all relies on the deployed-leader ZoneSearch fix.)

## GIVEN
P1LeaderBase: SOR_004/SOR_024
P2LeaderBase: SOR_011/SOR_021
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 5
WithP2Resources: 6
WithP2Hand: SOR_172
WithP2Hand: SOR_076

## WHEN
- P1>DeployLeader
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>PlayHand:0
- P1>Pass
- P2>Pass

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_004
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:HP:5
P1LEADER:DEPLOYED
