# WhenPlayed_HealsUpToThree
#// HMW_037 Bacta Tank (Vigilance/Command, Fortification, cost 1, UPGRADE) —
#// "Fortify (Attach this to your base, not a unit.)
#//  When Played: Heal up to 3 damage from a non-Vehicle unit.
#//  Action [defeat this upgrade]: Put a non-Vehicle unit from your discard pile on top of your deck."
#// COVERAGE: offer=WhenPlayed_HealOfferExcludesVehicles (heal pool) + Action_OfferIsYourOwnNonVehicle
#//           UnitsOnly (discard pool — excludes Vehicles, non-units AND the opponent's pile) ·
#//           negative=those two exclusions + Action_NoLegalTarget_StillPaysTheCost ·
#//           boundary=HealZero / HealOne / HealThree across three sections, plus HealClampsAtZero ·
#//           control=N/A — "your discard pile" is the controller's and an UPGRADE on your own base
#//           cannot change controller (no take-control effect targets a base or its Fortify upgrades);
#//           the heal half names no seat at all · reqboundary=RequestBoundary_AcrossTheHealAmount ·
#//           decline=N/A — see the "up to" ruling below: the soft pass is an AMOUNT of zero, not a
#//           declinable target
#// ⚠ "HEAL UP TO 3" — USER RULING (2026-08-14): the TARGET choice is MANDATORY and the soft pass is
#//   choosing ZERO. So this is a plain MZCHOOSE for the unit and an OPTIONCHOOSE that ALWAYS includes
#//   Heal0 — never a declinable target, and the amount step is never skipped (skipping it once forced
#//   a heal on a player who did not want it).
#// ⚠ "a non-Vehicle unit" carries no friendly/enemy qualifier, so BOTH sides are legal — only the
#//   Vehicle trait excludes. Same reading as HMW_095 Carbonite Chamber's identical phrase.
#// Here: the maximum, on a 3-damage friendly Security Force (3/7 — it must SURVIVE holding 3 damage).
#// ⚠ Every heal section seeds a SECOND legal non-Vehicle unit on purpose. With one legal target the
#// choose AUTO-RESOLVES, the target answer is then eaten by the amount OPTIONCHOOSE, and an
#// unrecognised value there silently takes the FIRST option (Heal0) — so the section would pass or
#// fail for entirely the wrong reason.

## GIVEN
CommonSetup: bgw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: HMW_037
WithP1GroundArena: SOR_046:1:3
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:Heal3

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# WhenPlayed_HealLess_HealOne
#// HMW_037 — "up to" means the player may heal FEWER than the maximum. Heal1 on a 3-damage unit leaves 2.
#// Without this the amount step could be hard-wired to the maximum and every other heal section passes.

## GIVEN
CommonSetup: bgw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: HMW_037
WithP1GroundArena: SOR_046:1:3
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:Heal1

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION

---

# WhenPlayed_HealZero_IsTheSoftPass
#// HMW_037 — the zero case, which under the "up to" ruling is the ONLY soft pass (the target choice is
#// mandatory). The unit is chosen and then healed for nothing.

## GIVEN
CommonSetup: bgw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: HMW_037
WithP1GroundArena: SOR_046:1:3
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:Heal0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# WhenPlayed_HealOfferExcludesVehicles
#// HMW_037 — the heal POOL, left pending. A friendly Trooper and an enemy Trooper are legal; the
#// friendly X-Wing is a VEHICLE and must be excluded. Two legal targets so nothing auto-resolves.
#// This is also what proves the pool spans both sides.

## GIVEN
CommonSetup: bgw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: HMW_037
WithP1GroundArena: SOR_046:1:3
WithP1SpaceArena: SOR_237:1:2
WithP2GroundArena: LAW_124:1:3

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
P1HASDECISION

---

# WhenPlayed_CanHealAnEnemyNonVehicle
#// HMW_037 — and the pool RESOLVES on the enemy side: an enemy Dark Trooper really is healed.
#// A friendly-only implementation passes every other heal section here.

## GIVEN
CommonSetup: bgw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: HMW_037
WithP2GroundArena: LAW_124:1:3
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:Heal2

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:1
P1NODECISION

---

# WhenPlayed_HealClampsAtZero
#// HMW_037 — healing 3 from a unit with only 1 damage lands on exactly 0, never -2.

## GIVEN
CommonSetup: bgw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: HMW_037
WithP1GroundArena: SOR_095:1:1
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:Heal3

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# Fortify_AttachesToTheBaseAndItsActionBecomesAvailable
#// HMW_037 — the FORTIFY clause, proven behaviourally because the harness has no base-upgrade-count
#// assertion: the Tank is PLAYED from hand (it must attach to the base, not to the Trooper standing
#// right there), and then the base Action it hosts is used successfully. That Action only exists if the
#// upgrade really landed on the base. Its cost defeats the upgrade, so the discard ends with the Tank
#// plus nothing else moved out of it — and the deck gains the chosen unit on top.
#// This deliberately chains both remaining clauses; the isolated Action sections below use a seeded
#// upgrade instead so they do not depend on the attach path.
#// ⚠ There is only ONE non-Vehicle unit on the table, so the When-Played TARGET auto-resolves
#// (SWUQueueChooseTarget emits a PASSPARAMETER) and the amount is the FIRST thing actually asked. This
#// section used to lead with a spare "myGroundArena-0", which the amount prompt swallowed — the classic
#// auto-resolve artifact. Exposed by the OPTIONCHOOSE pool validator.

## GIVEN
CommonSetup: bgw/rrk/{myResources:1;discardCardIds:SOR_095}
P1OnlyActions: true
WithP1Hand: HMW_037
WithP1GroundArena: SEC_080:1:2
WithP1Deck: [SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Heal0
- P1>UseBaseAbility
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1DECKTOPCARD:SOR_095
P1DECKCOUNT:3

---

# Action_PutsNonVehicleUnitFromDiscardOnTopOfDeck
#// HMW_037 — the Action, in isolation with the upgrade seeded onto the base. The chosen non-Vehicle unit
#// leaves the discard and becomes the TOP of the deck (asserted as identity, not just a count — a wrong
#// destination like the bottom would keep the count right and the top card wrong).

## GIVEN
CommonSetup: bgw/rrk/{discardCardIds:SOR_095}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_037
WithP1Deck: [SOR_046 SOR_046]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1DECKTOPCARD:SOR_095
P1DECKCOUNT:3
P1DISCARDCOUNT:1

---

# Action_OfferIsYourOwnNonVehicleUnitsOnly
#// HMW_037 — the Action's POOL, left pending, excluding three different things at once: a VEHICLE unit
#// (X-Wing), a NON-UNIT card (Confiscate, an event) and the OPPONENT'S discard pile ("YOUR discard pile"
#// is seat-scoped, unlike the heal's unqualified "a unit"). Two legal Troopers remain so the offer is
#// real. Getting any one of those three wrong passes the positive section unchanged.

## GIVEN
CommonSetup: bgw/rrk/{discardCardIds:SOR_095,SOR_237,SOR_251,SEC_080;theirDiscardCardIds:SOR_095}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_037
WithP1Deck: [SOR_046 SOR_046]

## WHEN
- P1>UseBaseAbility

## EXPECT
P1SELECTABLEEXACT:myDiscard-0&myDiscard-3
P1HASDECISION

---

# Action_NoLegalTarget_StillPaysTheCost
#// HMW_037 — "defeat this upgrade" is a COST and is paid even when the effect fizzles: with only a
#// Vehicle and an event in the discard there is no legal unit, and the Tank is defeated anyway with the
#// deck untouched. Asserting the STATE (deck size and top card unchanged, discard grown by the defeated
#// upgrade) rather than "nothing happened" is what makes this load-bearing — same discipline as
#// HMW_095's Action_NoLegalTargetStillPaysTheCost.
#// (Green before implementation — an absence guard.)

## GIVEN
CommonSetup: bgw/rrk/{discardCardIds:SOR_237,SOR_251}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_037
WithP1Deck: [SOR_046 SOR_095]

## WHEN
- P1>UseBaseAbility

## EXPECT
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_046
P1NODECISION

---

# RequestBoundary_AcrossTheHealAmount
#// HMW_037 — the request-boundary cell, on the sharpest link: the chosen UNIT is picked in one request
#// and the AMOUNT in the next, so the target must ride the continuation (as a UniqueID) rather than sit
#// in memory. Same flow and assertions as WhenPlayed_HealsUpToThree with the boundary between the two.

## GIVEN
CommonSetup: bgw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: HMW_037
WithP1GroundArena: SOR_046:1:3
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:Heal3

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
