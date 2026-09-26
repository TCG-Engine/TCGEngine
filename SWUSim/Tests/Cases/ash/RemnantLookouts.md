# Decline_NoDiscardNoDraw
#// ASH_220 Remnant Lookouts — declining the optional discard leaves the opponent's hand untouched (no
#// discard, so they do not draw). P1 plays it, looks at P2's hand, and declines.
## GIVEN
CommonSetup: yyk/yyk/{myResources:3;handCardIds:ASH_220;theirHandCardIds:SOR_095}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P2DISCARDCOUNT:0
P2HANDCOUNT:1

---

# DiscardFromOppHand_TheyDraw
#// ASH_220 Remnant Lookouts (Ground, 3/3, cost 3) — When Played: look at an opponent's hand; you may
#// discard a card from it; if you do, they draw a card. P1 plays it, sees P2's one card (SOR_095) and
#// discards it; P2 then draws back to 1 card and has 1 card in its discard pile.
## GIVEN
CommonSetup: yyk/yyk/{myResources:3;handCardIds:ASH_220;theirHandCardIds:SOR_095}
WithP2Deck: [SOR_046]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0
## EXPECT
P2DISCARDCOUNT:1
P2HANDCOUNT:1

---

# TwinSuns_PickAnOpponent_ThenTheDiscardOfferAppears
#// ⚠ REPORTED 2026-09-25 (game 1310334): "play Remnant Lookouts, choose P2 — nothing happens."
#//
#// ⚠ THE THREE SECTIONS AROUND THIS ONE CANNOT SEE IT. They are all 2-player (yyk/yyk), and at two seats
#// SWUQueueChooseOpponent auto-resolves the lone opponent to an invisible PASSPARAMETER — so the
#// OPTIONCHOOSE branch, and everything downstream of ANSWERING it, is unreachable in Premier. This is the
#// first section that actually picks a seat.
#//
#// With two opponents both holding cards, P1 is asked WHICH hand to look at. Answering "P2" must then
#// surface the optional discard over P2's hand — at >2 seats those mzIDs are "p2Hand-N", not "theirHand-N"
#// (SWULookAtOpponentHand switches prefix on SeatCountForGame; the hidden-zone reveal refuses to guess a
#// seat, so a "theirHand" Param would render the row as card backs).
#// Left PENDING deliberately: the bug is that no decision arrives at all, so the assertion is that one did.
## GIVEN
CommonSetup: yyk/yyk/{myResources:3;handCardIds:ASH_220;theirHandCardIds:SOR_095}
WithSeatOrder: 1234
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Hand: SOR_046
WithP2Deck: [SOR_046]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P2
## EXPECT
#// ⚠ The tooltip is 'Choose_a_card', NOT the "Discard a card from the opponent's hand?" string the card
#// passes: SWUQueueMayChooseTarget takes a $yesTooltip AND a $chooseTooltip but only ever forwards the
#// SECOND to AddDecision, so the first argument is dead at every call site. Asserting the string the card
#// author wrote would pin a prompt the player never sees.
P1DECISIONTOOLTIP:Choose_a_card
P1SELECTABLEEXACT:p2Hand-0

---

# TwinSuns_ActorIsSeat3_PicksSeat2
#// The REPORTED configuration of game 1310334: the acting seat is THREE, not one. The sibling section
#// above dresses seat 1, which is the trap this project keeps hitting — a multi-seat fixture that only
#// ever acts from seats 1-2 cannot see a seat-3 bug. Here P3 plays the Lookouts and picks P2.
#// ⚠ At three live seats P3 has TWO opponents (P1 and P2), both holding cards, so the OPTIONCHOOSE really
#// renders rather than auto-resolving — and the answer must route to P2's hand specifically, not to
#// "the first opponent" (which is how the whole SWUChooseOpponent family used to behave).
## GIVEN
CommonSetup: yyk/yyk/{handCardIds:SOR_095;theirHandCardIds:SOR_095}
WithSeatOrder: 1234
WithLiveSeats: 123
WithGamePhase: ActionPhase
WithInitiativePlayer: 3
WithInitiativeClaimed: true
WithActivePlayer: 3
#// ⚠ CommonSetup builds seats 1-2 ONLY. A seat-3 ACTOR needs its base seeded explicitly or it cannot play
#// anything at all — and a card that never enters play looks EXACTLY like the reported bug. Its base also
#// carries no matching aspect, so ASH_220 costs 3+2; 3 resources silently buys nothing. Both mistakes were
#// made here first, and both produced a convincing false "reproduction".
WithP3Base: SOR_021:0
WithP3Resources: 9
WithP3Hand: ASH_220
WithP2Deck: [SOR_046]
## WHEN
- P3>PlayHand:0
- P3>AnswerDecision:P2
## EXPECT
P3DECISIONTOOLTIP:Choose_a_card
P3SELECTABLEEXACT:p2Hand-0

---

# EmptyOppHand_Skipped
#// ASH_220 Remnant Lookouts — if the opponent has no cards in hand, the look-and-discard ability is simply
#// skipped (no decision surfaces). The Lookouts still enter play and P1 has no pending choice.
## GIVEN
CommonSetup: yyk/yyk/{myResources:3;handCardIds:ASH_220}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_220
P1NODECISION
P2HANDCOUNT:0
