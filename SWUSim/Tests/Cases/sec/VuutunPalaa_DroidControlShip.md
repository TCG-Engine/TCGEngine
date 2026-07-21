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
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:EXHAUSTED
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
