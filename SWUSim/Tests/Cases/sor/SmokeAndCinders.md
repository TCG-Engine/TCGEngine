# BothPlayersDiscardToTwo
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
