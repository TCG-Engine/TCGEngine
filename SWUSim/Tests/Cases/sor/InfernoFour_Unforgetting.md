# WhenDefeated
#// SOR_031 Inferno Four — WhenDefeated scry 2: trigger fires when defeated in combat.
#// SOR_031 (3/3) attacks P2's SOR_066 (4/6). SOR_031 takes 4 damage and dies.
#// Scry: put SOR_095 on bottom, keep SOR_128 on top.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1SpaceArena: SOR_031:1:0
WithP2SpaceArena: SOR_066:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:SOR_128|SOR_095

## EXPECT
P1DECKTOPCARD:SOR_128

---

# WhenPlayed_KeepBoth
#// SOR_031 Inferno Four — WhenPlayed scry 2: keep both cards on top, preserve order.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1Hand: SOR_031
WithP1Resources: 2
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095,SOR_128|

## EXPECT
P1DECKTOPCARD:SOR_095

---

# WhenPlayed_KeepBothSwap
#// SOR_031 Inferno Four — WhenPlayed scry 2: keep both on top but swap order.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1Hand: SOR_031
WithP1Resources: 2
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_128,SOR_095|

## EXPECT
P1DECKTOPCARD:SOR_128

---

# WhenPlayed_TopToBottom
#// SOR_031 Inferno Four — WhenPlayed scry 2: put top card on bottom, keep second.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1Hand: SOR_031
WithP1Resources: 2
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_128|SOR_095

## EXPECT
P1DECKTOPCARD:SOR_128

---

# NoGloryOnlyResults_NewControllerResolvesIt
#// SOR_031 Inferno Four — a take-control-then-defeat (JTL_043) defeats the unit under the TAKER's
#// control, so the TAKER resolves the When Defeated and "your deck" is the TAKER's deck: P1 looks at
#// the top 2 of P1's deck, keeps SOR_128 on top and puts SOR_095 on the bottom. Inferno Four goes to its OWNER P2's discard.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_043
WithP2SpaceArena: SOR_031:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:SOR_128|SOR_095

## EXPECT
P2SPACEARENACOUNT:0
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_128
P2DISCARDCOUNT:1

---

# WhenPlayed_ScryAnsweredAcrossRequestBoundary
#// SOR_031 Inferno Four — the SCRY panel is an INTERACTIVE decision, so in production the player
#// answers it in a LATER request, in a fresh process. DoScry splices the peeked cards out of the deck
#// and parks them in memory, so the finalize must still find them after the boundary. Same board as
#// WhenPlayed_KeepBothSwap; the only difference is the boundary between the play and the answer.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1Hand: SOR_031
WithP1Resources: 2
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:SOR_128,SOR_095|

## EXPECT
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_128

---

# ScryPutsBOTHOnTheBottom_ThirdCardBecomesTheTop
#// SOR_031 Inferno Four — the UPPER bound of "put ANY NUMBER of them on the bottom". The three existing
#// WhenPlayed sections cover none-to-bottom (KeepBoth, KeepBothSwap) and one-to-bottom (TopToBottom);
#// nothing sends BOTH down, which is the case where the "rest on top" half is the empty set.
#// COVERAGE: offer=the scry panel offers exactly the cards it peeked, asserted through the deck order
#//           the four WhenPlayed_* sections produce (a SCRY decision is not an MZCHOOSE and exposes no
#//           selectable list) · decline=N/A ("put any number ... and the rest ..." has no opt-out to
#//           decline; the zero-move answer IS the decline and is WhenPlayed_KeepBoth) ·
#//           control=NoGloryOnlyResults_NewControllerResolvesIt (defeated under a take-control effect,
#//           the NEW controller resolves it and "your deck" is the TAKER's deck) ·
#//           boundary=this section (2 of 2 to the bottom) vs WhenPlayed_TopToBottom (1 of 2) vs
#//           WhenPlayed_KeepBoth (0 of 2), plus ScryWithASingleCardDeck_NothingIsLost for the deck
#//           holding FEWER cards than the ability looks at · reqboundary=
#//           WhenPlayed_ScryAnsweredAcrossRequestBoundary
#//
#// The deck is three deep. The top two are peeked and both sent to the bottom, so the card that was
#// THIRD is now the top and the deck is still three cards. Per the standing ruling the relative order
#// of cards put on the bottom is not fixed, so only the new top and the count are asserted.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1Hand: SOR_031
WithP1Resources: 2
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_046    # third card — untouched by the scry, so it surfaces

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:|SOR_095,SOR_128

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_031
P1DECKCOUNT:3
P1DECKTOPCARD:SOR_046
P1NODECISION

---

# ScryWithASingleCardDeck_NothingIsLost
#// SOR_031 Inferno Four — "Look at the top 2 cards of your deck" against a deck that holds only ONE.
#// Every other section runs on a deck of exactly the two cards the ability wants, so a look that
#// assumed two would never be seen. The panel must still appear (with the one card it could see), the
#// answer must resolve, and — this is the regression the section is really guarding — the peeked card
#// must come BACK. The splice-out/park-in-memory shape this ability uses previously dropped the peeked
#// cards on the floor, taking the deck to zero without a prompt; a one-card deck is the smallest board
#// on which that failure is unmistakable.
#// The single card is sent to the bottom, which on a one-card deck is also the top.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1Hand: SOR_031
WithP1Resources: 2
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:|SOR_095

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_031
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_095
P1NODECISION
