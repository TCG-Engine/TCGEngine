# Action_DefeatsItselfAndBlocksTheChosenUnitsNextRegroupReady
#// HMW_095 — "Fortify. Action [defeat this upgrade]: Choose a non-Vehicle unit. It doesn't ready during
#// the next regroup phase." The first ACTIVATED ability hosted on a base rather than a unit, so it is
#// reached by clicking the base (UseBaseAbility) and must NOT consume the base's Epic slot.
#// Both enemy units start exhausted; after the regroup the chosen one is still exhausted while its
#// neighbour readies — the neighbour is what proves the ready step actually ran.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095
WithP2GroundArena: [SOR_095:0:0 SOR_046:0:0]
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1BASE:UPGRADECOUNT:0
P1DISCARDCOUNT:1
P1BASE:EPICAVAILABLE
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY

---

# Action_OnlyNonVehicleUnitsAreOffered
#// "Choose a NON-VEHICLE unit." SEC_214 Skyhopper Canyon Runner is a vanilla ground Vehicle, so the only
#// legal target is SOR_095 — which makes the pick a single-target auto-resolve, and the WHEN deliberately
#// sends NO answer. If the Vehicle were also offered there would be two candidates, the unanswered choose
#// would strand, and neither unit would carry the marker.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095
WithP2GroundArena: [SOR_095:0:0 SEC_214:0:0]
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>UseBaseAbility
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY

---

# Action_MarkerDoesNotBlockAMidPhaseReadyEffect
#// Scope guard. The card says the unit "doesn't ready during the next REGROUP PHASE" — it is NOT
#// SOR_186's "can't ready this round", which also blocks explicit "ready a unit" effects. So its own
#// SOR_095 (power 3) still readies from SOR_169 Keep Fighting in the same action phase. Reusing SOR_186's
#// SWU_CANT_READY_ flag here would leave it exhausted.
#// Both picks are single-target auto-resolves (one non-Vehicle unit in play; one unit with power <= 3).

## GIVEN
CommonSetup: rrw/rrw/{myResources:3;myhandCardIds:SOR_169}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095
WithP1GroundArena: SOR_095:0:0

## WHEN
- P1>UseBaseAbility
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:READY

---

# Action_NoLegalTargetStillPaysTheCost
#// The cost is "defeat this upgrade", which changes game state, so per CR 6.4.587.c the Action stays
#// available even with no unit to choose — it is paid and the effect fizzles cleanly.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095

## WHEN
- P1>UseBaseAbility

## EXPECT
P1BASE:UPGRADECOUNT:0
P1DISCARDCOUNT:1
P1NODECISION

---

# Action_CompetesWithTheBasesOwnEpicViaAChoice
#// Clicking the base is a single undifferentiated input, so a base that has BOTH its own Epic Action and
#// a Fortify upgrade with an Action must ask which one to use rather than silently locking one out.
#// SOR_022 Energy Conversion Lab supplies the Epic; choosing the Chamber leaves the Epic unspent.

## GIVEN
CommonSetup: ggw/ggw/{myBase:SOR_022;myResources:3}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095
WithP2GroundArena: SOR_095:0:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:CarboniteChamber

## EXPECT
P1BASE:UPGRADECOUNT:0
P1BASE:EPICAVAILABLE

---

# Action_TheBasesOwnEpicIsStillReachableWhileUpgraded
#// The other branch of the same choice: picking the Epic runs the base ability (ECL plays a unit costing
#// 6 or less from hand) and leaves the Fortify upgrade attached.

## GIVEN
CommonSetup: ggw/ggw/{myBase:SOR_022;myResources:3;myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:EpicAction
- P1>AnswerDecision:myHand-0

## EXPECT
P1BASE:UPGRADECOUNT:1
P1BASE:EPICUSED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
