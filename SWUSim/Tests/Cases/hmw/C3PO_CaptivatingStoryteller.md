# WhenPlayed_BuffsAnEwokAndARebel
#// HMW_255 C-3PO (2/3, Heroism, cost 2, Rebel Droid) — "When Played: You may give an Ewok unit +2/+2 for
#// this phase. You may give a Rebel unit +2/+2 for this phase." Two independent may-choices: buff HMW_257
#// (Ewok, 2/5 → 4/7) then SOR_095 (Rebel, 3/3 → 5/5).

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_255
WithP1GroundArena: [HMW_257:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_257
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:7
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:5

---

# WhenPlayed_DeclineEwok_StillOffersRebel
#// Independence: declining the first (Ewok) may must still offer the second (Rebel) may.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_255
WithP1GroundArena: [HMW_257:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:1:POWER:5

---

# WhenPlayed_BuffsAreThisPhaseOnly
#// "for this phase" — the +2/+2 expires at the next regroup. Buff HMW_257, pass to regroup, confirm it is
#// back to its printed 2/5.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_255
WithP1GroundArena: HMW_257:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_257
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:5

---

# WhenPlayed_DeclineRebel_EwokStillBuffed
#// The MIRROR of WhenPlayed_DeclineEwok_StillOffersRebel. The two "you may"s are independent in BOTH
#// directions, and only one of the two orders was covered — a handler that chained the second clause off
#// the first's acceptance passes the existing pair and fails here.
## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_255
WithP1GroundArena: [HMW_257:1:0 SOR_095:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_257
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:7
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:3

---

# WhenPlayed_DeclineBOTH_NothingIsBuffed
#// Both clauses refused. Cheap, and it is the only section that proves neither buff leaks through on a
#// double decline — the single-decline sections each still apply one buff, so a handler that applied a
#// buff on the refusal path is invisible to them.
## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_255
WithP1GroundArena: [HMW_257:1:0 SOR_095:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:3

---

# WhenPlayed_NoEwokOnTheBoard_TheRebelClauseStillResolves
#// NO-VALID-TARGET on the FIRST clause only. "You may give an EWOK unit +2/+2" has nothing to point at,
#// so it must resolve to nothing WITHOUT swallowing the independent Rebel clause behind it — the classic
#// shape where an early empty pool aborts the whole ability.
#// C-3PO is himself a Rebel, so he is a legal target for the second clause once he is in play.
## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_255
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5

---

# RequestBoundary_TheSecondMaySurvivesTheFirstAnswer
#// The request-boundary cell. C-3PO queues TWO independent may-choices from one When Played, so the
#// second offer has to survive a fresh request after the first is answered — anything the handler holds
#// in memory between them is empty in the next process, and the usual symptom is the second clause
#// simply never appearing.
#// Same GIVEN and EXPECT as WhenPlayed_BuffsAnEwokAndARebel, with one boundary between the two answers.
## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_255
WithP1GroundArena: [HMW_257:1:0 SOR_095:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_257
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:7
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:5
