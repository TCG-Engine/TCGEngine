# ASH_203 Mando's N-1 Starfighter (Space, 1/3, Support) — On Attack: you may exhaust a friendly leader.
# If you do, this unit gets +2/+0 for this attack. With the leader ready, the player exhausts it and the
# Starfighter deals 3 (1 + 2) to the enemy base.
## GIVEN
CommonSetup: yyk/yyk
WithP1SpaceArena: ASH_203:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:3
P1LEADER:EXHAUSTED
