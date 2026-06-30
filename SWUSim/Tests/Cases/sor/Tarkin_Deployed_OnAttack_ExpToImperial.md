# SOR_007 Grand Moff Tarkin — deployed leader unit (2/7) On Attack: You may give an Experience
# token to ANOTHER Imperial unit. Deployed Tarkin (the only ground unit) attacks the base; on
# YES the only other Imperial unit — SOR_225 (2/3, space) — auto-receives +1/+1 (→ 3/4). The
# base takes Tarkin's 2 power.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1SpaceArena: SOR_225:1:0     # another Imperial unit (space) — Experience recipient

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:POWER:3
P2BASEDMG:2
P1LEADER:EPICUSED
