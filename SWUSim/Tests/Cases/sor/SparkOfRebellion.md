# DiscardChosen
#// SOR_200 Spark of Rebellion (Event, cost 2, Cunning/Heroism) — "Look at an opponent's hand and
#// discard a card from it." P1 plays Spark and sees P2's two-card hand; P1 chooses to discard the
#// first card (SOR_171, an event). P2 hand 2→1, P2 discard 0→1 (From HAND). The Spark event itself
#// goes to P1's discard.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_200
WithP2Hand: SOR_171
WithP2Hand: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0

## EXPECT
P2HANDCOUNT:1
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_171
P2DISCARDUNIT:0:FROM:HAND
P1DISCARDCOUNT:1

---

# SingleCardHand_StillShowsTheHand
#// SOR_200 Spark of Rebellion — "LOOK AT an opponent's hand and discard a card from it." Two clauses; the
#// look is not conditional on the discard being a choice.
#// With exactly ONE card in the opponent's hand the discard auto-resolves, so no MZCHOOSE over
#// `theirHand` is raised — and that MZCHOOSE is the only thing that reveals a Visibility=Self hand. The
#// player was shown nothing and simply told a card had gone. SWUOfferDiscard now presents the hand
#// explicitly in that case (default-on for from=opp since 2026-08-18); this section pins it by leaving
#// the popup pending.
#// The unfiltered callers were the easiest to miss: they only lose the hand on a 1-card hand, whereas a
#// FILTERED one (Jam Communications, Tip the Scale, Charged with Espionage) loses it on any board with
#// ≤1 matching card. Same bug, different frequency.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_200
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECISIONTOOLTIP:Opponent's_hand
P2HANDCOUNT:0
P2DISCARDCOUNT:1
