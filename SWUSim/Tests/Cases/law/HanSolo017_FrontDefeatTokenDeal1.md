# LAW_017 Han Solo (leader front) — "Action [Exhaust, defeat a friendly token]: Deal 1 damage to a
# unit." P1's only friendly token (JTL_T01 TIE Fighter) is defeated as the cost, and 1 damage is dealt
# to P2's SOR_128 (3/1), defeating it.

## GIVEN
P1LeaderBase: LAW_017/SOR_028
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_T01:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
