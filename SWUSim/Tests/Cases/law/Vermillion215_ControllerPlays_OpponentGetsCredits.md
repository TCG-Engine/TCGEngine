# LAW_215 Vermillion (5/7 Space) — When Attack Ends (survived): reveal the top card of a deck, choose a
# player to play it for free; a DIFFERENT player creates Credits = that card's cost. Here P1 reveals its
# own deck (P2's is empty → auto), chooses ITSELF to play the revealed Battlefield Marine (cost 2) for
# free, and the other player (P2) creates 2 Credits.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:You
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P2CREDITCOUNT:2
P1CREDITCOUNT:0
