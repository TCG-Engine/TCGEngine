# SHD_163 Migs Mayfeld — "When a player discards a card from their hand: You may deal 2 damage to a unit
# or base. Once each round." P1 plays SHD_244 (No Bargain), forcing P2 to discard its only card; Migs
# then deals 2 to the enemy SOR_046.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SHD_163:1:0
WithP1Hand: SHD_244
WithP1Deck: SOR_095
WithP2Hand: SOR_095
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2HANDCOUNT:0
