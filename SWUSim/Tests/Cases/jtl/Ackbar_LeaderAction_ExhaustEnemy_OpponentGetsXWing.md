# JTL_016 Admiral Ackbar (leader) — Action [1 resource, Exhaust]: Exhaust a non-leader unit. If you do,
# its controller creates an X-Wing token. P1 exhausts the enemy SOR_095, so P2 (its controller) creates
# an X-Wing (JTL_T02) in P2's space arena.

## GIVEN
P1LeaderBase: JTL_016/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:EXHAUSTED
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:JTL_T02
P1RESAVAILABLE:0
P1LEADER:EXHAUSTED
