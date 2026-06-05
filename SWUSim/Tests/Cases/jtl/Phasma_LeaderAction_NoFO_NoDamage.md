# JTL_010 Captain Phasma (leader) — without having played a First Order card this phase, the action
# does nothing (the leader still exhausts). No base takes damage and no decision is pending. Gate test.

## GIVEN
P1LeaderBase: JTL_010/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:0
P2BASEDMG:0
P1LEADER:EXHAUSTED
P1NODECISION
