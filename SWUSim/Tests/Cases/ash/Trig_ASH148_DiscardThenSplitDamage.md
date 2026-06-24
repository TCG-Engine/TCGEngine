# ASH_148 Ninth Sister (Ground, 8/7, Overwhelm, cost 7) — When Played: an opponent discards a card; you
# may deal damage equal to its cost divided among any number of units. P2 discards SOR_046 (cost 4, its
# only card), and P1 assigns all 4 to SEC_080 (3/3), defeating it.
## GIVEN
CommonSetup: rrk/rrk/{myResources:7;handCardIds:ASH_148;theirHandCardIds:SOR_046}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:4
## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:2
