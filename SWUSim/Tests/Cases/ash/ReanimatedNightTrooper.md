# LookAndDiscardDeckTop
#// ASH_045 Reanimated Night Trooper (Ground, 2/2) — When Defeated: look at the top card of a deck; you may
#// discard it. The Trooper attacks SOR_046 and dies; it looks at the opponent's deck top and discards it
#// (P2 deck 2 → 1, discard 0 → 1).
## GIVEN
CommonSetup: bbk/bbk
WithP1GroundArena: ASH_045:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Deck: [SEC_080 SOR_095]
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Opponent
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:0
P2DECKCOUNT:1
P2DISCARDCOUNT:1

---

# Decline_NoDiscard
#// ASH_045 Reanimated Night Trooper — the discard is optional. On defeat P1 looks at the opponent's deck top
#// but declines, so nothing is discarded.
## GIVEN
CommonSetup: bbk/bbk
WithP1GroundArena: ASH_045:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Deck: [SEC_080 SOR_095]
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Opponent
- P1>AnswerDecision:NO
## EXPECT
P2DECKCOUNT:2
P2DISCARDCOUNT:0

---

# LookOwnDeckAndDiscard
#// ASH_045 Reanimated Night Trooper — "look at the top card of a deck" can pick the CONTROLLER's OWN deck.
#// The Trooper attacks SOR_046 and dies; P1 chooses their own deck and discards the top card. P1 deck 2 → 1,
#// and P1's discard holds both the milled card and the defeated Trooper (2).
## GIVEN
CommonSetup: bbk/bbk
WithP1GroundArena: ASH_045:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SEC_080 SOR_095]
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:You
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:0
P1DECKCOUNT:1
P1DISCARDCOUNT:2

---

# BothDecksEmpty_NoEffect
#// ASH_045 Reanimated Night Trooper — with BOTH decks empty on defeat, whichever deck is chosen has no top
#// card, so nothing is looked at or discarded. The Trooper attacks SOR_046 and dies; choosing a deck yields
#// no discard — both decks stay empty and only the defeated Trooper lands in P1's discard.
## GIVEN
CommonSetup: bbk/bbk
SkipPreGame: true
WithP1GroundArena: ASH_045:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:You
## EXPECT
P1GROUNDARENACOUNT:0
P1DECKCOUNT:0
P2DECKCOUNT:0
P1DISCARDCOUNT:1

---

# SelectedDeckEmpty_NoDiscard
#// ASH_045 Reanimated Night Trooper — the deck choice is offered even when a deck is empty; choosing the
#// empty deck just does nothing. P1's own deck is empty (P2's is not). On defeat P1 chooses their own deck
#// → no card to look at or discard; the opponent's deck is untouched.
## GIVEN
CommonSetup: bbk/bbk
SkipPreGame: true
WithP1GroundArena: ASH_045:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Deck: [SEC_080 SOR_095]
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:You
## EXPECT
P1DECKCOUNT:0
P2DECKCOUNT:2
P2DISCARDCOUNT:0

---

# ControlStolenAtDefeat_OpponentResolves
#// ASH_045 Reanimated Night Trooper — the "When Defeated" ability is resolved by whoever CONTROLS the unit
#// when it dies. P2 plays No Glory, Only Results (JTL_043: take control of a non-leader unit, then defeat
#// it) on the Trooper, so P2 controls it at defeat and P2 makes the choice. P2 picks P1's deck ("Opponent"
#// from P2's frame) and discards its top card. P1 deck 2 → 1.
## GIVEN
CommonSetup: bbk/bbk/{theirResources:8;theirhandCardIds:JTL_043}
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: ASH_045:1:0
WithP1Deck: [SEC_080 SOR_095]
WithP2Deck: [SEC_080 SOR_095]
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Opponent
- P2>AnswerDecision:YES
## EXPECT
P1DECKCOUNT:1
P1DISCARDCOUNT:2

---

# ThrawnReuse_DiscardFromEachDeck
#// ASH_045 Reanimated Night Trooper — with Grand Admiral Thrawn (JTL_002, "When you use a When Defeated
#// ability: you may exhaust this leader to use it again") deployed, one defeat resolves the look twice. The
#// Trooper dies; P1 discards P1's deck top, then reuses via Thrawn and discards P2's deck top — one card
#// milled from EACH deck in a single defeat.
## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_002:1:1}
WithP1GroundArena: ASH_045:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SEC_080 SOR_095]
WithP2Deck: [SEC_080 SOR_095]
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:You
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AnswerDecision:Opponent
- P1>AnswerDecision:YES
## EXPECT
P1DECKCOUNT:1
P2DECKCOUNT:1
P1DISCARDCOUNT:2
P2DISCARDCOUNT:1
