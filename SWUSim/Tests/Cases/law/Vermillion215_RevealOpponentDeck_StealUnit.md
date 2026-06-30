# LAW_215 Vermillion — "reveal the top card of A deck" is the controller's choice between the two decks.
# Both decks are non-empty here, so P1 is asked which to reveal. P1 reveals the OPPONENT's deck-top
# (Battlefield Marine, cost 2), then chooses ITSELF to play it: P1 gets a free unit (owned by P2, its deck
# owner; controlled by P1), and the other player (P2) creates 2 Credits.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP1Deck: SOR_237
WithP2Deck: SOR_095
WithP2Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Theirs
- P1>AnswerDecision:You
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P2CREDITCOUNT:2
