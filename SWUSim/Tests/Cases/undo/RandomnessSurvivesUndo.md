# DeckSearchRemainder_NoUndo
#// ── THE ENGINE-RANDOMNESS / UNDO CONTRACT ────────────────────────────────────────────────────────
#// Every game-affecting random pick in SWUSim MUST come from Core/DeterministicRNG.php
#// (EngineShuffle / EngineRandomInt), never from PHP's shuffle() / array_rand() / mt_rand().
#// PHP's Mt19937 is auto-seeded per process from the OS: it derives from neither the gamestate nor the
#// per-game secret RNG_SEED, does not advance $gRandomCounter, and is NOT captured by an undo snapshot.
#// The engine's stream is all three, so a random outcome is unpredictable to players yet REPRODUCIBLE
#// when the same action is replayed after an undo — which is the property this file guards.
#//
#// This is the same defect the Versions-zone exclusion in EngineDeterministicIgnoredStateNames()
#// (Core/DeterministicRNG.php:22) exists to prevent one layer up: "mulligan → undo → mulligan reseeds
#// off a different undo-stack and draws a different hand".
#//
#// ⚠ THE CARD-ID LITERALS BELOW ARE THE DETERMINISTIC STREAM'S OUTPUT, NOT A RULES FACT.
#// They are correct only while the fixed inputs (board, deck contents, counter position) are unchanged.
#// If a legitimate engine change moves the stream, the two sections of a PAIR move TOGETHER — re-derive
#// the literal from the NoUndo section and put the SAME value in its WithUndo twin. If only ONE of a
#// pair moves, the undo/redo contract is broken and that is a real bug: do not "fix" it by splitting
#// the literals.
#//
#// PAIR 1 — the TOPDECKSEARCH remainder (_topDeckPutRemainingToBottom, GameLogic.php).
#// LOF_103 Following the Path searches the top 8 for up to 2 Force units; declining ("-") sends all 8
#// to the BOTTOM OF THE DECK IN A SHUFFLED ORDER. The deck here is exactly those 8 cards, so the deck's
#// top card after the search IS the first card of that shuffle — a 1-in-8 observable.
#// This section is the control: the shuffle with no undo involved.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1;handCardIds:LOF_103}
P1OnlyActions: true
WithP1Deck: LOF_077
WithP1Deck: LOF_050
WithP1Deck: SOR_049
WithP1Deck: SOR_171
WithP1Deck: SOR_210
WithP1Deck: SOR_190
WithP1Deck: SOR_203
WithP1Deck: SOR_228

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1DECKCOUNT:8
P1DECKTOPCARD:SOR_190

---

# DeckSearchRemainder_SurvivesUndoAndRedo
#// PAIR 1, the undo half — THE USER-VISIBLE HALF OF THE BUG. Identical board and identical action, but
#// the search is played, undone, and played again. The deck order must come out the SAME as the control
#// above (SOR_190 on top), because the undo snapshot rewinds $gRandomCounter along with the zones.
#// Measured with PHP's shuffle() in _topDeckPutRemainingToBottom(): five consecutive runs of this file
#// gave (control → undo half) SOR_049→SOR_171, SOR_171→LOF_077, SOR_049→SOR_171, SOR_190→SOR_228 and
#// LOF_050→SOR_210 — i.e. undoing past a deck search and redoing it dealt a DIFFERENT deck every time.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1;handCardIds:LOF_103}
P1OnlyActions: true
WithP1Deck: LOF_077
WithP1Deck: LOF_050
WithP1Deck: SOR_049
WithP1Deck: SOR_171
WithP1Deck: SOR_210
WithP1Deck: SOR_190
WithP1Deck: SOR_203
WithP1Deck: SOR_228

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>Undo
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1DECKCOUNT:8
P1DECKTOPCARD:SOR_190

---

# RandomDiscard_NoUndo
#// PAIR 2 — the other shape in the family: a random pick out of a hand rather than a shuffle.
#// SOR_190 Lothal Insurgent, "When Played: If you played another card this phase, each opponent draws a
#// card then discards a random card from their hand." P2 holds five cards and draws a sixth, so the
#// discard is a genuine 1-in-6 pick (cards/sor/LothalInsurgent.php). Control: no undo.

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_210
WithP1Hand: SOR_190
WithP2Hand: SOR_171
WithP2Hand: SOR_203
WithP2Hand: SOR_228
WithP2Hand: SOR_189
WithP2Hand: SOR_049
WithP2Deck: SOR_077

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_077

---

# RandomDiscard_SurvivesUndoAndRedo
#// PAIR 2, the undo half. Lothal is played, undone, and played again; the SAME card must be discarded.
#// Measured with array_rand() in cards/sor/LothalInsurgent.php: five consecutive runs disagreed between
#// the control and this section in every run (e.g. control SOR_203 → undo half SOR_049).

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_210
WithP1Hand: SOR_190
WithP2Hand: SOR_171
WithP2Hand: SOR_203
WithP2Hand: SOR_228
WithP2Hand: SOR_189
WithP2Hand: SOR_049
WithP2Deck: SOR_077

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>Undo
- P1>PlayHand:0

## EXPECT
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_077
