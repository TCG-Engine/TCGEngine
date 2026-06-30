# ASH_202 Carson Teva (Ground, 1/4, Support) — "While attacking, this unit deals combat damage before the
# defender." Carson attacks SOR_128 (3/1): deals 1 first, killing it, so it deals NO counter — Carson
# takes 0. (Without deal-first, the 3-power counter would have hit Carson for 3.)
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_202:1:0
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:ASH_202
P1GROUNDARENAUNIT:0:DAMAGE:0
