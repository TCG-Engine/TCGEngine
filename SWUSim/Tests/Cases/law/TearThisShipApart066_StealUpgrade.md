# LAW_066 — stealing an UPGRADE from an opponent's resources. P2's resource is SOR_120 Academy Training
# (+2/+2). P1 controls SOR_247 (2/3) as the only valid host, so the attach auto-resolves: SOR_247
# becomes 4/5. P2 then refills from deck.

## GIVEN
P1LeaderBase: JTL_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 13
WithP1Hand: LAW_066
WithP1GroundArena: SOR_247:1:0
WithP2Resources: 1:SOR_120:1
WithP2Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_247
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2RESCOUNT:1
P2RESAVAILABLE:0
P2DECKCOUNT:0
