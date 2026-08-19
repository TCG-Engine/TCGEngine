# EntersPlayReady
#// COVERAGE: offer=N/A (no target pool; the tax is a fixed pay/bounce option pair)
#//           · decline=Regroup_BounceToHand (declining the pay bounces her to hand)
#//           · boundary=RegroupTax_PayableWithCreditToken (the tax is a COST: a Credit may pay it,
#//           real resources untouched) + PlayedFromDiscard_EntersReady (discount + aspect-penalty
#//           math on the alternate play path) · control=RescuedFromCapture_EntersReady (re-entry via
#//           rescue, not a play, still applies enters-ready) · reqboundary=Regroup_PayToKeep (the
#//           regroup YESNO pends across the phase-crossing request boundary)
#// SOR_193 Millennium Falcon "Piece of Junk" — "This unit enters play ready."
#// Most units enter play exhausted; the Falcon enters READY. Played from hand for its cost (3),
#// it lands in the Space arena ready to attack immediately.
#// Han Solo (SOR_017, Cunning+Heroism) is the leader so the Falcon's aspects are fully paid for
#// (cost stays 3, no off-aspect penalty).

## GIVEN
CommonSetup: gyw/ggk
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_193
WithP1Resources: 3

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_193
P1SPACEARENAUNIT:0:READY
P1RESCOUNT:3
P1RESAVAILABLE:0

---

# Regroup_BounceToHand
#// SOR_193 Millennium Falcon — regroup trigger, choose to bounce:
#// "Either pay [1 resource] or return this unit to her owner's hand."
#// Falcon is on the board. During the Ready step the controller declines to pay (NO), so the
#// Falcon returns to its owner's hand. Resources are untouched.
#// Hand ends at 3: 0 starting + 2 drawn in the Draw step + the returned Falcon.
#//
#// NOTE (phase-crossing): both players must answer the Resource-step MZMAYCHOOSE (ResourcePass)
#// before the cycle reaches the Ready step where the Falcon trigger fires.

## GIVEN
CommonSetup: grw/ggk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_193:1:0
WithP1Resources: 2
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:NO

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:3
P1RESCOUNT:2
P1RESAVAILABLE:2

---

# Regroup_PayToKeep
#// SOR_193 Millennium Falcon — regroup trigger:
#// "When you ready cards during the regroup phase: Either pay [1 resource] or return this unit
#//  to her owner's hand."
#// Falcon is on the board. Both players pass → regroup. During the Ready step the Falcon trigger
#// asks the controller to pay 1 resource (YES) or bounce (NO). Paying keeps the Falcon and
#// exhausts 1 resource: 2 resources → 2 total, 1 ready / 1 exhausted.
#//
#// NOTE (phase-crossing): both players must answer the Resource-step MZMAYCHOOSE (ResourcePass)
#// before the cycle reaches the Ready step where the Falcon trigger fires.

## GIVEN
CommonSetup: grw/ggk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_193:1:0
WithP1Resources: 2
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_193
P1RESCOUNT:2
P1RESAVAILABLE:1

---

# RescuedFromCapture_EntersReady
#// SOR_193 Millennium Falcon — "enters play ready" also applies when the Falcon is RESCUED from
#// capture. P2's Take Captive (SHD_131) has the Cartel Spacer capture P1's Falcon (both space,
#// single candidates → auto-picks). P1's Vanquish then defeats the Spacer: the Falcon is rescued
#// and re-enters P1's space arena READY.

## GIVEN
SkipPreGame: true
CommonSetup: grw/ggk
WithActivePlayer: 2
WithP2Resources: 3
WithP1Resources: 9
WithP2Hand: SHD_131
WithP1Hand: SOR_078
WithP2SpaceArena: SOR_178:1:0
WithP1SpaceArena: SOR_193:0:0

## WHEN
- P2>PlayHand:0
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:2
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_193
P1SPACEARENAUNIT:0:READY

---

# PlayedFromDiscard_EntersReady
#// SOR_193 Millennium Falcon — "enters play ready" applies when played from the DISCARD pile too.
#// P1 plays SHD_094 Palpatine's Return (cost 6): the only unit in the discard is the Falcon,
#// played via the discard picker. Under ggk both Falcon aspects are uncovered (+4), so she costs
#// 3 + 4 − 6 = 1: the 7th resource pays it. She enters the space arena READY.

## GIVEN
CommonSetup: ggk/ggk/{myResources:7;discardCardIds:SOR_193}
P1OnlyActions: true
WithP1Hand: SHD_094

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_193
P1SPACEARENAUNIT:0:READY
P1DISCARDCOUNT:1
P1RESAVAILABLE:0

---

# RegroupTax_PayableWithCreditToken
#// SOR_193 Millennium Falcon — the regroup "pay [1 resource]" tax is a real COST, so a Credit
#// token may pay it (CR 3.13). P1 keeps the Falcon (YES) and pays with the seeded Credit: the
#// Falcon stays in play, the Credit is consumed, and BOTH real resources stay ready.

## GIVEN
CommonSetup: grw/ggk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_193:1:0
WithP1Resources: 2
WithP1Credits: 1
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:YES
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_193
P1CREDITCOUNT:0
P1RESCOUNT:2
P1RESAVAILABLE:2
