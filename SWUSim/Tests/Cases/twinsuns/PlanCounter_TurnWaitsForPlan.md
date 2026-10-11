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

# PlanCounter_DecliningTheChoiceStillPasses
#// The choice is a "may" prompt; declining it (the Pass button) must still end the plan taker's turn — the
#// deferred pass is exempt from the sticky-PASS skip (dontSkipOnPass), or the seat would keep the turn.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_251
WithP1Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>TakeCounter:plan
- P1>AnswerDecision:PASS
## EXPECT
TURNPLAYER:2
P1HANDCOUNT:2
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
