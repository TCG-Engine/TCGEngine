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
