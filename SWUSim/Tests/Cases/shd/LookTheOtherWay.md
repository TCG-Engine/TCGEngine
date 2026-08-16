# CantPay_Exhausted
#// SHD_227 Look the Other Way — with only 1 ready resource, P2 cannot pay the 2, so SOR_046 is exhausted
#// (no choice is offered).
#// COVERAGE: offer=Offer_SpansBothSidesAndBothArenas (decision left pending; "a unit" is unqualified, so
#//           the pool is every unit in both arenas on both sides) · reqboundary=
#//           SimulateRequestBoundary_ControllerPayResolvesTheSameUnit (the target pick and the
#//           controller's pay/decline are separate requests; the unit is re-resolved by identity after
#//           the boundary) · control=ControlledEnemyOwnedUnit_ControllerPaysNotOwner (a unit P1 controls
#//           but P2 owns — the tax decision follows the CONTROLLER) · boundary pair=CantPay_Exhausted
#//           (1 resource: no offer at all) vs ControllerPays_NotExhausted (exactly 2: the pay is
#//           affordable and offered) · decline=Declines_Exhausted (enemy declines) +
#//           FriendlyTarget_DeclinesPay_OwnUnitExhausted (you decline for your own unit)

## GIVEN
CommonSetup: yyk/yyk/{theirResources:1}
WithActivePlayer: 1
WithP1Hand: SHD_227
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# ControllerPays_NotExhausted
#// SHD_227 Look the Other Way (0-cost event) — "Exhaust a unit unless its controller pays 2 resources." P1
#// targets the enemy SOR_046; its controller P2 has 2 ready resources and chooses to pay, so SOR_046 stays
#// ready and P2's resources drop to 0.

## GIVEN
CommonSetup: yyk/yyk/{theirResources:2}
WithActivePlayer: 1
WithP1Hand: SHD_227
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:READY
P2RESAVAILABLE:0

---

# Declines_Exhausted
#// SHD_227 Look the Other Way — P2 can afford the 2 but declines to pay, so SOR_046 is exhausted and P2
#// keeps its 2 resources.

## GIVEN
CommonSetup: yyk/yyk/{theirResources:2}
WithActivePlayer: 1
WithP1Hand: SHD_227
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:EXHAUSTED
P2RESAVAILABLE:2

---

# Offer_SpansBothSidesAndBothArenas
#// SHD_227 — "Exhaust A UNIT" is unqualified: the target pool is every unit on the board, friendly and
#// enemy, ground and space. All four bodies are seated and the target choice is left PENDING so the
#// offer itself is the assertion.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2;theirResources:2}
WithActivePlayer: 1
WithP1Hand: SHD_227
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0

---

# FriendlyTarget_DeclinesPay_OwnUnitExhausted
#// SHD_227 — the "unless its controller pays 2" decision belongs to the TARGET's controller, not to the
#// caster. P1 aims the event at its OWN SOR_095, so P1 is the one asked to pay; P1 declines and exhausts
#// its own unit. The event costs 0, so P1's 2 resources are still untouched afterwards and the enemy
#// unit is completely unaffected.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2;theirResources:2}
WithActivePlayer: 1
WithP1Hand: SHD_227
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:2
P2GROUNDARENAUNIT:0:READY
P2RESAVAILABLE:2

---

# FriendlyTarget_YouPayForYourOwnUnit
#// SHD_227 — the other half of the friendly-target pair: P1 targets its own SOR_095 and chooses to PAY,
#// so P1 taxes itself 2 resources and the unit stays ready. Together with
#// FriendlyTarget_DeclinesPay_OwnUnitExhausted this proves the payer is always the target's controller,
#// even when that is the player who played the event.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2;theirResources:2}
WithActivePlayer: 1
WithP1Hand: SHD_227
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:READY
P1RESAVAILABLE:0
P2GROUNDARENAUNIT:0:READY
P2RESAVAILABLE:2

---

# SimulateRequestBoundary_ControllerPayResolvesTheSameUnit
#// SHD_227 — in production the caster's target pick and the controller's pay/decline arrive as two
#// separate requests, so the chosen unit has to be re-resolved from the serialized gamestate rather than
#// from anything cached during the pick. Mirrors Declines_Exhausted with two units on the board (so the
#// pick is a real choice) and the boundary inserted between the pick and P2's answer.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2;theirResources:2}
WithActivePlayer: 1
WithP1Hand: SHD_227
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>SimulateRequestBoundary
- P2>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:EXHAUSTED
P2RESAVAILABLE:2
P1GROUNDARENAUNIT:0:READY

---

# ControlledEnemyOwnedUnit_ControllerPaysNotOwner
#// SHD_227 — "its CONTROLLER pays 2", not its owner. P1 controls a SOR_046 that P2 still OWNS (the end
#// state after a take-control effect). P1 aims the event at that unit: the pay/decline is put to P1, and
#// P1 paying spends P1's resources while P2's are untouched. Were the tax routed by ownership, P2 would
#// have been billed instead.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2;theirResources:2}
WithActivePlayer: 1
WithP1Hand: SHD_227
WithP1GroundArenaControlled: SOR_046:2
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:READY
P1RESAVAILABLE:0
P2RESAVAILABLE:2
