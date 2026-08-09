# OppDeclines
#// TS26_33 Kouhun Assassination — if the opponent declines to discard ("may"), the rider does not happen:
#// no debuff, the opponent keeps their card and unit.
## GIVEN
CommonSetup: byk/rrk/{myResources:3;handCardIds:TS26_33;theirhandCardIds:SOR_095}
WithP2GroundArena: SEC_080:1:0
WithActivePlayer: 1
## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:-
## EXPECT
P2HANDCOUNT:1
P2GROUNDARENACOUNT:1

---

# OppDiscardsThenDebuff
#// TS26_33 Kouhun Assassination (Event, cost 3) — An opponent may discard a card from their hand. If they
#// do, give a non-Vehicle unit -8/-8 for this phase. The opponent discards their card, then the caster
#// debuffs a non-Vehicle enemy unit (SEC_080, 3/3) to death.
## GIVEN
CommonSetup: byk/rrk/{myResources:3;handCardIds:TS26_33;theirhandCardIds:SOR_095}
WithP2GroundArena: SEC_080:1:0
WithActivePlayer: 1
## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2HANDCOUNT:0
P2GROUNDARENACOUNT:0

---

# AnOpponentWithAnEmptyHandCannotPayAndNothingHappens
#// TS26_33 Kouhun Assassination — "An opponent MAY DISCARD a card from their hand. IF THEY DO, give a
#// non-Vehicle unit -8/-8." With P2 holding nothing there is no discard to make, so no offer is raised and
#// SEC_080 keeps its 3 power.

## GIVEN
CommonSetup: byk/rrk/{myResources:3;handCardIds:TS26_33}
SkipPreGame: true
WithActivePlayer: 1
WithP2GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:3
P1NODECISION

---

# WithOnlyVEHICLESInPlayThereIsNothingToDebuff
#// TS26_33 Kouhun Assassination — the payoff needs a NON-VEHICLE unit. P2 holds a card but the only unit
#// on the board is the Vehicle ASH_261, so the exchange never starts: their hand is untouched and the Pod
#// keeps its 3 power.

## GIVEN
CommonSetup: byk/rrk/{myResources:3;handCardIds:TS26_33;theirhandCardIds:SOR_095}
SkipPreGame: true
WithActivePlayer: 1
WithP2GroundArena: ASH_261:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P2HANDCOUNT:1
P2GROUNDARENAUNIT:0:POWER:3
