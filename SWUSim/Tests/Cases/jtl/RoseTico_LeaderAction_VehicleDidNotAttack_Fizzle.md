# JTL_004 Rose Tico (leader) — the heal only applies to a Vehicle that ATTACKED this phase. Here the
# damaged X-Wing never attacked, so there is no eligible target and the action fizzles (leader still
# exhausts, the X-Wing keeps its 2 damage). Proves the "that attacked this phase" restriction.

## GIVEN
P1LeaderBase: JTL_004/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:2

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:2
P1LEADER:EXHAUSTED
P1NODECISION
