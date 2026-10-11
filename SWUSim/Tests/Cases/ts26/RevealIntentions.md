# EmptyOpponentHand
#// TS26_80 Reveal Intentions — edge: the opponent's hand is empty, so the caster (P1) discards nothing
#// from it (no decision), but P2 still discards a card from P1's hand, and BOTH players still draw a card.
## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
WithActivePlayer: 1
WithP1Hand: TS26_80
WithP1Hand: SOR_095
WithP1Hand: SOR_046
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:theirHand-0
## EXPECT
P2DISCARDCOUNT:0
P1DISCARDCOUNT:2
P1DECKCOUNT:1
P2DECKCOUNT:1
P1HANDCOUNT:2
P2HANDCOUNT:1

---

# MutualDiscardThenDraw
#// TS26_80 Reveal Intentions (Event, cost 1, Cunning, Gambit) — "Each player reveals their hand. In
#// player order, each player discards a card from the hand of the player to their right. Then, each player
#// draws a card." In 2P: P1 discards a card from P2's hand (its choice), P2 discards a card from P1's hand,
#// then both draw. P1 plays the event, so its own discard pile also holds the spent event (→ count 2).
## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
WithActivePlayer: 1
WithP1Hand: TS26_80
WithP1Hand: SOR_095
WithP1Hand: SOR_046
WithP2Hand: SOR_095
WithP2Hand: SOR_046
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0
- P2>AnswerDecision:theirHand-0
## EXPECT
P2DISCARDCOUNT:1
P1DISCARDCOUNT:2
P1DECKCOUNT:1
P2DECKCOUNT:1
P1HANDCOUNT:2
P2HANDCOUNT:2

---

# TheFinalDrawStillHappensWithEMPTYDecks
#// TS26_80 Reveal Intentions — "Then, each player draws a card" is unconditional. With both decks empty
#// the discards still resolve and both players still attempt the draw, each eating the empty-deck penalty
#// of 3 base damage. Both hands end empty and both bases sit on 3.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: TS26_80
WithP1Hand: SOR_095
WithP2Hand: SOR_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0
- P2>AnswerDecision:theirHand-0

## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:0
P1BASEDMG:3
P2BASEDMG:3

---

# TwinSuns_ThreeSeats_TheWalkReachesEVERYSeat
#// THREE seats — the shape bug #1086 was reported against (game 1310526), and the one the file was
#// missing: it had 2-seat sections and a 4-seat section, and an odd seat count is exactly where a
#// "caster + one opponent" walk looks healthy for the caster and silently drops everyone else.
#// The reported symptom was that the caster's discard resolved and then nothing else happened — no
#// second or third discard, and no closing draw for anybody.
#//
#// ⚠ The CLOSING DRAW is the assertion that catches a dropped seat. The discard piles alone can't:
#// if the walk stops after seat 1, seat 2's pile is empty, which is ALSO what "seat 2 was asked and
#// picked nothing" would look like. The draws only run once every seat has been asked, so a deck of 3
#// dropping to 2 on EVERY seat is what proves the walk ran to completion.
#//   P1 takes from P2 · P2 takes from P3 · P3 wraps and takes from P1 · then all three draw.
#// Each seat holds a DISTINCT card so its discard pile names who took from it.

## GIVEN
CommonSetup: yyk/rrk/{myResources:4}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024:0
WithP1Hand: [TS26_80 SOR_095 SOR_128]
WithP2Hand: [SOR_046 SOR_046]
WithP3Hand: [SEC_080 SEC_080]
WithP1Deck: [SOR_237 SOR_237 SOR_237]
WithP2Deck: [SOR_237 SOR_237 SOR_237]
WithP3Deck: [SOR_237 SOR_237 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p2Hand-0
- P2>AnswerDecision:p3Hand-0
- P3>AnswerDecision:p1Hand-0

## EXPECT
SEATCOUNT:3
P2DISCARDUNIT:0:CARDID:SOR_046
P3DISCARDUNIT:0:CARDID:SEC_080
P1DECKCOUNT:2
P2DECKCOUNT:2
P3DECKCOUNT:2

---

# TwinSuns_ThreeSeats_TheSECONDSeatIsReallyOffered
#// The half of the report that a completed walk cannot show: that seat 2 is HANDED the decision rather
#// than having it resolved for it. The run stops right after the caster's pick and asserts seat 2 is
#// holding a real, correctly-worded choice over seat 3's hand — which is the state a live player at
#// seat 2 said they never got to act on.
#// ⚠ The decision is deliberately left UNANSWERED so it is still there to read.

## GIVEN
CommonSetup: yyk/rrk/{myResources:4}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024:0
WithP1Hand: [TS26_80 SOR_095 SOR_128]
WithP2Hand: [SOR_046 SOR_046]
WithP3Hand: [SEC_080 SEC_080]
WithP1Deck: [SOR_237 SOR_237 SOR_237]
WithP2Deck: [SOR_237 SOR_237 SOR_237]
WithP3Deck: [SOR_237 SOR_237 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p2Hand-0

## EXPECT
P2HASDECISION
P2DECISIONTOOLTIP:Discard_a_card_from_the_hand_of_the_player_to_your_right
P1NODECISION

---

# TwinSuns_EachSeatDiscardsFromTheSeatToITSRight
#// ⚠ THE ADJACENCY CELL — added 2026-08-21 under the USER RULING that **RIGHT is the increment along
#// SeatOrder** (so seat 1's right neighbour is seat 2, and seat 4's wraps to seat 1). Before this the
#// card resolved OtherPlayer(): unambiguous at two seats, undefined at four, and it never even asked
#// seats 3 and 4.
#// Every seat holds a DIFFERENT card, so the discard pile of each seat identifies exactly WHO took from
#// it — the only assertion that can catch a wrong direction. If "right" were the decrement instead, all
#// four discards would land on the opposite neighbours and every line below would fail.
#//   P1 takes from P2 · P2 takes from P3 · P3 takes from P4 · P4 wraps and takes from P1
#// Then every seat draws one.
#// ⚠ Each seat holds 2 cards so the pick is a real choice, and the decks are stocked so the closing
#//   draw does not deck anyone out.

## GIVEN
CommonSetup: yyk/rrk/{myResources:4}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: [TS26_80 SOR_095 SOR_095]
WithP2Hand: [SOR_046 SOR_046]
WithP3Hand: [SEC_080 SEC_080]
WithP4Hand: [SOR_128 SOR_128]
WithP1Deck: [SOR_237 SOR_237 SOR_237]
WithP2Deck: [SOR_237 SOR_237 SOR_237]
WithP3Deck: [SOR_237 SOR_237 SOR_237]
WithP4Deck: [SOR_237 SOR_237 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p2Hand-0
- P2>AnswerDecision:p3Hand-0
- P3>AnswerDecision:p4Hand-0
- P4>AnswerDecision:p1Hand-0

## EXPECT
SEATCOUNT:4
P2DISCARDUNIT:0:CARDID:SOR_046
P3DISCARDUNIT:0:CARDID:SEC_080
P4DISCARDUNIT:0:CARDID:SOR_128
P1DISCARDCOUNT:2

---

# TeamSuns_EVERYPlayerDiscards_NotEveryOpponent
#// ⚠ THE WORD IS "EACH PLAYER", NOT "EACH OPPONENT" (owner, 2026-09-26). Team Suns changes nothing about
#// who participates: all four seats reveal, all four discard, all four draw. A teammate is not exempt.
#// This is the guard against someone "helpfully" narrowing the walk to OpponentsOf() — which would look
#// right in Premier and in free-for-all Twin Suns, and silently skip half the table in a team game.
#//
#// Teams are seat PARITY (SWUTeamOf: 1,3 = Red · 2,4 = Blue), so with the default 1234 seating every
#// seat's right neighbour is an OPPONENT. That makes this section the "teams changes nothing" control
#// and leaves the teammate-to-your-right case to the section below — which is the one that can actually
#// break. Every seat holds a DIFFERENT card, so each discard pile names who took from it.

## GIVEN
CommonSetup: yyk/rrk/{myResources:4}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: [TS26_80 SOR_095 SOR_095]
WithP2Hand: [SOR_046 SOR_046]
WithP3Hand: [SEC_080 SEC_080]
WithP4Hand: [SOR_128 SOR_128]
WithP1Deck: [SOR_237 SOR_237 SOR_237]
WithP2Deck: [SOR_237 SOR_237 SOR_237]
WithP3Deck: [SOR_237 SOR_237 SOR_237]
WithP4Deck: [SOR_237 SOR_237 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p2Hand-0
- P2>AnswerDecision:p3Hand-0
- P3>AnswerDecision:p4Hand-0
- P4>AnswerDecision:p1Hand-0

## EXPECT
SEATCOUNT:4
P2DISCARDUNIT:0:CARDID:SOR_046
P3DISCARDUNIT:0:CARDID:SEC_080
P4DISCARDUNIT:0:CARDID:SOR_128
P1DISCARDCOUNT:2
#// The closing draw only runs once EVERY seat has been asked, so 3 -> 2 on all four is what proves the
#// walk ran to completion rather than stopping at the first teammate.
P1DECKCOUNT:2
P2DECKCOUNT:2
P3DECKCOUNT:2
P4DECKCOUNT:2

---

# TeamSuns_AfterAnElimination_YourRightNeighbourIsYourTEAMMATE
#// ⚠⚠ THE CASE THAT CAN ACTUALLY BREAK, and it is REACHABLE IN A NORMAL GAME. The lobby seats Team Suns
#// Red/Blue/Red/Blue, so while all four are alive every seat's right neighbour is an opponent and the
#// teammate branch never runs. **Eliminate one seat and it does**: with SeatOrder 1234 and seat 2 gone,
#// "right" is the next LIVE seat, so seat 1's right neighbour is seat 3 — its own partner.
#// (SWUSeatToTheRight is NextLiveSeat, which correctly does NOT skip a teammate the way OpponentsOf does.)
#//
#// Why it breaks: the pool is built from `ZoneSearch("theirHand")`, and in a team game `their<Zone>` is
#// the OPPONENT fan-out — it EXCLUDES a teammate. The pool comes back EMPTY, the walk `continue`s, and
#// that seat is SILENTLY SKIPPED. Per the owner's reading (2026-09-26) the card says "each PLAYER", so
#// P1 must still discard from its teammate P3.
#// ⚠ The closing draw is what separates "skipped" from "stalled": all three live seats still draw here,
#// so the tell is a MISSING DISCARD, not a frozen game.
#//   Ask order 1, 3, 4 · P1 takes from teammate P3 · P3 takes from P4 · P4 wraps to P1.

## GIVEN
CommonSetup: yyk/rrk/{myResources:4}
SkipPreGame: true
WithTeams: true
WithSeatOrder: 1234
WithLiveSeats: 134
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: [TS26_80 SOR_095 SOR_095]
WithP3Hand: [SEC_080 SEC_080]
WithP4Hand: [SOR_128 SOR_128]
WithP1Deck: [SOR_237 SOR_237 SOR_237]
WithP3Deck: [SOR_237 SOR_237 SOR_237]
WithP4Deck: [SOR_237 SOR_237 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3Hand-0
- P3>AnswerDecision:p4Hand-0
- P4>AnswerDecision:p1Hand-0

## EXPECT
SEATCOUNT:4
#// THE CLAIM: the caster took from its TEAMMATE.
P3DISCARDUNIT:0:CARDID:SEC_080
P4DISCARDUNIT:0:CARDID:SOR_128
P1DISCARDCOUNT:2
P1DECKCOUNT:2
P3DECKCOUNT:2
P4DECKCOUNT:2

---

# TwinSuns_FourSeats_ADEFEATEDSeatIsSkippedByTheWalk
#// FREE-FOR-ALL (no teams), four seated, SEAT 3 DEFEATED — the remaining #1086 gap. "Right" is the next
#// LIVE seat, so a dead seat is stepped OVER rather than ending the walk:
#//   P1 plays it · P1 takes from P2 · P2 takes from P4 (skipping dead P3) · P4 wraps and takes from P1.
#//
#// ⚠ THE DEAD SEAT IS THE ASSERTION, and it is why P3 is given a real hand and a real deck. A fixture
#// that left P3 empty could not tell "correctly skipped" from "touched, but there was nothing to take" —
#// the same trap as leaving an opponent's discard pile empty. P3 must end with its hand AND deck
#// untouched: it neither discards, nor is discarded from, nor draws the closing card.
#// ⚠ Deck 3 -> 2 on the three LIVE seats is what proves the walk ran to completion; the closing draw only
#// happens after every live seat has been asked.

## GIVEN
CommonSetup: yyk/rrk/{myResources:4}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 124
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: [TS26_80 SOR_095 SOR_095]
WithP2Hand: [SOR_046 SOR_046]
WithP3Hand: [SEC_080 SEC_080]
WithP4Hand: [SOR_128 SOR_128]
WithP1Deck: [SOR_237 SOR_237 SOR_237]
WithP2Deck: [SOR_237 SOR_237 SOR_237]
WithP3Deck: [SOR_237 SOR_237 SOR_237]
WithP4Deck: [SOR_237 SOR_237 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p2Hand-0
- P2>AnswerDecision:p4Hand-0
- P4>AnswerDecision:p1Hand-0

## EXPECT
SEATCOUNT:4
P2DISCARDUNIT:0:CARDID:SOR_046
P4DISCARDUNIT:0:CARDID:SOR_128
P1DISCARDCOUNT:2
#// The defeated seat is untouched on every axis.
P3DISCARDCOUNT:0
P3HANDCOUNT:2
P3DECKCOUNT:3
#// The three live seats each drew the closing card.
P1DECKCOUNT:2
P2DECKCOUNT:2
P4DECKCOUNT:2
