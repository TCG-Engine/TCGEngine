# UpgradedBase_CostsTwoLess
#// COVERAGE: offer=N/A — STRUCTURAL: neither clause selects anything. Clause 1 is a play-cost modifier
#//           (no targets, no decision) and clause 2 is the Restore keyword (its heal is fixed on the
#//           controller's own base). There is no pool anywhere on the card. The nearest equivalent —
#//           whether the discounted card is OFFERED at all — IS asserted, through the affordability
#//           pair Credits_TheDISCOUNTEDCostIsReachable / Credits_UndiscountedCostIsNOTReachable.
#//           decline=N/A — STRUCTURAL: no "you may", no "up to"; both clauses are automatic.
#//           boundary=N/A — STRUCTURAL: "an upgraded base" is a BOOLEAN, not a threshold, so there is
#//           no N vs N±1 to pin. The nearest hazard — reading it as a per-upgrade count — is covered
#//           by TwoUpgradesOnTheBaseIsStillOnlyTwoLess.
#//           control=Restore2_UnderNewControl_HealsTHEIRBase (Restore says "your base": under a
#//           take-control effect it must heal the NEW CONTROLLER's base, not the owner's)
#//           reqboundary=SurvivesTheRequestBoundary
#//           modes=2P only — "if YOU control an upgraded base" is self-only in every format by design,
#//           and Restore's heal is the controller's own base. Neither clause carries a player
#//           reference or the words friendly/enemy, so all three formats are one code path.
#//
#// HMW_260 Queen Amidala, Retaking Theed — 4-cost 4/4 Ground, Naboo·Official, unique.
#//   "If you control an upgraded base, this unit costs [2 resources] less to play.
#//    Restore 2"
#// ⚠ PREVIEW SET — no official rulings exist for HMW. Read from the CR plus released precedent.
#// ⚠ The preview data gives this card an EMPTY aspect array, so it is priced as aspectless (no penalty
#// under any leader/base). That is taken from the dictionary as the source of truth, per the standing
#// rule — but if the aspects are filled in by a later card-data regen, every resource number in this
#// file moves and these sections are where it will show up first.
#//
#// "AN UPGRADED BASE" IS THE FORTIFY INTERACTION. Fortify ("attach this to your base, not a unit") is
#// an HMW keyword and six Fortify upgrades already exist, so a base can carry upgrades exactly as a
#// unit can — they live in Base.Subcards, which is why the ordinary _SWUIsUpgraded predicate answers
#// this without anything new being built.
#//
#// THE POSITIVE. HMW_081 Alliance Shield Generator is a Fortify upgrade on P1's base, so Amidala costs
#// 4 − 2 = 2, leaving 2 of the 4 resources ready.
#// ⚠ HMW_081 is deliberately the INERT choice: its only ability prevents damage of 5 or more to the
#// attached base, which nothing on these boards deals. A Fortify upgrade with an entry or phase
#// trigger (HMW_147 Beast Lair) would move numbers this file asserts.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_260]
WithP1BaseUpgrade: HMW_081

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_260
P1RESAVAILABLE:2
P1NODECISION

---

# BareBase_CostsFullFour
#// THE NEGATIVE that proves the gate is load-bearing — the same board with the Fortify upgrade removed
#// and nothing else changed. Full price, so all 4 resources are spent. Without this, the positive
#// above passes for a card that simply cost 2.
## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_260]
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:0

---

# OpponentsUpgradedBaseDoesNOTDiscount
#// SEAT SCOPING. "If YOU control an upgraded base" is the CASTER's base, not any upgraded base on the
#// table. P2 holds the Fortify upgrade and P1's base is bare, so the answer is full price — a check
#// written as "an upgraded base is in play" reads 2 here and passes every other section in this file.
## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_260]
WithP2BaseUpgrade: HMW_081
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:0

---

# TwoUpgradesOnTheBaseIsStillOnlyTwoLess
#// QUANTITY DISCRIMINATION. "An upgraded base" is a BOOLEAN — the base either is upgraded or is not —
#// so a second Fortify upgrade must not buy a second discount. HMW_081 is non-unique, so two copies is
#// a legal board. A per-upgrade implementation reads cost 0 here and is indistinguishable from the
#// correct one in every other section.
## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_260]
WithP1BaseUpgrade: HMW_081
WithP1BaseUpgrade: HMW_081
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:2

---

# UpgradeDefeatedBeforeThePlay_CostRECOMPUTES
#// THE DURATION / RECOMPUTE CELL. The discount is a condition read at PLAY time, not a stamp taken when
#// the upgrade arrived — so defeating the Fortify upgrade first puts the price back to 4.
#// P1 plays SOR_251 Confiscate ("Defeat an upgrade", neutral, cost 1) which auto-resolves onto the only
#// upgrade in play, then plays Amidala: 5 − 1 − 4 = 0 ready. A stamped discount reads 1 left here.
#// ⚠ This also pins that base-attached upgrades are reachable by "defeat an upgrade" at all — they were
#// invisible to that whole family until Fortify shipped.
## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: [SOR_251 HMW_260]
WithP1BaseUpgrade: HMW_081
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_260
#// Confiscate itself + the Fortify upgrade it defeated. This pins that the base upgrade was
#// ACTUALLY removed — without it the section would also pass if Confiscate had fizzled against a
#// base-attached upgrade, which is the one way it could read "full price" for the wrong reason.
P1DISCARDCOUNT:2
P1RESAVAILABLE:0

---

# Credits_TheDISCOUNTEDCostIsReachable_OfferRaised
#// THE AFFORDABILITY CELL, half one — and the sharpest form available, because it proves the discount
#// reaches the OFFER and not merely the payment.
#// A cost reduction has to be visible to CanAffordActivationReserve as well as to ActivateCard, or a
#// player who can afford the card is never allowed to play it. Credits make that observable: the engine
#// raises the Credit-payment picker only when total payment capacity actually reaches the cost, so the
#// prompt appearing IS the assertion that the gate priced the card at 2 rather than 4.
#// 1 ready resource + 1 Credit = capacity 2, exactly the discounted cost.
## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SOR_095:1
WithP1Credits: 1
WithP1Hand: [HMW_260]
WithP1BaseUpgrade: HMW_081
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Defeat_any_number_of_Credit_tokens_to_pay_1_resource_less_each

---

# Credits_UndiscountedCostIsNOTReachable_NoOffer
#// THE AFFORDABILITY CELL, half two — the identical board with the Fortify upgrade removed. Capacity is
#// still 2 but the cost is now 4, which is out of reach, so nothing is offered, nothing is spent, and
#// the Credit is NOT destroyed paying for a play that cannot complete.
#// The PAIR is what makes either half mean anything: half one alone would pass for an engine that
#// always raises the picker.
## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SOR_095:1
WithP1Credits: 1
WithP1Hand: [HMW_260]
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P1HANDCOUNT:1
P1GROUNDARENACOUNT:0
P1CREDITCOUNT:1
P1RESAVAILABLE:1

---

# SurvivesTheRequestBoundary
#// THE REQUEST-BOUNDARY CELL. The card raises no interactive decision, so the boundary goes between the
#// two player ACTIONS that write and read the condition: P1 plays the Fortify upgrade onto its base,
#// the request ends, and only then is Amidala played. In production those are two separate processes,
#// so a discount that consulted anything held in memory from the first action would silently price her
#// at 4 here — and the only visible symptom would be two missing resources.
#// 4 resources: HMW_081 costs 2 (Vigilance, covered by the base and leader), Amidala then costs 2.
#// ⚠ A Fortify upgrade's only legal host is the player's own base, so it auto-attaches with no prompt.
## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_081 HMW_260]
## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_260
P1RESAVAILABLE:0

---

# Restore2_HealsYourBaseOnAttack
#// CLAUSE 2. Restore is keyword-only and already auto-wired from the generated registry, so this is a
#// verification section rather than new behaviour — but a keyword-plus-rider card that ships with only
#// the rider tested is exactly how half a card goes uncovered.
#// P1's base starts on 5 damage; Amidala (4/4) attacks the enemy base and Restore heals 2, leaving 3.
#// ⚠ She is SEEDED rather than played: a unit played this turn cannot attack.
## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_260:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:3
P2BASEDMG:4

---

# Restore2_ClampsAtZero
#// The heal must not go NEGATIVE. One damage on the base and a Restore 2 leaves exactly 0, not −1.
#// Distinct from the section above: that one would pass for an unclamped implementation.
## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_260:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:0
P2BASEDMG:4

---

# Restore2_UnderNewControl_HealsTHEIRBase
#// THE CONTROL-CHANGE CELL. Restore's reminder text says "heal X damage from YOUR base", and under a
#// take-control effect "your" is the NEW CONTROLLER's — the ability resolves for whoever controls the
#// unit, not for whoever owns it. P2 controls a P1-owned Amidala and attacks P1's base.
#// The two readings are separated in BOTH numbers, which is what makes this discriminating rather than
#// merely multiplayer-flavoured: P1's base takes the 4 and stays on 7 while P2's heals 3 -> 1. An
#// owner-scoped heal would read P1 5 and P2 3.
## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:3;theirBaseDamage:3}
SkipPreGame: true
WithActivePlayer: 2
WithP2GroundArenaControlled: HMW_260:1
## WHEN
- P2>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:7
P2BASEDMG:1
