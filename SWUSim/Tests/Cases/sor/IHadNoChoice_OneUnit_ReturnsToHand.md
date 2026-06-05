# SOR_187 I Had No Choice — when the caster chooses only ONE unit, the opponent's "choose 1 of those"
# is forced and there is no "other," so that unit just returns to its owner's hand (no deck-bottom).
# P1 picks SEC_080; it returns to P2's hand, SOR_128 stays in play.

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_187
WithP1Resources: 9
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
WithP2Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P2HANDCOUNT:1
P2DECKCOUNT:1
P1DISCARDCOUNT:1
