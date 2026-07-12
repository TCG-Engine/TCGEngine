# TWI_100 Petition the Senate (Event, cost 3, Command/Heroism, Tactic) — "If you control 3 or more
# Official units, draw 3 cards." With three Official units in play (TWI_056, TWI_157, TWI_208), drawing 3.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:TWI_100}
P1OnlyActions: true
WithP1GroundArena: [TWI_056:1:0 TWI_157:1:0 TWI_208:1:0]
WithP1Deck: [SOR_046 SOR_046 SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:3
P1DECKCOUNT:1
