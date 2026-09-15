# WhenPlayed_TheChosenUnitPlaysForOneLess
#// HMW_122 Boga, Loyal Varactyl — Unit (Ground) 6/6, cost 6, [Command][Heroism], Creature, unique.
#// "When Played/When Defeated: Choose a non-Vehicle unit in your discard pile not named Boga. For this
#//  phase, you may play that unit from your discard pile. It costs [1 resource] less."
#//
#// COVERAGE: offer=Offer_NonVehicleUnitsNotNamedBoga + WhenDefeated_BogaItselfIsNotOffered
#//           decline=DecliningThePlay_ThePermissionLapsesNextPhase (the PLAY is the "may"; the CHOOSE is
#//                   mandatory — typed SWUQueueChooseTarget, no pass — so it has no decline branch)
#//           novalidtarget=NoLegalUnit_NoPrompt_BogaStillEnters
#//           boundary=this section (1 left → plays) vs OneShort_ThePermissionWaits (0 left → cannot):
#//                    the discount is exactly 1 · quantity=AspectPenaltyStillApplies (2 + 2 − 1 = 3)
#//           scope=OnlyTheChosenCardGetsThePermission
#//           dispatch=When Played (this) + When Defeated (WhenDefeated_ThenPlayIt)
#//           control=ControlChange_TheControllersDiscardPile (a stolen Boga's "your" is the thief's pile)
#//           reqboundary=AcrossARequestBoundary (the pick names a discard mzID; the stamp lands after)
#//           modes=2P only — "your discard pile" names no other player: self-only in every format, so
#//                 TwinSuns/TeamSuns sections would run the identical path and could not fail.
#//
#// PREVIEW SET — no official ruling. Readings (from the CR + released analogues):
#//   • The permission is TWI_201 Aid from the Innocent's ("for this phase, you may play … it costs 2
#//     less") at 1: an at-cost discard modifier, cleared at regroup start. Stamped 'TPP1U' — 'U' =
#//     UNIT-ONLY: "play that UNIT", and a Pilot played by a "play a unit" ability may only be played as a
#//     unit (official Piloting ruling, 03/06/2025) — Pilot_PlayedAsAUnitOnly.
#//   • It is a real PLAY at cost, so every other part of Determine Cost applies: the aspect penalty, other
#//     reducers (GnkPowerDroid_TheDiscountsStack), Exploit (Exploit_TheDiscountRidesTheExploitPicker) and
#//     Credits (Credits_PayTheDiscountedCost).
#//   • A card already carrying a BETTER permission keeps it (KeepsABetterPermission_CobbVanthsFreePlay).
#//
#// This section: 7 resources; Boga (6) leaves 1. The only legal pick auto-resolves, and SOR_095 (2) plays
#// from the discard for 1 — 0 left. At full cost it could not be played; if it were free, 1 would be left.

## GIVEN
CommonSetup: ggw/rrk/{myResources:7;myhandCardIds:HMW_122;discardCardIds:SOR_095}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>PlayFromDiscard:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_122
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1DISCARDCOUNT:0
P1RESAVAILABLE:0

---

# OneShort_ThePermissionWaits
#// The other half of the boundary pair: 6 resources, so Boga leaves 0 and SOR_095 still costs 1. The play
#// is refused, and the card sits in the discard with its permission intact (and does not glow).

## GIVEN
CommonSetup: ggw/rrk/{myResources:6;myhandCardIds:HMW_122;discardCardIds:SOR_095}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>PlayFromDiscard:0

## EXPECT
P1GROUNDARENACOUNT:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P1DISCARDUNIT:0:MODIFIER:TPP1U
P1DISCARDPLAYABLENOT:0

---

# Offer_NonVehicleUnitsNotNamedBoga
#// The pool, left pending:
#//   myDiscard-0  SOR_095 Battlefield Marine, a unit          → in
#//   myDiscard-1  SOR_237 Alliance X-Wing, a VEHICLE unit      → out ("non-Vehicle")
#//   myDiscard-2  HMW_122, another Boga                        → out ("not named Boga")
#//   myDiscard-3  SOR_199 Bamboozle, an EVENT                  → out (not a unit)
#//   myDiscard-4  SEC_080 Imperial Dark Trooper, a unit        → in

## GIVEN
CommonSetup: ggw/rrk/{myResources:6;myhandCardIds:HMW_122;discardCardIds:SOR_095,SOR_237,HMW_122,SOR_199,SEC_080}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_a_non-Vehicle_unit_in_your_discard_pile_not_named_Boga
P1SELECTABLEEXACT:myDiscard-0&myDiscard-4

---

# OnlyTheChosenCardGetsThePermission
#// Two legal units; P1 picks SEC_080. Only that entry is stamped — SOR_095 has no permission, and trying
#// to play it from the discard does nothing (8 resources, so cost is not what stops it).

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;myhandCardIds:HMW_122;discardCardIds:SOR_095,SEC_080}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-1
- P1>PlayFromDiscard:0

## EXPECT
P1DISCARDCOUNT:2
P1DISCARDUNIT:0:MODIFIER:
P1DISCARDUNIT:1:MODIFIER:TPP1U
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:2

---

# NoLegalUnit_NoPrompt_BogaStillEnters
#// Nothing legal — a Vehicle, another Boga and an event: no prompt, nothing stamped, Boga is in play.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6;myhandCardIds:HMW_122;discardCardIds:SOR_237,HMW_122,SOR_199}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:CARDID:HMW_122
P1DISCARDUNIT:0:MODIFIER:
P1DISCARDUNIT:1:MODIFIER:

---

# DecliningThePlay_ThePermissionLapsesNextPhase
#// "For THIS PHASE". P1 does not play the chosen SOR_095; after the regroup (7 fresh resources — plenty
#// for the full cost), playing it from the discard is refused and its permission is gone.

## GIVEN
CommonSetup: ggw/rrk/{myResources:7;myhandCardIds:HMW_122;discardCardIds:SOR_095}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayFromDiscard:0

## EXPECT
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:MODIFIER:
P1GROUNDARENACOUNT:1

---

# WhenDefeated_BogaItselfIsNotOffered
#// The When Defeated half. Boga (3 remaining) attacks SOR_046 (3/7) and dies; its own copy is now in the
#// discard beside SOR_095 and SEC_080 — and is not offered ("not named Boga").

## GIVEN
CommonSetup: ggw/rrk/{myResources:0;discardCardIds:SOR_095,SEC_080}
P1OnlyActions: true
WithP1GroundArena: HMW_122:1:3
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDUNIT:2:CARDID:HMW_122
P1HASDECISION
P1SELECTABLEEXACT:myDiscard-0&myDiscard-1

---

# WhenDefeated_ThenPlayIt
#// …and the grant works from the When Defeated too: SOR_095 plays for 1 with the 1 resource P1 has.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1;discardCardIds:SOR_095,SEC_080}
P1OnlyActions: true
WithP1GroundArena: HMW_122:1:3
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myDiscard-0
- P1>PlayFromDiscard:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1RESAVAILABLE:0

---

# AspectPenaltyStillApplies
#// An off-aspect pick pays its aspect penalty: SEC_080 (Command/Villainy, 2) under a Command/Heroism
#// leader + Command base is missing Villainy, so 2 + 2 − 1 = 3. 9 resources: Boga 6, SEC_080 3, 0 left.

## GIVEN
CommonSetup: ggw/rrk/{myResources:9;myhandCardIds:HMW_122;discardCardIds:SEC_080}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>PlayFromDiscard:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1RESAVAILABLE:0

---

# GnkPowerDroid_TheDiscountsStack
#// It is a real play, so other reducers apply too. Boga takes all 6 resources; SEC_110 GNK Power Droid
#// then attacks ("the next unit you play this phase costs 1 less"); SOR_095 now costs 2 − 1 − 1 = 0.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6;myhandCardIds:HMW_122;discardCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: SEC_110:1:0

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE
- P1>PlayFromDiscard:0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:SOR_095
P1DISCARDCOUNT:0

---

# Exploit_TheDiscountRidesTheExploitPicker
#// TWI_117 Baktoid Spider Droid (Command, 8, Exploit 2). 9 resources: Boga 6, 3 left. From the discard:
#// 8 − 1 (Boga) − 4 (exploiting both Battlefield Marines) = 3 — 0 left.

## GIVEN
CommonSetup: ggw/rrk/{myResources:9;myhandCardIds:HMW_122;discardCardIds:TWI_117}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0
- P1>PlayFromDiscard:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_122
P1GROUNDARENAUNIT:1:CARDID:TWI_117
P1DISCARDCOUNT:2
P1RESAVAILABLE:0

---

# Exploit_OneShortEvenAtMaxExploit_NoPickerOpens
#// The Exploit boundary pair: 8 resources, so Boga leaves 2 and TWI_117 costs at best 8 − 1 − 4 = 3. The
#// play is refused up front — no Exploit picker opens (the pipeline raises it BEFORE the charge, so an
#// ungated play would ask which units to defeat and then abort) — and the permission waits.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;myhandCardIds:HMW_122;discardCardIds:TWI_117}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0
- P1>PlayFromDiscard:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:3
P1DISCARDUNIT:0:CARDID:TWI_117
P1DISCARDUNIT:0:MODIFIER:TPP1U
P1DISCARDPLAYABLENOT:0
P1RESAVAILABLE:2

---

# Glow_ExploitMakesItAffordable
#// The same board, before the play: 7 against 3 resources is affordable only through Exploit, and the
#// discard entry must glow (the client only offers "Play" on a glowing entry).

## GIVEN
CommonSetup: ggw/rrk/{myResources:9;myhandCardIds:HMW_122;discardCardIds:TWI_117}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDUNIT:0:MODIFIER:TPP1U
P1DISCARDPLAYABLE:0

---

# Credits_PayTheDiscountedCost
#// Boga takes all 6 resources (the Credit offer declined); the discard play raises the same Credit
#// picker a hand play does, and defeating the Credit pays SOR_095's 1.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6;myhandCardIds:HMW_122;discardCardIds:SOR_095}
P1OnlyActions: true
WithP1Credits: 1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>PlayFromDiscard:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1DISCARDCOUNT:0
P1CREDITCOUNT:0
P1TEMPZONECOUNT:0

---

# Glow_CreditsCount
#// Boga is paid from resources (the Credit offer declined), leaving 0 ready and 1 Credit: the entry glows,
#// because Credits pay every cost. The old discard glow counted ready resources only.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6;myhandCardIds:HMW_122;discardCardIds:SOR_095}
P1OnlyActions: true
WithP1Credits: 1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1DISCARDPLAYABLE:0

---

# Pilot_PlayedAsAUnitOnly
#// "Play that UNIT": JTL_046 Paige Tico (Piloting) may only be played as a unit (official Piloting
#// ruling, 03/06/2025). 10 resources: Boga 6, 4 left — the unit (2 + 2 Vigilance penalty − 1 = 3) AND
#// the Pilot (2 + 2 = 4) are both affordable and an X-Wing is there to fly, so an unrestricted permission
#// would ask "Unit or Pilot?". Boga's does not: it enters the ground arena as a unit, 1 left.

## GIVEN
CommonSetup: ggw/rrk/{myResources:10;myhandCardIds:HMW_122;discardCardIds:JTL_046}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayFromDiscard:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:1:CARDID:JTL_046
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:1

---

# KeepsABetterPermission_CobbVanthsFreePlay
#// SHD_115 Cobb Vanth dies attacking SOR_046 and discards SOR_095 from the deck with "play it for FREE
#// this phase" (TPF). Boga then picks that SOR_095: its "1 less" must not DOWNGRADE the free play — with 0
#// resources left after Boga it still plays. (Cobb, also in the discard, is the other legal pick.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:6;myhandCardIds:HMW_122}
P1OnlyActions: true
WithP1GroundArena: SHD_115:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SEC_110 SEC_110 SEC_110]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SOR_095
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-1
- P1>PlayFromDiscard:1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_115
P1RESAVAILABLE:0

---

# ControlChange_TheControllersDiscardPile
#// "Your discard pile" is read from Boga's CONTROLLER. P1 controls P2's Boga; it trades with TWI_235
#// (6/5), goes to its OWNER's discard, and its When Defeated offers P1's discard — not P2's.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0;discardCardIds:SOR_095,SEC_080;theirDiscardCardIds:SOR_128,SOR_046}
P1OnlyActions: true
WithP1GroundArenaControlled: HMW_122:2
WithP2GroundArena: TWI_235:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2DISCARDUNIT:2:CARDID:HMW_122
P1HASDECISION
P1SELECTABLEEXACT:myDiscard-0&myDiscard-1

---

# AcrossARequestBoundary
#// The pick is answered in a fresh process; the stamp lands on the chosen entry.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;myhandCardIds:HMW_122;discardCardIds:SOR_095,SEC_080}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myDiscard-1

## EXPECT
P1DISCARDUNIT:0:MODIFIER:
P1DISCARDUNIT:1:MODIFIER:TPP1U

---

# ActionClose_TheDiscardPlayEndsTheActionOnce
#// The at-cost discard play now runs the hand play pipeline, which closes its own action — the inline path
#// it replaced closed it too, so exactly ONE turn swap must remain. No P1OnlyActions (it hides a double
#// swap): P1 plays Boga, P2 passes, P1 plays SOR_095 from the discard, and the turn is P2's.

## GIVEN
CommonSetup: ggw/rrk/{myResources:7;myhandCardIds:HMW_122;discardCardIds:SOR_095}

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>PlayFromDiscard:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
TURNPLAYER:2
NOEXTRAACTION
