# GainsAmbush_WithAnotherVehicle
#// SOR_249 Frontier AT-RT (3/5, aspectless ground Vehicle) — "While you control another Vehicle
#// unit, this unit gains Ambush." P1 controls SOR_237 Alliance X-Wing (space Vehicle) — the gate is
#// trait-based and arena-agnostic, so the AT-RT is played WITH Ambush: YES, attack the chosen
#// SOR_128 (3/1) — the Stormtrooper dies, the AT-RT takes 3 and ends exhausted.
#//
#// COVERAGE: offer=AmbushTargetOffer_GroundUnitsOnly (target pool asserted pending) ·
#//           reqboundary=this section (PlayHand → YES → target answers cross three request
#//           boundaries with serialized state) · control=GateCountsControlledNotOwned_Vehicle
#//           (P1-controlled, P2-owned Vehicle satisfies "you control") ·
#//           boundary pair=GainsAmbush_WithAnotherVehicle vs NoAmbush_NonVehicleFriendly +
#//           NoAmbush_NoOtherFriendlyUnit (self excluded) · decline=AmbushDeclined_EntersExhausted

## GIVEN
CommonSetup: ggk/bbk/{myResources:4;handCardIds:SOR_249}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_249
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:1
P2DISCARDCOUNT:1

---

# AmbushDeclined_EntersExhausted
#// Ambush is "may": with the gate ON P1 answers NO — the AT-RT enters play exhausted, no attack
#// happens, and the enemy unit survives undamaged.

## GIVEN
CommonSetup: ggk/bbk/{myResources:4;handCardIds:SOR_249}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_249
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# NoAmbush_NoOtherFriendlyUnit
#// The AT-RT is itself a Vehicle, but the gate says ANOTHER Vehicle unit — alone on the board it
#// gets no Ambush prompt (P1NODECISION) and enters play exhausted.

## GIVEN
CommonSetup: ggk/bbk/{myResources:4;handCardIds:SOR_249}
P1OnlyActions: true
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:CARDID:SOR_249
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# NoAmbush_NonVehicleFriendly
#// The gate is trait-specific: a friendly NON-Vehicle unit (SOR_095 Battlefield Marine, Trooper)
#// does not satisfy "another Vehicle unit" — no prompt, no attack.

## GIVEN
CommonSetup: ggk/bbk/{myResources:4;handCardIds:SOR_249}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:1:CARDID:SOR_249
P1GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# NoAmbush_EnemyVehicleDoesNotCount
#// "While YOU control another Vehicle unit" — the OPPONENT's AT-ST (SOR_232, Vehicle) does not
#// turn the gate on. No Ambush prompt.

## GIVEN
CommonSetup: ggk/bbk/{myResources:4;handCardIds:SOR_249}
P1OnlyActions: true
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:CARDID:SOR_249
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# SecondCopy_FirstATRTCountsAsAnotherVehicle
#// The AT-RT is non-unique and itself a Vehicle: with one AT-RT already on the board, playing a
#// SECOND copy satisfies "another Vehicle unit" — the new copy gets the Ambush prompt and kills
#// the Stormtrooper.

## GIVEN
CommonSetup: ggk/bbk/{myResources:4;handCardIds:SOR_249}
P1OnlyActions: true
WithP1GroundArena: SOR_249:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_249
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:1:DAMAGE:3
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1

---

# AmbushTargetOffer_GroundUnitsOnly
#// OFFER assert: after YES, the Ambush attack of a GROUND unit can only target enemy GROUND units
#// — the enemy space Vehicle is not in the pool. Two enemy ground units are seated so the target
#// choice stays pending (a single target would auto-resolve); the decision is left unanswered and
#// the pool is asserted exactly.

## GIVEN
CommonSetup: ggk/bbk/{myResources:4;handCardIds:SOR_249}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_111:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# GateCountsControlledNotOwned_Vehicle
#// The gate reads CONTROL, not ownership: P1 controls an Alliance X-Wing OWNED by P2 (post-
#// control-change state). It still satisfies "you control another Vehicle unit" → Ambush prompt,
#// Stormtrooper dies.

## GIVEN
CommonSetup: ggk/bbk/{myResources:4;handCardIds:SOR_249}
P1OnlyActions: true
WithP1SpaceArenaControlled: SOR_237:2
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_249
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
