# ASH_149 Eviscerator (Space, 9/7, cost 8) — When Played: give 2 Advantage tokens to each OTHER friendly
# unit. Playing the Eviscerator gives SOR_095 (ground) and SOR_237 (space) 2 Advantage tokens each; the
# Eviscerator itself gets none.
## GIVEN
CommonSetup: rrk/rrk/{myResources:8;handCardIds:ASH_149}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:2
P1SPACEARENAUNIT:1:ADVANTAGECOUNT:0
