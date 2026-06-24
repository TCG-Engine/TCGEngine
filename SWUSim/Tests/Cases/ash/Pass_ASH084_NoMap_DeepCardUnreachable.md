# ASH_084 control — WITHOUT Arcana Star Map, Tarkin's search stays at top 5, which is all non-Imperial
# SOR_063 filler, so the depth-7 Imperial (SOR_085) is never seen and nothing is drawn (hand stays empty).
## GIVEN
P1LeaderBase: SOR_007/SOR_024
P2LeaderBase: SOR_002/SOR_020
SkipPreGame: true
WithP1Resources: 4
WithP1Hand: SOR_084
WithP1GroundArena: SOR_095:1:0
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
- P1>AnswerDecision:
## EXPECT
P1HANDCOUNT:0
