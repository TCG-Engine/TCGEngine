# OnAttack_PutOnBottom
#// SOR_236 R2-D2 — OnAttack scry 1: put top card on bottom.
#// COVERAGE: offer=LooksAtEXACTLYOneCard_TheSecondIsOutOfTheWindow — a scry is answered with CardIDs
#//           rather than arena mzIDs, so P1SELECTABLEEXACT reads nothing off it; the pool is instead
#//           asserted by proving a card OUTSIDE the one-card window cannot be moved, with
#//           OnAttack_PutOnBottom as the passing control that the in-window card CAN be ·
#//           reqboundary=SimulateRequestBoundary_PeekedCardSurvivesTheAnswer (the peeked card must
#//           stay IN the deck across the boundary; this family's historical failure was destroying it)
#//           · control=ControlTakenR2_LooksAtTheCONTROLLERsDeck (owner differs from controller: "your
#//           deck" resolves for the CONTROLLER, and both decks are asserted so a wrong-seat peek
#//           cannot hide behind equal counts) · boundary=SingleCardDeck_BottomAnswerDoesNotDESTROYIt
#//           (N=1, where bottom and top are the same slot) and EmptyDeck_NoLookAtAll (N=0, no decision
#//           at all) against the two-card sections — the card count is this ability's only quantity ·
#//           decline=WhenPlayed_KeepOnTop + OnAttack_KeepOnTop, one for each of the two trigger
#//           windows ("you MAY put it on the bottom … otherwise leave it on top").

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_236:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:|SOR_095

## EXPECT
P1DECKTOPCARD:SOR_128
P2BASEDMG:1

---

# WhenPlayed_KeepOnTop
#// SOR_236 R2-D2 — WhenPlayed scry 1: choose to keep top card on top.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1Hand: SOR_236
WithP1Resources: 2
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095|

## EXPECT
P1DECKTOPCARD:SOR_095

---

# WhenPlayed_PutOnBottom
#// SOR_236 R2-D2 — WhenPlayed scry 1: put top card on bottom.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1Hand: SOR_236
WithP1Resources: 2
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:|SOR_095

## EXPECT
P1DECKTOPCARD:SOR_128

---

# OnAttack_KeepOnTop
#// SOR_236 R2-D2 — the decline half of the ON ATTACK window. "You MAY put it on the bottom of your
#// deck. (Otherwise, LEAVE IT ON TOP.)" R2-D2 attacks P2's base and P1 keeps the peeked card where it
#// was: the deck top is still SOR_095, the deck is still 2 cards, nothing is drawn, and the attack's 1
#// damage lands as normal.
#// The file already declines in the WHEN PLAYED window (WhenPlayed_KeepOnTop); this is the same choice
#// through the other trigger. The two windows share one handler but are wired by two separate ability
#// registrations, so a window that dropped its bottom/keep continuation would be invisible here
#// otherwise — and a keep that silently reordered the deck is caught by DECKTOPCARD.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_236:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:SOR_095|

## EXPECT
P1DECKTOPCARD:SOR_095
P1DECKCOUNT:2
P1HANDCOUNT:0
P2BASEDMG:1
P1NODECISION

---

# EmptyDeck_NoLookAtAll
#// SOR_236 R2-D2 — "Look at the top card of your deck" with NO deck. There is nothing to look at, so
#// no decision is raised at all: the attack resolves straight through for its 1 damage and the ability
#// is a silent no-op. Guards a scry that queues an empty peek and leaves the player staring at a
#// decision they cannot answer — which in production stalls the action rather than merely fizzling.
#// Note this is NOT the deck-out rule: nothing is DRAWN here, so P1's base takes no damage.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_236:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P1DECKCOUNT:0
P1HANDCOUNT:0
P1BASEDMG:0
P2BASEDMG:1

---

# SingleCardDeck_BottomAnswerDoesNotDESTROYIt
#// SOR_236 R2-D2 — the degenerate N=1 deck. The only card is peeked and sent to the bottom, which in a
#// one-card deck is the same slot it came from: deck count 1 and SOR_095 still on top.
#// This is the exact shape of the failure this family is prone to — a peek implemented by splicing the
#// cards OUT of the deck loses them if the finalize does not put them back, and a one-card deck makes
#// the loss unambiguous (1 -> 0) instead of merely reordered. Both halves are asserted: the count, and
#// that the card is a real deck entry rather than having drifted into a hand or a discard.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_236:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:|SOR_095

## EXPECT
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_095
P1HANDCOUNT:0
P1DISCARDCOUNT:0
P2BASEDMG:1

---

# LooksAtEXACTLYOneCard_TheSecondIsOutOfTheWindow
#// SOR_236 R2-D2 — "look at THE TOP CARD" (singular). The window is one card deep.
#//
#// ⚠ THIS SECTION WAS RE-ENCODED (2026-09-01, same pass that wrote it). Its first form proved the scope
#// by answering with the SECOND card (`|SOR_128`) and asserting that nothing moved — which worked only
#// because SCRY answers were NOT validated: SCRY_FINALIZE is deliberately forgiving ("any peeked card the
#// answer fails to account for goes back on top"), so an out-of-window answer was a silent no-op.
#// Closing that validation hole turned the silent no-op into an outright refusal, which is the stronger
#// guarantee but cannot be written as an assertion. So the window depth is now proven POSITIVELY: put
#// the one peeked card on the bottom and watch the SECOND card — never peeked — become the new top.
#// A two-deep peek would have offered SOR_128 as well; a resolve against the whole deck rather than the
#// peeked set would let SOR_128 be buried. Neither can produce this end state.
#// (The out-of-window answer is now refused ENGINE-WIDE by SWUValidateDecisionAnswer, so that guarantee
#// no longer needs a per-card section at all.)

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_236:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_046

## WHEN
- P1>AttackGroundArena:0:BASE
#// the ONE peeked card, sent to the bottom
- P1>AnswerDecision:|SOR_095

## EXPECT
#// the second card — outside the window — is simply the next one down, now on top
P1DECKTOPCARD:SOR_128
P1DECKCOUNT:3
P1HANDCOUNT:0
P1NODECISION

---

# ControlTakenR2_LooksAtTheCONTROLLERsDeck
#// SOR_236 R2-D2 × a control change. R2-D2 sits in P1's arena under P1's CONTROL but OWNED by P2. "Look
#// at the top card of YOUR deck" is resolved by the ability's controller, so it is P1's deck that is
#// peeked and reordered: P1's top goes from SOR_095 to SOR_128, while P2's deck keeps its own top card
#// and its own count. The attack likewise belongs to P1 and hits P2's base.
#// An implementation that resolved a unit's On Attack from the OWNER's seat would shuffle the wrong
#// deck — and because both decks stay the same SIZE, only the per-seat TOP CARD assertions catch it.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArenaControlled: SOR_236:2
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP2Deck: SOR_046
WithP2Deck: SOR_067

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:|SOR_095

## EXPECT
P1DECKTOPCARD:SOR_128
P1DECKCOUNT:2
P2DECKTOPCARD:SOR_046
P2DECKCOUNT:2
P1HANDCOUNT:0
P2BASEDMG:1

---

# SimulateRequestBoundary_PeekedCardSurvivesTheAnswer
#// SOR_236 R2-D2 — in production the look-at-top decision ENDS the request: the answer arrives in a
#// fresh process where every non-serialized global is empty. This is the failure mode that once
#// DESTROYED the peeked cards outright for this whole family, so the boundary is asserted on the count
#// as well as the order: deck 3 before, deck 3 after, hand still empty, and the new top is the card
#// that was second.
#// The peeked card must therefore stay IN the deck across the boundary, with only the finalize
#// mutating it — a scry that holds its peek in memory reads as a card vanishing here.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_236:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_046

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:|SOR_095

## EXPECT
P1DECKTOPCARD:SOR_128
P1DECKCOUNT:3
P1HANDCOUNT:0
P1DISCARDCOUNT:0
P2BASEDMG:1
