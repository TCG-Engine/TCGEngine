# FiresRightAfterSetup_BeforeTheFirstAction
#// SWUSim Replay Schema
#// JTL_028 Nabat Village — "When the first action phase starts: Put 3 cards from your hand on the bottom of your
#// deck in any order." Official ruling (03/06/2025): "resolved at the start of the first action phase, after setup
#// but before the player with initiative has taken their first action."
#// FOUND 2026-09-14 in a live Bot Practice game: the engine resolved it a whole ROUND late. SWUSim/CreateGame.php
#// puts a new game straight into APS and runs setup there, so ActionPhaseStart() — where the bottom-3 lived — never
#// ran for round 1; the first call was round 2's. The prompt now fires when setup ends and the game enters MAIN.
#// (NabatVillage.md's two SkipPreGame sections load straight into MAIN, where setup never ran; they still reach it
#// through round 2's ActionPhaseStart, which stays as the once-per-game fallback.)
#// P1 is on Nabat: draws 9, resources 2 → 7 in hand, and is asked for the 3 before anyone acts.
## GIVEN
P1LeaderBase: SOR_014/JTL_028
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2LeaderBase: SOR_014/SOR_024
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
InitChoice: 1
## WHEN
- P1>MulliganNo
- P1>ResourceHand:0
- P1>ResourceHand:0
- P2>MulliganNo
- P2>ResourceHand:0
- P2>ResourceHand:0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Put_3_cards_from_your_hand_on_the_bottom_of_your_deck_(Nabat_Village)
P1HANDCOUNT:7
P2NODECISION

---

# AnsweringItPutsThreeOnTheBottom_InRoundOne
#// Same setup; P1 picks three → 4 in hand, deck 11 + 3 = 14, nothing left to answer.
## GIVEN
P1LeaderBase: SOR_014/JTL_028
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2LeaderBase: SOR_014/SOR_024
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
InitChoice: 1
## WHEN
- P1>MulliganNo
- P1>ResourceHand:0
- P1>ResourceHand:0
- P2>MulliganNo
- P2>ResourceHand:0
- P2>ResourceHand:0
- P1>AnswerDecision:myHand-0&myHand-1&myHand-2
## EXPECT
P1HANDCOUNT:4
P1DECKCOUNT:14
P1NODECISION

---

# OncePerGame_RoundTwoDoesNotAskAgain
#// After the round-1 answer, a full round: the regroup draws 2 (4 → 6) and round 2's action phase start must NOT
#// raise the prompt again (the once-per-game flag the round-1 prompt set).
## GIVEN
P1LeaderBase: SOR_014/JTL_028
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2LeaderBase: SOR_014/SOR_024
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
InitChoice: 1
## WHEN
- P1>MulliganNo
- P1>ResourceHand:0
- P1>ResourceHand:0
- P2>MulliganNo
- P2>ResourceHand:0
- P2>ResourceHand:0
- P1>AnswerDecision:myHand-0&myHand-1&myHand-2
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
## EXPECT
P1HANDCOUNT:6
P1DECKCOUNT:12
P1NODECISION

---

# TheOpponentsNabatAsksThemBeforeTheInitiativePlayerActs
#// P2 is on Nabat and P1 holds the initiative: the prompt is P2's, raised when setup ends, and P1 has not acted.
#// This is the live Bot Practice shape (the bot on Nabat). A non-Nabat seat is never asked.
## GIVEN
P1LeaderBase: SOR_014/SOR_024
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2LeaderBase: SOR_014/JTL_028
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
InitChoice: 1
## WHEN
- P1>MulliganNo
- P1>ResourceHand:0
- P1>ResourceHand:0
- P2>MulliganNo
- P2>ResourceHand:0
- P2>ResourceHand:0
## EXPECT
P2HASDECISION
P2DECISIONTOOLTIP:Put_3_cards_from_your_hand_on_the_bottom_of_your_deck_(Nabat_Village)
P2HANDCOUNT:7
P1NODECISION
P1HANDCOUNT:4
