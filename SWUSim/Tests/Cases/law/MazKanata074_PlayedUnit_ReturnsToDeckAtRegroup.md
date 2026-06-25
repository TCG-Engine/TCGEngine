# LAW_074 Maz Kanata — "At the start of the regroup phase, put that unit on the bottom of your deck (if
# still in play)." Maz plays SOR_247 via her attack-end ability; at the regroup phase it returns to the
# bottom of the deck (NOT the discard). After regroup only Maz remains in the arena; SOR_247 is back in
# the deck (deck = 6 bottomed − 2 drawn at regroup = 4), and the discard is empty.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1GroundArena: LAW_074:1:0
WithP1Deck: SOR_247
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:SOR_247
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_074
P1DISCARDCOUNT:0
P1DECKCOUNT:4
