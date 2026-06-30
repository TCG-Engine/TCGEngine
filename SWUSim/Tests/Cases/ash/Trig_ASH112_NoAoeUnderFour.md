# ASH_112 Luke Skywalker — the AoE fires ONLY with 4+ friendly units. P1 controls 1 unit + Luke = 2
# (< 4), so the enemy SEC_080 and SOR_225 take no damage and survive.
## GIVEN
CommonSetup: ggw/ggk/{myResources:6;handCardIds:ASH_112}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:1
P2SPACEARENACOUNT:1
