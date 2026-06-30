# ASH_084 Arcana Star Map (Upgrade, cost 1) — "Attached unit gains: if you would search a number of cards
# from your deck, search twice that number instead." P1 controls SOR_095 wearing ASH_084, then plays
# SOR_084 Grand Moff Tarkin (search top 5 for Imperial). Doubled to top 10, the search reaches the lone
# Imperial (SOR_085) at depth 7 and draws it (it would be unreachable in the top 5).
## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Resources: 4
WithP1Hand: SOR_084
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_084
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_085
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_085
## EXPECT
P1HANDCOUNT:1
