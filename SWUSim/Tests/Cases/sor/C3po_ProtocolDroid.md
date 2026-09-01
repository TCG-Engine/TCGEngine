# OnAttack_MatchDraw
#// SOR_238 C-3PO — On Attack window (same ability fires when C-3PO attacks). C-3PO (in play, ready,
#// power 1) attacks P2's base; the On Attack trigger resolves first: choose 2 (matches SOR_095) →
#// Draw → SOR_095 drawn (deck 3→2, hand 0→1). Then combat deals C-3PO's 1 power to P2's base.
#// COVERAGE: offer=the reveal step is an OPTIONCHOOSE whose LABEL SET is itself the pool, and it differs
#//           by branch — "Draw"/"Leave" on a match (WhenPlayed_MatchDraw / WhenPlayed_MatchLeave) but
#//           only "OK" on a whiff (WhenPlayed_NoMatch / OnAttack_NoMatch_StaysOnTop), so a "Draw" answer
#//           on a whiff is rejected outright by the answer validator rather than acted on. That pair of
#//           branches IS the pool assertion; P1SELECTABLEEXACT reads nothing off a NUMBERCHOOSE or an
#//           OPTIONCHOOSE · reqboundary=SimulateRequestBoundary_NumberThenRevealSurviveTwoBoundaries
#//           (this ability ends the request TWICE) · control=ControlTakenC3PO_ReadsAndDrawsFromThe
#//           CONTROLLERsDeck (owner differs from controller, and the owner's deck is topped with a card
#//           of the SAME cost so only per-seat identity separates the two behaviours) ·
#//           boundary=WhenPlayed_MatchDraw (chosen 2 = cost 2, hit) vs WhenPlayed_OffByOne_ChoosingOne
#//           AgainstACostTwoTopCard (chosen 1, miss) and WhenPlayed_NoMatch (chosen 5, miss) — the
#//           N-1/N/N+ triple that pins "IS the chosen number" to equality rather than a threshold;
#//           EmptyDeck_NoNumberChooseAtAll is the zero-card edge · decline=WhenPlayed_MatchLeave ("you
#//           MAY reveal and draw it": Leave keeps the card on top and the hand empty).

## GIVEN
CommonSetup: ggw/ggw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_238:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:2
- P1>AnswerDecision:Draw

## EXPECT
P2BASEDMG:1
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P1DECKCOUNT:2
P1GROUNDARENACOUNT:1

---

# WhenPlayed_MatchDraw
#// SOR_238 C-3PO (Unit 1/4, cost 2, Heroism) — When Played/On Attack: choose a number, then look at
#// the top card; if its cost is the chosen number, you may reveal and draw it. P1 plays C-3PO and
#// chooses 2 (blindly). The top card SOR_095 (Battlefield Marine) costs 2 → matches → the player is
#// offered the card and chooses Draw → SOR_095 is drawn (hand 0→1, deck 3→2).

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_238
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:2
- P1>AnswerDecision:Draw

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P1DECKCOUNT:2
P1DISCARDCOUNT:0

---

# WhenPlayed_MatchLeave
#// SOR_238 C-3PO — match but decline: P1 chooses 2 (matches SOR_095's cost 2), is offered the card,
#// and chooses Leave → nothing drawn, the card stays on top of the deck. ("you MAY reveal and draw")

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_238
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:2
- P1>AnswerDecision:Leave

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:3
P1DECKTOPCARD:SOR_095
P1DISCARDCOUNT:0
P1NODECISION

---

# WhenPlayed_NoMatch
#// SOR_238 C-3PO — whiff: P1 chooses 5, but the top card SOR_095 costs 2 → no match. The player
#// STILL gets to look at the top card (the peek always happens — "Choose a number, THEN look at the
#// top card"), but the only outcome is to acknowledge and leave it on top: nothing is revealed or
#// drawn, and the card stays on top.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_238
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:5
- P1>AnswerDecision:OK

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:3
P1DECKTOPCARD:SOR_095
P1DISCARDCOUNT:0
P1NODECISION

---

# OnAttack_NoMatch_StaysOnTop
#// SOR_238 C-3PO — the whiff branch through the ON ATTACK window (WhenPlayed_NoMatch covers it through
#// the other one). C-3PO attacks P2's base, P1 chooses 5, and the top card SOR_095 costs 2 — no match.
#// The peek still happens (the number is chosen BLIND, so the look is owed either way) but the only
#// outcome offered is to acknowledge it: nothing revealed, nothing drawn, SOR_095 still on top and the
#// deck still 2. The attack's 1 damage lands regardless.
#// The two windows share one handler but are registered as two separate abilities, so a whiff path
#// wired into only one of them is invisible without this section.

## GIVEN
CommonSetup: ggw/ggw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_238:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:5
- P1>AnswerDecision:OK

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_095
P2BASEDMG:1
P1NODECISION

---

# WhenPlayed_OffByOne_ChoosingOneAgainstACostTwoTopCard
#// SOR_238 C-3PO — "If its cost IS the chosen number": an EQUALITY, not a threshold. WhenPlayed_NoMatch
#// chooses 5 against a cost-2 card, which any of "==", ">=" or "<=" would reject; this chooses 1, the
#// adjacent number below, which a "<=" comparison would ACCEPT. Together they are the N vs N-1 pair
#// that pins the test to equality on the low side (WhenPlayed_MatchDraw at exactly 2 is the hit).
#// Nothing is drawn, the deck stays 2 and SOR_095 stays on top.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_238
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:1
- P1>AnswerDecision:OK

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_095
P1GROUNDARENACOUNT:1
P1NODECISION

---

# EmptyDeck_NoNumberChooseAtAll
#// SOR_238 C-3PO — with an EMPTY deck there is no top card to look at, so the ability is a silent
#// no-op: the number picker is never even raised. C-3PO still enters play.
#// The order here is what makes the section load-bearing. "Choose a number, THEN look at the top card"
#// tempts an implementation into asking for the number first and discovering the empty deck afterwards,
#// which in production leaves the player answering a picker that can lead nowhere. Nothing is drawn
#// either, so this is not the deck-out rule and P1's base takes no damage.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_238

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1DECKCOUNT:0
P1HANDCOUNT:0
P1BASEDMG:0

---

# ControlTakenC3PO_ReadsAndDrawsFromTheCONTROLLERsDeck
#// SOR_238 C-3PO × a control change. C-3PO sits in P1's arena under P1's CONTROL but OWNED by P2. "Look
#// at the top card of YOUR deck … reveal and draw it" resolves for the ability's CONTROLLER, so the
#// card that is compared against the chosen number, and the card that is drawn, both come from P1's
#// deck: P1 draws SOR_095 (cost 2, matching the chosen 2) and is left with 1 card.
#// P2's deck is deliberately topped with SOR_207 Crafty Smuggler, which ALSO costs 2 — so an
#// implementation resolving from the OWNER's seat would still find a match and still draw a card, and
#// only the per-seat identity assertions (P1HANDCARD, P2DECKTOPCARD, the two deck counts) separate the
#// two behaviours.

## GIVEN
CommonSetup: ggw/ggw/{myResources:0}
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_238:2
WithP1Deck: SOR_095
WithP1Deck: SOR_046
WithP2Deck: SOR_207
WithP2Deck: SOR_067

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:2
- P1>AnswerDecision:Draw

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P1DECKCOUNT:1
P2HANDCOUNT:0
P2DECKCOUNT:2
P2DECKTOPCARD:SOR_207
P2BASEDMG:1

---

# SimulateRequestBoundary_NumberThenRevealSurviveTwoBoundaries
#// SOR_238 C-3PO — this ability crosses TWO request boundaries in production: the number picker ends
#// one request and the reveal-and-draw picker ends another, so the chain number -> peek -> draw is
#// reassembled twice in a fresh process with every non-serialized global empty. Mirrors
#// WhenPlayed_MatchDraw with a boundary inserted before EACH answer.
#// The peeked card must stay IN the deck across both (only the draw may remove it), which is why the
#// deck count is asserted alongside the drawn card's identity: a peek held in memory reads here as a
#// card vanishing rather than as a wrong card being drawn.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_238
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:2
- P1>SimulateRequestBoundary
- P1>AnswerDecision:Draw

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P1DECKCOUNT:2
P1DISCARDCOUNT:0
P1GROUNDARENACOUNT:1
