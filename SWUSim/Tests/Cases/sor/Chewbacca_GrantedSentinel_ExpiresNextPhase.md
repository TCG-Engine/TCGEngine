# SOR_003 Chewbacca — the granted Sentinel is "for this phase" only. P1 plays SOR_237 via the leader
# action (it gains Sentinel), then passes to end the action phase; RegroupPhaseStart expires the
# SOR_003 phase-duration token, so the X-Wing no longer has Sentinel. It survives (undamaged), so the
# unit is still in play — only the keyword is gone.

## GIVEN
P1LeaderBase: SOR_003/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_237

## WHEN
- P1>UseLeaderAbility
- P1>Pass

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel
