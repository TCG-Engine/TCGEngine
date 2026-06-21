# LAW_004 Aurra Sing (leader front) — "Action [Exhaust]: Defeat a non-leader unit with 1 or less
# remaining HP." P2's SOR_128 (3/1) has 1 remaining HP → it is the only legal target and is defeated.

## GIVEN
P1LeaderBase: LAW_004/SOR_028
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENACOUNT:0
