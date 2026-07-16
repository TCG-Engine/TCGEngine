# ControllerPlays_OpponentGetsCredits
#// LAW_215 Vermillion (5/7 Space) — When Attack Ends (survived): reveal the top card of a deck, choose a
#// player to play it for free; a DIFFERENT player creates Credits = that card's cost. Here P1 reveals its
#// own deck (P2's is empty → auto), chooses ITSELF to play the revealed Battlefield Marine (cost 2) for
#// free, and the other player (P2) creates 2 Credits.

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

---

# Declined_NoPlayNoCredits
#// LAW_215 Vermillion — "They MAY play the revealed card." Declining means nothing is played and NO
#// Credits are created (the Credit clause is gated on "if they do"). The revealed Battlefield Marine stays
#// on top of P1's deck (deck count unchanged), no unit enters play, and neither player gets Credits.

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
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:0
P1DECKCOUNT:2
P1CREDITCOUNT:0
P2CREDITCOUNT:0

---

# OpponentPlays_ControllerGetsCredits
#// LAW_215 Vermillion — the cross-player branch. P1 reveals its own deck-top (Battlefield Marine) but
#// chooses the OPPONENT (P2) to play it. P2 plays it for free — it enters P2's arena owned by P1 (its deck
#// owner), controlled by P2 — and the DIFFERENT player (P1) creates 2 Credits.

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
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P1CREDITCOUNT:2
P2CREDITCOUNT:0

---

# RevealOpponentDeck_StealUnit
#// LAW_215 Vermillion — "reveal the top card of A deck" is the controller's choice between the two decks.
#// Both decks are non-empty here, so P1 is asked which to reveal. P1 reveals the OPPONENT's deck-top
#// (Battlefield Marine, cost 2), then chooses ITSELF to play it: P1 gets a free unit (owned by P2, its deck
#// owner; controlled by P1), and the other player (P2) creates 2 Credits.

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
