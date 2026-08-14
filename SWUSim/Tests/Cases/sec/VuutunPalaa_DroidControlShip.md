# CostsLessPerDroid
#// SEC_122 Vuutun Palaa — "costs 1 resource less for each friendly Droid unit"
#// SEC_122 is a Space Capital Ship, cost 9, Command aspect, unique.
#// P1 controls 3 friendly Droid units:
#//   - 2 × TWI_T01 Battle Droid tokens (type "Token Unit", trait Droid) in the Ground
#//     arena — these PROVE that token Droid units are counted (GetField includes tokens).
#//   - 1 × TWI_T01 Battle Droid token in the Space arena — proves both arenas are scanned.
#// Discount = 3 × 1 = 3.  Effective cost = 9 - 3 = 6.
#// P1 starts with exactly 6 ready resources → can afford SEC_122 at discounted cost.
#// If tokens weren't counted (or only one arena checked), the cost would be 8 or 9 and
#// the play would fail (not enough resources). Assertion P1RESAVAILABLE:0 confirms 6 paid.
#//
#// Implementation note: SWU uses traits (HasTrait / $traitData) for "Droid", not subtypes
#// (CardSubtypes always returns '' in SWUSim). The modifier uses GetField + HasTrait.
#//
#// Aspect: SEC_122 is Command-only. Leader = SOR_007 Tarkin (Command+Villainy), base =
#// SOR_024 Echo Base (Command). No aspect penalty.
#//
#// NOTE: SEC_122 is NOT a Droid (traits: Separatist,Vehicle,Capital Ship) and is in hand
#// during the cost calculation, so it never counts itself.

## GIVEN
CommonSetup: ggk/ggk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6:SOR_095
WithP1Hand: SEC_122
WithP1GroundArena: TWI_T01
WithP1GroundArena: TWI_T01
WithP1SpaceArena: TWI_T01

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:SEC_122
P1RESAVAILABLE:0
P1HANDCOUNT:0

---

# DroidPaymentLowersResourcesPaid
#// SEC_122 Vuutun Palaa — Droids pay full cost of a unit → resources-paid = 0
#// "Each friendly Droid unit may be exhausted to pay costs as if it were a resource."
#// P1 controls SEC_122 in the Space arena and 2 ready TWI_T01 Battle Droids in the Ground
#// arena. LAW_231 Weequay Pirate (cost 2, Cunning) is in hand. P1 has 0 ready resources.
#// Playing LAW_231 triggers the Droid alt-pay MZMULTICHOOSE (max 2). P1 exhausts both Droids
#// → cost 2 fully covered by Droids, real resources paid = 0.
#// LAW_231 "When Played: If no resources were paid to play this unit, give it an Experience
#// token." → SWUUnitResourcesPaid returns 0 == 0 → LAW_231 gets +1/+1 (3/2 → 4/3).
#// Assertion: P1RESAVAILABLE:0 (no resources spent), LAW_231 Power:3/HP:4 (base 3/2 + token).
#// Both Battle Droids are EXHAUSTED (Status:0). No bounce or other side effects.
#//
#// Leader: yyk = Cunning+Villainy (SOR_016 Thrawn) + Cunning base (SOR_029).
#// LAW_231 is Cunning — Thrawn covers that aspect → no penalty (effective cost = 2).
#// SEC_122 is placed directly via WithP1SpaceArena (no play cost incurred here).
#//
#// LAW_231 is the only unit in hand (auto-selected → no WhenPlayed target prompt needed).
#// MZMULTICHOOSE answer: both TWI_T01s at myGroundArena-0 and myGroundArena-1.

## GIVEN
CommonSetup: yyk/ggk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SEC_122:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1Resources: 0
WithP1Hand: LAW_231

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:LAW_231
P1GROUNDARENAUNIT:2:POWER:3
P1GROUNDARENAUNIT:2:HP:4
P1GROUNDARENAUNIT:2:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
P1RESAVAILABLE:0
P1RESCOUNT:0
P1HANDCOUNT:0

---

# DroidPaysFalconRegroup
#// SEC_122 Vuutun Palaa -- Droid pays the Falcon regroup cost (non-play cost coverage)
#// "Each friendly Droid unit may be exhausted to pay costs as if it were a resource."
#// SOR_193 Millennium Falcon is in the Space arena. At the regroup phase Ready step the
#// Falcon trigger fires and asks the controller to pay 1 resource or bounce. Because
#// SEC_122 is in play and a ready Droid (SOR_236 R2-D2) is on the board, the engine
#// offers a MZMULTICHOOSE instead of an immediate resource payment. P1 picks the Droid
#// and the Droid is exhausted, NO resources are spent (P1RESAVAILABLE stays at 0),
#// and the Falcon STAYS in the Space arena.
#//
#// Space arena order: SEC_122 (index 0), SOR_193 (index 1).
#// Ground arena: SOR_236 R2-D2 at index 0 (the only ready Droid offered).
#// SOR_236 placed directly via WithP1GroundArena (no WhenPlayed trigger fires).
#//
#// Phase flow (mirrors existing Falcon regroup tests):
#//   P1>Pass                           - P1 passes main action
#//   P1>ResourcePass / P2>ResourcePass - both answer the Resource-step MZMAYCHOOSE
#//   P1>AnswerDecision:YES             - keep the Falcon (SEC_122 triggers MZMULTICHOOSE)
#//   P1>AnswerDecision:myGroundArena-0 - exhaust R2-D2 (FALCON_DROIDPAY_RESOLVE)

## GIVEN
CommonSetup: ygw/yrk
P1OnlyActions: true
WithP1SpaceArena: SEC_122
WithP1SpaceArena: SOR_193
WithP1GroundArena: SOR_236
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:SOR_193
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_236
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESCOUNT:0
P1RESAVAILABLE:0

---

# DroidPaysFalconRegroupRefuseBounces
#// SEC_122 Vuutun Palaa -- Droid pays the Falcon regroup cost (non-play cost coverage)
#// "Each friendly Droid unit may be exhausted to pay costs as if it were a resource."
#// SOR_193 Millennium Falcon is in the Space arena. At the regroup phase Ready step the
#// Falcon trigger fires and asks the controller to pay 1 resource or bounce. Because
#// SEC_122 is in play and a ready Droid (SOR_236 R2-D2) is on the board, the engine
#// offers a MZMULTICHOOSE instead of an immediate resource payment. P1 picks the Droid
#// and the Droid is exhausted, NO resources are spent (P1RESAVAILABLE stays at 0),
#// and the Falcon STAYS in the Space arena.
#//
#// Space arena order: SEC_122 (index 0), SOR_193 (index 1).
#// Ground arena: SOR_236 R2-D2 at index 0 (the only ready Droid offered).
#// SOR_236 placed directly via WithP1GroundArena (no WhenPlayed trigger fires).
#//
#// Phase flow (mirrors existing Falcon regroup tests):
#//   P1>Pass                           - P1 passes main action
#//   P1>ResourcePass / P2>ResourcePass - both answer the Resource-step MZMAYCHOOSE
#//   P1>AnswerDecision:YES             - keep the Falcon (SEC_122 triggers MZMULTICHOOSE)
#//   P1>AnswerDecision:myGroundArena-0 - exhaust R2-D2 (FALCON_DROIDPAY_RESOLVE)

## GIVEN
CommonSetup: ygw/yrk
P1OnlyActions: true
WithP1SpaceArena: SEC_122
WithP1SpaceArena: SOR_193
WithP1GroundArena: SOR_236
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:YES
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_236
P1GROUNDARENAUNIT:0:READY
P1RESCOUNT:0
P1RESAVAILABLE:0

---

# DroidsPayForEvent
#// SEC_122 Vuutun Palaa — "Each friendly Droid unit may be exhausted to pay costs as if it were a resource"
#// applies to EVENTS too, not just units. With Vuutun in play, 2 ready Battle Droids and 0 resources, P1 plays
#// SOR_218 Asteroid Sanctuary (cost 2, Cunning) by exhausting both Droids (real resources paid = 0); the event
#// resolves and exhausts the enemy SOR_046.
## GIVEN
CommonSetup: yyk/ggk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SEC_122:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1Resources: 0
WithP1Hand: SOR_218
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:EXHAUSTED
P1RESAVAILABLE:0

---

# ExhaustedDroidsDoNotCount_CannotPay
#// SEC_122 Vuutun Palaa — an already-exhausted Droid is NOT available to pay. With 1 ready + 1 exhausted
#// Battle Droid and 0 resources, LAW_231 (cost 2) can't be covered (1 Droid pays only 1), so it can't be
#// played and stays in hand.
## GIVEN
CommonSetup: yyk/ggk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SEC_122:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:0:0
WithP1Resources: 0
WithP1Hand: LAW_231
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:1
P1GROUNDARENACOUNT:2

---

# DroidsPayForUpgrade
#// SEC_122 Vuutun Palaa — "Each friendly Droid unit may be exhausted to pay costs as if it were a resource"
#// applies to UPGRADES too. With Vuutun in play, 2 ready Battle Droids and 0 resources, P1 plays SOR_214
#// Smuggling Compartment (cost 1) onto Vuutun by exhausting 1 Droid. Previously the upgrade was (a) filtered
#// out of valid targets (affordability ignored Droid capacity) and (b) double-offered a spurious PLAY_CARD
#// Droid step, leaving it stuck in hand.

## GIVEN
CommonSetup: yyk/ggk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SEC_122:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1Resources: 0
WithP1Hand: SOR_214

## WHEN
- P1>PlayHand:0
# (host choose auto-resolves since the 2026-08-13 Vehicle-group fix: Vuutun is the only legal
#  Vehicle host on this board, so the SOR_214 pool is a single option)
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1HANDCOUNT:0
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:SOR_214
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:0

---

# DroidsPayForSmuggle
#// SEC_122 Vuutun Palaa — "Each friendly Droid unit may be exhausted to pay costs as if it were a
#// resource." SMUGGLE is a cost paid to PLAY a card, so Droids must be able to pay it too, exactly as they
#// already do for a normal play, an event, and an upgrade (the three sections above). P1 smuggles
#// SHD_065 Vigilant Pursuit Craft (Smuggle [7 resources, Vigilance]) holding only 4 real ready resources
#// plus 3 Battle Droid tokens — the card itself self-pays 1 (CR 8.22.e), leaving 6 to find from 3 real
#// resources + 3 Droids. It enters the space arena and all three Droids end EXHAUSTED.
## GIVEN
CommonSetup: bbk/ggk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SEC_122:1:0
WithP1GroundArena: TWI_T01
WithP1GroundArena: TWI_T01
WithP1GroundArena: TWI_T01
WithP1Resources: 1:SHD_065:1,3:SOR_095:1
## WHEN
- P1>SmuggleResource:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1&myGroundArena-2
## EXPECT
P1SPACEARENACOUNT:2
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:2:EXHAUSTED

---

# DroidExhaustCapIsTheCardsCost_ExtraDroidsAreNotBurned
#// SEC_122 Vuutun Palaa — Droids pay "as if they were a resource", and CR 1.7.2 says a player exhausts
#// ready resources EQUAL TO the cost (CR 8.1.4 forbids exhausting one when not paying a cost). So a
#// 1-cost card can only ever consume ONE Droid, no matter how many are offered up. P1 has 2 ready Battle
#// Droids and 0 resources and submits BOTH for SOR_108 Vanguard Infantry (cost 1): the card is played and
#// exactly one Droid is exhausted — the second stays ready rather than being burned for nothing.
#// ⚠ The MZMULTICHOOSE upper bound is enforced by the CLIENT only (the queue controller checks that at
#// least one choice exists, never how many were submitted), so this cap has to live in the DROID_PAY
#// handler; that is also the only reason it is testable at all, since the harness bypasses the offer.

## GIVEN
CommonSetup: ggk/ggk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SEC_122:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1Resources: 0
WithP1Hand: SOR_108

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:SOR_108
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:READY
P1RESAVAILABLE:0
P1HANDCOUNT:0

---

# DeclineDroidPayment_NothingIsSpentAndTheCardStaysInHand
#// SEC_122 Vuutun Palaa — declining the Droid offer must leave the board exactly as it was, not half-pay.
#// P1 has 0 real resources, so SOR_108 is affordable ONLY via Droids; declining the offer aborts the play
#// cleanly: the card is still in hand and BOTH Droids are still ready. (The paired section above shows the
#// same fixture succeeding when the offer is accepted, so this is a real abort, not an unplayable board.)

## GIVEN
CommonSetup: ggk/ggk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SEC_122:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1Resources: 0
WithP1Hand: SOR_108

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:READY
P1RESAVAILABLE:0

---

# OnlyREADYDroidsAreOfferedAsPayment_ExhaustedAndNonDroidExcluded
#// SEC_122 Vuutun Palaa — the payment offer is built from READY friendly DROID units only. P1 controls a
#// ready Battle Droid, an already-exhausted Battle Droid, and a Wampa (SOR_164 — Creature, not a Droid).
#// Only the ready Droid is selectable: the exhausted one can't pay (CR 1.7.2) and the Wampa was never
#// eligible. This asserts the OFFER itself, which the outcome alone can't distinguish.

## GIVEN
CommonSetup: ggk/ggk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SEC_122:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:0:0
WithP1GroundArena: SOR_164:1:0
WithP1Resources: 4
WithP1Hand: SOR_108

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0

---

# DroidPaymentAppliesToEVERYCardPlayed_AcrossTheTurnAndIntoTheNextPhase
#// SEC_122 Vuutun Palaa — the Droid alt-payment is a standing constant ability, not a once-per-turn or
#// once-per-card effect. With 0 real resources throughout, P1 plays SOR_108 (cost 1, one Droid), then
#// SEC_080 (cost 2, the other two Droids) in the SAME turn; all three Droids end exhausted. After the
#// regroup phase readies them, a third card is played the same way in the NEXT action phase. P1 never
#// spends a real resource — resource count and ready resources stay 0 the whole way.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1SpaceArena: SEC_122:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1Resources: 0
WithP1Hand: SOR_108
WithP1Hand: SEC_080
WithP1Hand: SOR_108
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1&myGroundArena-2
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:6
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:2:READY
P1RESAVAILABLE:0
P1RESCOUNT:0

---

# ExploitedDroidStillCountsForThePerDroidDiscount
#// SEC_122 Vuutun Palaa — Determine Cost is a SINGLE step and every reduction is settled against the same
#// board state, with the player free to order the reductions within it. So a Droid that Exploit defeats
#// still counts for "costs 1 resource less for each friendly Droid unit": take the per-Droid reduction
#// first, then Exploit. P1 controls 3 Battle Droids and uses TWI_005 Count Dooku's leader Action ("Play a
#// Separatist card from your hand. It gains Exploit 1") on SEC_122, exploiting one Droid.
#//   9 (printed) − 3 (all three Droids) − 2 (Exploit 1) = 4 → 10 resources drop to 6.
#// Sampling the count AFTER the defeat would give 9 − 2 − 2 = 5, which is also order-dependent (applying
#// the two reductions the other way round changes the answer) — a single cost determination must not be.
#// Judge ruling, 2026-08-08.

## GIVEN
CommonSetup: ggk/ggk/{myLeader:TWI_005}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1Resources: 10
WithP1Hand: SEC_122

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_122
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:6
P1HANDCOUNT:0

---

# ExploitDeclined_PerDroidDiscountOnly
#// SEC_122 Vuutun Palaa — the control for the section above: decline Exploit and only the per-Droid
#// reduction applies. 9 − 3 = 6, so 10 resources drop to 4 and all three Droids survive. The pair pins
#// both reductions independently — the Exploit case must be exactly 2 cheaper than this one, which is
#// only true if the third Droid was still counted while being defeated.

## GIVEN
CommonSetup: ggk/ggk/{myLeader:TWI_005}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1Resources: 10
WithP1Hand: SEC_122

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:3
P1RESAVAILABLE:4

---

# ExploitedDroidIsGoneBeforeThePayStep_CannotAlsoBeExhaustedToPay
#// SEC_122 Vuutun Palaa — the other half of the same ruling. Exploit's defeats happen during Determine
#// Cost; exhausting Droids as payment happens in the LATER Pay Costs step. A Droid spent to Exploit is
#// therefore already gone and cannot also be exhausted to pay — it counts once, not twice.
#// Vuutun is already in play (so its payment ability is active) and P1 plays TWI_083 General's Guardian
#// (cost 4) through Dooku with Exploit 1, exploiting one of the three Droids. Exploit takes the cost to 2,
#// and the pay-time offer lists exactly the TWO survivors; both are exhausted and P1's single real
#// resource is never touched.
#// (Vuutun's per-Droid discount does NOT apply here — it reduces only Vuutun's own cost.)

## GIVEN
CommonSetup: ggk/ggk/{myLeader:TWI_005}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SEC_122:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1Resources: 1
WithP1Hand: TWI_083

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:TWI_083
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
P1RESAVAILABLE:1
P1HANDCOUNT:0
