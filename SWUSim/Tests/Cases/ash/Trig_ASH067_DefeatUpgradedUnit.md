# ASH_067 Get Lost (Event, cost 4) — Defeat an upgraded non-leader unit. The enemy SEC_080 carries SOR_120
# (the only upgraded unit, auto-resolved) and is defeated.
## GIVEN
CommonSetup: bbw/bbk/{myResources:4;handCardIds:ASH_067}
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:0
