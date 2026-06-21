# LAW_066 Tear This Ship Apart — look at an opponent's resources, play one for free; that opponent
# resources their deck-top. P2's only resource is SOR_247 (a unit). P1 plays it for free → it enters
# P1's arena (owned by P2, controlled by P1). P2 then refills from deck (SOR_095), so P2's resource
# count is unchanged and their deck drops by 1. The refill enters EXHAUSTED ("resources the top card",
# not "as a ready resource"), so P2 has 0 ready resources afterward.

## GIVEN
P1LeaderBase: JTL_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 13
WithP1Hand: LAW_066
WithP2Resources: 1:SOR_247:1
WithP2Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_247
P2RESCOUNT:1
P2RESAVAILABLE:0
P2DECKCOUNT:0
