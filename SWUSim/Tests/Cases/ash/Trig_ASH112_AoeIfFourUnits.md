# ASH_112 Luke Skywalker (Ground, 5/5, Restore 1) — When Played: if you control at least 4 units, deal 3
# damage to each enemy unit. P1 controls 3 units + Luke = 4, so each enemy (SEC_080 3/3, SOR_225 2/1)
# takes 3 and is defeated.
## GIVEN
CommonSetup: ggw/ggk/{myResources:6;handCardIds:ASH_112}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
