# Decline_NoDiscard
#// SEC_230 Charged with Espionage — decline the disclose → opponent's hand untouched.

## GIVEN
CommonSetup: yyk/grw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SEC_230
WithP1Hand: SEC_220
WithP1Hand: SEC_233
WithP2Hand: SOR_095
WithP2Hand: SEC_074

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2HANDCOUNT:2
P2DISCARDCOUNT:0
P1NODECISION

---

# Disclose_DiscardUnitFromOppHand
#// SEC_230 Charged with Espionage (Event, Cunning) — "You may disclose CunningCunning → look at an
#//   opponent's hand and discard a UNIT from it." Opp hand: SOR_095 (unit) + SEC_074 (event). Disclose
#//   SEC_220 + SEC_233 (Cunning each) → the unit filter offers only SOR_095 → it's discarded; the event stays.

## GIVEN
CommonSetup: yyk/grw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SEC_230
WithP1Hand: SEC_220
WithP1Hand: SEC_233
WithP2Hand: SOR_095
WithP2Hand: SEC_074

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1
- P1>AnswerDecision:OK

## EXPECT
P2HANDCOUNT:1
P2DISCARDCOUNT:1
P1NODECISION

---

# Disclose_LooksAtTheHandEvenThoughTheDiscardAutoResolved
#// SEC_230 — the "look at an opponent's hand" clause, which is SEPARATE from the discard and owed to the
#// player whether or not the discard has a choice to offer.
#// Exactly ONE unit is in the opponent's hand, so the discard has a single legal target and auto-resolves
#// — no MZCHOOSE is raised, and an MZCHOOSE over `theirHand` is the ONLY thing that reveals a
#// Visibility=Self hand to the viewer. Without the explicit popup the player is told a unit was discarded
#// from a hand they were never shown.
#// The popup is deliberately left PENDING here. Its sibling above answers it with `AnswerDecision:OK`,
#// and an OK against no pending decision is silently absorbed by the harness — so that section passes
#// with or without the reveal and is NOT a guard for it. This one is: it asserts the popup exists.
#// (SWUOfferDiscard now shows the hand by DEFAULT for from=opp; this is the section that pins the
#// default, since four of its seven callers never passed the old opt-in flag.)

## GIVEN
CommonSetup: yyk/grw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SEC_230
WithP1Hand: SEC_220
WithP1Hand: SEC_233
WithP2Hand: SOR_095
WithP2Hand: SEC_074

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1DECISIONTOOLTIP:Opponent's_hand
P2DISCARDCOUNT:1
