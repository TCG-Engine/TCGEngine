# ForceUnit_CostReducedAndDamage
#// SOR_139 (Aggression/Villainy event, cost 2, Force) — "If you control a FORCE unit, this event costs
#// 1 less. Deal 5 damage to a non-Vehicle unit. That unit's controller draws a card." P1 controls a
#// Force unit (SOR_004) → SOR_139 costs 1 (2 ready resources → 1 left after). It deals 5 to the enemy
#// SOR_046 (3/7 → survives at DAMAGE:5) and that unit's controller (P2) draws a card from their deck.
#// Two non-Vehicle units are in play (friendly SOR_004 + enemy SOR_046) so the target is chosen.
#// COVERAGE: offer=Offer_ExcludesVEHICLEUnits (a Vehicle plus TWO legal targets seated, decision left
#//           pending, P1SELECTABLEEXACT asserts the exact pool) + EveryUnitIsAVehicle_... (the
#//           exclusion emptying the pool) · reqboundary=SimulateRequestBoundary_TargetPickAndDrawRider
#//           Survive · control=ControlTakenUnit_TheCONTROLLERDraws_NotTheOwner (owner differs from
#//           controller: the draw follows the CONTROLLER, the defeated card follows the OWNER) ·
#//           boundary=ForceUnit_CostReducedAndDamage (cost 1) vs NoForceUnit_FullCost (cost 2) is the
#//           N vs N+1 pair for the discount, and the 5-damage clause is discriminated by
#//           TargetsOwnUnit_... (3 HP -> defeated) against ForceUnit_CostReducedAndDamage (7 HP ->
#//           survives at DAMAGE:5) · decline=N/A — neither clause is printed as "you may": the target
#//           pick is a mandatory MZCHOOSE (SWUValidateDecisionAnswer refuses a PASS on it) and the
#//           rider draw is not optional either, so the only no-effect branch is an empty pool, covered
#//           by EveryUnitIsAVehicle_NoTarget_EventStillPlayedAndPaid.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_004:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Deck: SOR_237
WithP1Hand: SOR_139

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:1
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:5
P2HANDCOUNT:1
P2DECKCOUNT:0

---

# NoForceUnit_FullCost
#// SOR_139 — cost-reduction guard: P1 controls only a non-Force unit (SOR_128 Imperial), so the "if you
#// control a Force unit" discount does NOT apply → full cost 2 (2 ready resources → 0 left). The damage
#// + draw effect still resolves: 5 to the enemy SOR_046, P2 draws.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Deck: SOR_237
WithP1Hand: SOR_139

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:5
P2HANDCOUNT:1
P2DECKCOUNT:0

---

# Offer_ExcludesVEHICLEUnits
#// SOR_139 Force Choke — "Deal 5 damage to a NON-VEHICLE unit." The Vehicle exclusion is a pool
#// restriction, so it has to be asserted on the OFFER: answering a target only ever proves the branch
#// that was taken. Three units are seated — P1's Battlefield Marine (Rebel/Trooper, no Vehicle trait),
#// P2's Consular Security Force (likewise) and P2's TIE/ln Fighter (Imperial/VEHICLE/Fighter, space).
#// Two of them are legal, so the pick stays interactive and the decision is left PENDING.
#// Intended: exactly myGroundArena-0 and theirGroundArena-0 — the space Vehicle is never offered, and
#// "a unit" carries no controller word so P1's own unit is in the pool.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: SOR_139

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# TargetsOwnUnit_YOUAreTheControllerSoYOUDraw
#// SOR_139 Force Choke — the resolution half of the offer above, taking the branch that discriminates
#// the rider: "THAT UNIT'S CONTROLLER draws a card" is resolved from the TARGET, not from the caster and
#// not from the opponent. P1 chokes their OWN Battlefield Marine (3/3): it takes 5 and is defeated, and
#// the card is drawn by P1. An implementation that hardcoded the draw to the opponent would send the
#// card to P2 here while passing both of the enemy-target sections above.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_237
WithP2Deck: SOR_237
WithP1Hand: SOR_139

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1HANDCOUNT:1
P1DECKCOUNT:0
P2HANDCOUNT:0
P2DECKCOUNT:1

---

# ControlTakenUnit_TheCONTROLLERDraws_NotTheOwner
#// SOR_139 Force Choke × a control change. P1 CONTROLS an Imperial Dark Trooper (3/3) that P2 OWNS.
#// It is the only non-Vehicle unit on the table, so the pick auto-resolves onto it. Two readings have
#// to come apart here and both are asserted:
#//   * "That unit's CONTROLLER draws" — the controller is P1, so P1 draws; P2 does not.
#//   * CR 8.4 — the 5 damage defeats the 3/3, and a defeated card goes to its OWNER's discard, so the
#//     Dark Trooper lands in P2's pile even though P1 controlled it.
#// The impl captures the controller BEFORE dealing the damage precisely because the hit can clean the
#// unit up; a post-damage read would find a removed object and drop the draw entirely.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArenaControlled: SEC_080:2
WithP1Deck: SOR_237
WithP2Deck: SOR_237
WithP1Hand: SOR_139

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DECKCOUNT:0
P2HANDCOUNT:0
P2DECKCOUNT:1
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SEC_080

---

# EveryUnitIsAVehicle_NoTarget_EventStillPlayedAndPaid
#// SOR_139 Force Choke — the no-valid-target branch of the Vehicle exclusion. The only unit in play is
#// P2's TIE/ln Fighter, a Vehicle, so the pool is empty and no decision is raised: neither the damage
#// nor the draw rider happens. Per the standing ruling that a fizzling action still pays its cost,
#// Force Choke is played anyway — 2 resources spent (no Force unit, so no discount) and the card in
#// P1's discard. Guards an empty MZCHOOSE hanging the action and a "no targets" path that refunds.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP2SpaceArena: SOR_225:1:0
WithP2Deck: SOR_237
WithP1Hand: SOR_139

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1RESAVAILABLE:0
P1DISCARDCOUNT:1
P1HANDCOUNT:0
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:DAMAGE:0
P2HANDCOUNT:0
P2DECKCOUNT:1

---

# SimulateRequestBoundary_TargetPickAndDrawRiderSurvive
#// SOR_139 Force Choke — in production the "deal 5 damage to a non-Vehicle unit" MZCHOOSE ENDS the
#// request, so the answer arrives in a fresh process with every non-serialized global empty. Mirrors
#// Offer_ExcludesVEHICLEUnits' board with the boundary inserted before the answer: both the pending
#// pick and its SOR_139#0 continuation (which still has to read the target's controller and run the
#// draw rider) must survive a gamestate round-trip. P1 answers the enemy Consular Security Force
#// (3/7) — it survives on 5 damage and its controller P2 draws.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_237
WithP2Deck: SOR_237
WithP1Hand: SOR_139

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENACOUNT:1
P2HANDCOUNT:1
P2DECKCOUNT:0
P1HANDCOUNT:0
P1DECKCOUNT:1
