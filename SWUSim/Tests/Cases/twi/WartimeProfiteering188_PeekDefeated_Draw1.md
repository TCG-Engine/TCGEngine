# TWI_188 Wartime Profiteering (Event, cost 1, Cunning/Villainy, Supply) — "Look at cards from the top of
# your deck equal to the number of units that were defeated this phase. Draw 1 and put the others on the
# bottom." A 3/1 vs 3/1 trade defeats 2 units this phase, so 2 cards are looked at; drawing 1 (SOR_046).

## GIVEN
CommonSetup: yyk/rrk/{myResources:1;handCardIds:TWI_188}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Deck: [SOR_046 SOR_046 SOR_046]

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:2
