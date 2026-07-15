# TS26_061 Encircle (Event, cost 5, Command) — costs 1 less per friendly unit; a friendly unit captures
# an enemy non-leader unit in the same arena. With 1 friendly unit the cost is 4 (only affordable via the
# discount: 4 resources - 4 = 0). The friendly SEC_080 captures the enemy SOR_095 in the ground arena.
## GIVEN
CommonSetup: ggk/rrk/{myResources:4;handCardIds:TS26_061}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1RESAVAILABLE:0
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
