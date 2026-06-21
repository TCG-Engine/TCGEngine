# LAW_002 Tobias Beckett (leader front) — "Action [Exhaust]: Choose a friendly unit. An opponent takes
# control of it. If they do, create a Credit token." P1 gives its only unit (SEC_080) to P2 and creates
# 1 Credit → SEC_080 moves to P2's arena.

## GIVEN
P1LeaderBase: LAW_002/SOR_028
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P1CREDITCOUNT:1
