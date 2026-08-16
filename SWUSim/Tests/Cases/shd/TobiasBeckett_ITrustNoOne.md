# TobiasBeckett_PlayNonUnit_ExhaustUnit
#// SHD_217 Tobias Beckett — "When you play a non-unit card: You may exhaust a unit that costs the same as
#// or less than the card you played. Once each round." P1 plays SHD_178 (event, cost 1); its own deal-2
#// hits the enemy SHD_095, then Tobias exhausts SHD_095 (cost 1 ≤ 1).
#// COVERAGE: offer=Offer_OnlyUnitsCostingAtMostThePlayedCard (pending pool, both sides and both arenas)
#//           · boundary pair=Offer_OnlyUnitsCostingAtMostThePlayedCard vs
#//           Offer_UpgradePlayRaisesTheBoundaryToTwo — the identical board read at cost 1 and at cost 2,
#//           so the 2-cost body flips from out to in — plus SmuggledPlay_ComparesAgainstThePrintedCost
#//           (paid 5, printed 1, pool still only the 1-cost bodies) and the round-limit pair
#//           OncePerRound_SecondNonUnitPlayRaisesNoOffer vs RearmsInTheNextRound ·
#//           decline=Decline_NothingExhausted · control=EnemyOwnedTobias_ReactsToHisControllersPlay
#//           (a Tobias P1 controls but P2 owns arms off P1's play), paired with the scoping negative
#//           OpponentsNonUnitPlayDoesNotTriggerHim ("when YOU play") · reqboundary=
#//           SimulateRequestBoundary_ExhaustSurvivesTheBoundary. The "non-unit" gate's negative leg is
#//           PlayingAUnitDoesNotTriggerHim; his own Smuggle cost is
#//           SmuggleCostIsSevenWithoutTheVigilanceAspect.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SHD_217:1:0
WithP1Hand: SHD_178
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# PlayingAUnitDoesNotTriggerHim
#// SHD_217 — "When you play a NON-UNIT card". Playing a unit is the excluded case: P1 plays the 1-cost
#// Death Star Stormtrooper with a legal 1-cost enemy body on the board and Tobias raises nothing.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SHD_217:1:0
WithP1Hand: SOR_128
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:2
P2SPACEARENAUNIT:0:READY

---

# Offer_OnlyUnitsCostingAtMostThePlayedCard
#// SHD_217 — the pool is every READY unit on either side in either arena whose cost is at most the cost
#// of the card just played. P1 plays the 1-cost Daring Raid (its own 2 damage sent to the enemy base so
#// the board is untouched) and the offer is left PENDING. The 1-cost friendly Stormtrooper and the
#// 1-cost enemy TIE fighter are in; the 2-cost enemy Battlefield Marine is out, and so is Tobias himself
#// at cost 4.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SHD_217:1:0
WithP1GroundArena: SOR_128:1:0
WithP1Hand: SHD_178
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-1&theirSpaceArena-0

---

# Offer_UpgradePlayRaisesTheBoundaryToTwo
#// SHD_217 — THE BOUNDARY PAIR, and the second half of "a non-unit card": an UPGRADE triggers him too.
#// Identical board to Offer_OnlyUnitsCostingAtMostThePlayedCard, but the card played is the 2-cost Armed
#// to the Teeth, so the 2-cost enemy Battlefield Marine that was excluded a moment ago is now in the
#// pool. Tobias at cost 4 is still out.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SHD_217:1:0
WithP1GroundArena: SOR_128:1:0
WithP1Hand: SHD_175
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-1&theirGroundArena-0&theirSpaceArena-0

---

# Decline_NothingExhausted
#// SHD_217 — "YOU MAY exhaust a unit". The offer is raised and declined; every body on the board is
#// still ready.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SHD_217:1:0
WithP1GroundArena: SOR_128:1:0
WithP1Hand: SHD_178
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:-

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:READY
P2SPACEARENAUNIT:0:READY
P2BASEDMG:2

---

# OncePerRound_SecondNonUnitPlayRaisesNoOffer
#// SHD_217 — "Use this ability only once each round." Two 1-cost events in the same round: the first
#// exhausts the enemy TIE fighter, the second raises nothing even though a second legal 1-cost target
#// (the friendly Stormtrooper) is still ready — so an empty pool cannot be what silences him.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SHD_217:1:0
WithP1GroundArena: SOR_128:1:0
WithP1Hand: [SHD_178 SHD_178]
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirSpaceArena-0
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1NODECISION
P2SPACEARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:READY
P2BASEDMG:4

---

# RearmsInTheNextRound
#// SHD_217 — the once-each-round limit clears at the round boundary: the same second event one round
#// later raises the offer again, and this time the friendly Stormtrooper is exhausted as well. Both
#// decks are seeded past the regroup draws so no empty-deck damage pollutes the base counts.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SHD_217:1:0
WithP1GroundArena: SOR_128:1:0
WithP1Hand: [SHD_178 SHD_178]
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirSpaceArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_128
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:0:READY
P2BASEDMG:4

---

# SmuggledPlay_ComparesAgainstThePrintedCost
#// SHD_217 — "a unit that costs the same as or less than the card you played" means the card's PRINTED
#// cost, never what it actually cost to play. Smuggler's Aid is printed at 1 but is played out of the
#// resource row for its Smuggle cost of 3 (+2 for the uncovered Heroism aspect = 5 resources spent), and
#// Tobias' pool is still only the 1-cost bodies — the 2-cost Battlefield Marine stays out. The offer is
#// left PENDING so the pool is the assertion.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_217:1:0
WithP1GroundArena: SOR_128:1:0
WithP1Resources: 4:SOR_046:1,1:SHD_252:1
WithP1Deck: [SOR_095 SOR_095]
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>SmuggleResource:4

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-1&theirSpaceArena-0

---

# SmuggleCostIsSevenWithoutTheVigilanceAspect
#// SHD_217 — his own Smuggle is [5 resources, Vigilance]. Under an Aggression base with an
#// Aggression/Villainy leader the Vigilance bracket is uncovered, so the smuggle play costs 5 + 2 = 7.
#// Eight resources are seated (seven plain ones plus Tobias himself); the smuggled card is a ready
#// resource that exhausts toward its own cost, so exactly one resource is left ready and the vacated
#// slot is refilled from the top of the deck, keeping the resource COUNT at 8. Playing him as a unit
#// does not trigger his own "when you play a non-unit card" reaction.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 7:SOR_046:1,1:SHD_217:1
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>SmuggleResource:7

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_217
P1RESCOUNT:8
P1RESAVAILABLE:1
P1NODECISION

---

# OpponentsNonUnitPlayDoesNotTriggerHim
#// SHD_217 — "When YOU play a non-unit card". The opponent playing an event is not your play: P2's
#// Daring Raid resolves against P1's base and Tobias raises nothing, with a legal 1-cost target ready on
#// the board the whole time.

## GIVEN
CommonSetup: rrk/rrk/{theirResources:1}
WithActivePlayer: 1
WithP1GroundArena: SHD_217:1:0
WithP1GroundArena: SOR_128:1:0
WithP2Hand: SHD_178

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirBase-0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:READY
P1BASEDMG:2

---

# EnemyOwnedTobias_ReactsToHisControllersPlay
#// SHD_217 — "when YOU play" is read from whoever CONTROLS Tobias, not from whoever owns him. P1
#// controls a Tobias that P2 still owns (the end state after a take-control effect) and P1's own event
#// play arms him. Controlled units seat after the regular arena lines, so Tobias is myGroundArena-1.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP1GroundArenaControlled: SHD_217:2
WithP1Hand: SHD_178
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SHD_217
P2SPACEARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:READY

---

# SimulateRequestBoundary_ExhaustSurvivesTheBoundary
#// SHD_217 — the play that arms him and the answer that spends him arrive as two separate requests in
#// production, so the pending pool, the played card's cost and the once-each-round marker all have to be
#// re-read from the serialized gamestate. Mirrors TobiasBeckett_PlayNonUnit_ExhaustUnit with the
#// boundary inserted between the event's own resolution and the exhaust.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SHD_217:1:0
WithP1GroundArena: SOR_128:1:0
WithP1Hand: SHD_178
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:READY
P2BASEDMG:2
