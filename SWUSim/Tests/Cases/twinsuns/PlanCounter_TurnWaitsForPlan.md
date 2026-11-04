# PlanCounter_TurnStaysWhilePlanResolves
#// Owner UX report (Twin Suns, 2026-09-22): "when someone is in the middle of their Plan action, if someone tries to
#// take an action they get the 'can't do anything' warning" — P3 takes Plan, P4 clicks Blast, and is refused with
#// "Cannot take a counter while decisions are pending." SWUTakeCounter queued Plan's "put a card on the bottom" choice
#// and then passed at once, so the NEXT seat became the turn player while the plan taker was still choosing. The pass
#// now waits behind the choice (SWU_PLAN_PASS): the turn stays with the plan taker until the card goes to the bottom.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_251
WithP1Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>TakeCounter:plan
## EXPECT
PLANCOUNTER:P1
TURNPLAYER:1
P1DECISIONTOOLTIP:Put_a_card_on_the_bottom_of_your_deck

---

# PlanCounter_TurnMovesOnceThePlanResolves
#// Choosing the card finishes Plan, and only THEN is the turn passed — exactly one seat on.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_251
WithP1Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>TakeCounter:plan
- P1>AnswerDecision:myHand-0
## EXPECT
PLANCOUNTER:P1
TURNPLAYER:2
P1HANDCOUNT:1
P1DECKCOUNT:3
P1NODECISION
PHASE:MAIN

---

# PlanCounter_TheBottomCardIsMandatory_NoPass
#// CR (Twin Suns counters): "When a player takes the plan counter, they draw 1 card from their deck, then place 1 card from
#// their hand on the bottom of their deck." The bottom-a-card is NOT optional (owner report 2026-11-04: "the plan action
#// allows you to pass on putting a card at the bottom of your deck. this is not allowed"). This section used to assert
#// the opposite — a PASS accepted and the turn moved on with 2 cards in hand. The pick is now a MANDATORY MZCHOOSE, which
#// has no Pass button and on which SWUValidateDecisionAnswer refuses a PASS (the harness throws on one — Vanquish's
#// decline=N/A convention). Read the OFFER, left pending: its type, its pool (both cards), and the turn held meanwhile.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_251
WithP1Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>TakeCounter:plan
## EXPECT
P1DECISIONTYPE:MZCHOOSE
P1SELECTABLEEXACT:myHand-0&myHand-1
TURNPLAYER:1
P1HANDCOUNT:2

---

# PlanCounter_OnlyTheDrawnCardInHand_ItGoesToTheBottom
#// The boundary: an empty hand draws 1, and that card is the only legal pick — a mandatory choice with one option is no
#// choice, so it goes to the bottom at once and the turn moves on. Before, the lone card was still a "may" and could be kept.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>TakeCounter:plan
## EXPECT
PLANCOUNTER:P1
TURNPLAYER:2
P1HANDCOUNT:0
P1DECKCOUNT:3
P1NODECISION

---

# PlanCounter_NextSeatCanTakeBlastAfterPlan
#// The reported sequence, end to end: after the plan taker finishes, the next seat takes Blast without a refusal.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_251
WithP1Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>TakeCounter:plan
- P1>AnswerDecision:myHand-0
- P2>TakeCounter:blast
## EXPECT
BLASTCOUNTER:P2
TURNPLAYER:3
