# ASH_067 Get Lost — only an UPGRADED unit can be defeated. With the enemy SEC_080 carrying no upgrade,
# Get Lost has no legal target and fizzles (SEC_080 survives).
## GIVEN
CommonSetup: bbw/bbk/{myResources:4;handCardIds:ASH_067}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:1
