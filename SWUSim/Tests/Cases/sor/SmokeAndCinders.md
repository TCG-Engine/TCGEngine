# BothPlayersDiscardToTwo
#// COVERAGE: offer=KeepPick_Offer_EachPlayerPicksFromTheirOWNHand (both keep-picks left pending; each
#//           seat's candidate set is exactly that seat's own hand) · decline=N/A (mandatory — "each
#//           player discards", no "you may"; a player under the limit is simply never asked, see
#//           ExactlyTwoCards_NoDecisionNoDiscard) · control=N/A (a one-shot discard out of hand zones;
#//           no unit and no persistent effect exists for a controller change to follow) ·
#//           boundary=ExactlyTwoCards_NoDecisionNoDiscard (a hand of exactly 2 discards nothing) vs
#//           BothPlayersDiscardToTwo (3 discards 1), with SmallHand_NoDiscard below the line at 1 ·
#//           reqboundary=N/A (both keep-picks are raised in the same drain and each is answered from
#//           its own seat's queue; nothing is read back after a decision)
#// SOR_174 Smoke and Cinders (event, cost 5) — "Each player discards all but 2 cards (of their choice)
#// from their hand." Both players hold 3 cards (after P1 plays Smoke and Cinders), each keeps 2 of their
#// choice and discards the third. Aggression off-aspect for SOR_009 (Command) → WithP1Resources:7.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_174
WithP1Hand: SOR_095
WithP1Hand: SOR_095
WithP1Hand: SOR_095
WithP1Resources: 7
WithP2Hand: SOR_095
WithP2Hand: SOR_095
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1
- P2>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1HANDCOUNT:2
P2HANDCOUNT:2
P1DISCARDCOUNT:2
P2DISCARDCOUNT:1

---

# SmallHand_NoDiscard
#// SOR_174 Smoke and Cinders — a player holding 2 or fewer cards discards nothing (and gets no
#// decision). P1 (3 cards after playing) keeps 2/discards 1; P2 (1 card) keeps it, no decision.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_174
WithP1Hand: SOR_095
WithP1Hand: SOR_095
WithP1Hand: SOR_095
WithP1Resources: 7
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1HANDCOUNT:2
P2HANDCOUNT:1
P2DISCARDCOUNT:0
P1DISCARDCOUNT:2

---

# TwinSuns_EVERYSeatDiscardsToTwo
#// ⚠ ADDED 2026-08-21 while implementing its 3-card sibling HMW_161 Raze to Ruin, which surfaced this
#// as a live bug rather than a hypothetical: SOR_174's When Played resolved `OtherPlayer($player)` and
#// the caster, so in a four-seat Twin Suns game seats 3 and 4 kept their FULL hands while seats 1 and 2
#// were stripped to two. Fixed to loop OpponentsOf() (live seats only) + the caster last.
#// ⚠ A 2-player version of this section CANNOT fail — with one opponent the old code and the new code
#//   are the same two calls. The seat count IS the test.
#// P1 (caster) and P2 hold 4 each, P3 holds 3, P4 holds none: everyone lands at or below 2, and P4's
#// empty hand raises no decision.

## GIVEN
CommonSetup: rrk/bbw/{myResources:7}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: [SOR_174 SOR_095 SOR_046 SEC_080 SOR_128]
WithP2Hand: [SOR_095 SOR_046 SEC_080 SOR_128]
WithP3Hand: [SOR_095 SOR_046 SEC_080]
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1
- P2>AnswerDecision:myHand-0&myHand-1
- P3>AnswerDecision:myHand-0&myHand-1

## EXPECT
SEATCOUNT:4
P1HANDCOUNT:2
P2HANDCOUNT:2
P3HANDCOUNT:2
P4HANDCOUNT:0
P2DISCARDCOUNT:2
P3DISCARDCOUNT:1
P4NODECISION

---

# ExactlyTwoCards_NoDecisionNoDiscard
#// SOR_174 Smoke and Cinders — "discards ALL BUT 2 cards" at exactly the threshold. SmallHand_NoDiscard
#// covers a hand of ONE, which is below the line and would also be spared by an off-by-one check that
#// fired at "more than 1". Here P2 holds exactly 2: nothing is over the limit, so P2 is asked nothing
#// and discards nothing, while P1 (3 in hand after the event leaves it) still discards its one excess
#// card. The pair with BothPlayersDiscardToTwo (3 → discard 1) brackets the boundary from both sides.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_174
WithP1Hand: SOR_095
WithP1Hand: SOR_095
WithP1Hand: SOR_095
WithP1Resources: 7
WithP2Hand: SOR_095
WithP2Hand: SOR_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1HANDCOUNT:2
P1DISCARDCOUNT:2
P2HANDCOUNT:2
P2DISCARDCOUNT:0
P2NODECISION

---

# KeepPick_Offer_EachPlayerPicksFromTheirOWNHand
#// SOR_174 Smoke and Cinders — "(OF THEIR CHOICE)". Every existing section answers both keep-picks in
#// sequence, which proves the branch but never the pools: a caster who picked the opponent's keeps, or
#// a pool built from the wrong hand, would produce the same counts. Here BOTH decisions are left
#// PENDING and read directly. Each is raised against its own seat and its candidates are exactly that
#// seat's own hand, in that seat's own frame — P1 three cards (the event has already left hand), P2
#// four. Neither player is offered a single card belonging to the other.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_174
WithP1Hand: SOR_095
WithP1Hand: SOR_095
WithP1Hand: SOR_095
WithP1Resources: 7
WithP2Hand: SOR_095
WithP2Hand: SOR_046
WithP2Hand: SOR_237
WithP2Hand: SEC_080

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P2HASDECISION
P1DECISIONTOOLTIP:Keep_2_cards_-_discard_the_rest
P2DECISIONTOOLTIP:Keep_2_cards_-_discard_the_rest
P1SELECTABLEEXACT:myHand-0&myHand-1&myHand-2
P2SELECTABLEEXACT:myHand-0&myHand-1&myHand-2&myHand-3
