# PayThree_DealsPowerToAnEnemyUnit
#// HMW_036 Kelnacca, Solitary Master — Unit, Ground, cost 4, 4/5, [Command][Vigilance], unique,
#// traits Force / Jedi / Wookiee.
#// Text: "Restore 2
#//        When Played: You may pay any number of resources. For every 3 resources paid this way, deal
#//        damage equal to this unit's power to an enemy unit."
#// COVERAGE: offer=OfferIsEnemyUnitsOnly — "an ENEMY unit", asserted as a pending pool with friendly
#//           units on the board that must not appear ·
#//           decline=Decline_NothingPaidNothingDealt, and separately the two cases where the offer must
#//           not be RAISED at all (FewerThanThreeReady_NoOfferAtAll, NoEnemyUnit_NoOfferAtAll) — a
#//           payment that could only fizzle is never offered ·
#//           boundary=FewerThanThreeReady_NoOfferAtAll (2 ready = nothing) vs this section (3 = one
#//           instance) vs PaySix_TwoSeparateInstances (6 = two) — the "for every 3" step function ·
#//           control=N/A — the ability names no owner-scoped zone; "this unit" is the just-played
#//           Kelnacca and "an enemy unit" is relative to his controller, who is also the payer. There
#//           is no window in which control could change: the whole thing resolves inside one When
#//           Played ·
#//           reqboundary=RequestBoundary_TargetSurvivesTheBoundary ·
#//           modes=2P,TeamSuns — the text says "an ENEMY unit", so a teammate's unit must be excluded
#//           (TeamSuns_TeammatesUnitIsNotAnEnemy). No player REFERENCE, so no separate Twin Suns
#//           section: 'side' => 'their' already fans out across every live opponent.
#// ⚠ "PAY ANY NUMBER OF RESOURCES" IS THE SEC_040 EMERGENCY POWERS SHAPE — that card carries the same
#//   sentence ("pay any number of resources. For each resource paid this way, …") and is the house
#//   pattern this one follows: ONE NUMBERCHOOSE over the full range 0..(ready resources). "Any number"
#//   is literal, so the range is NOT clipped to useful multiples — paying 4 or 7 is a legal choice that
#//   simply wastes the remainder (PayFourOrSeven_ExtraResourcesAreBurned), which matters to a player
#//   feeding something that counts EXHAUSTED resources.
#// ⚠ RESOURCES ONLY — no Credit tokens and no SEC_122 Droids. "For every 3 RESOURCES paid this way" is
#//   a SCALED effect, and a Credit is not a resource (CR 3.13): it is a separate token created in the
#//   resource zone, and defeating one pays 1 less rather than becoming a resource paid. USER-CONFIRMED
#//   2026-08-26. Inherited from SEC_040#1 / LOF_255#0 rather than re-decided; shared coverage lives in
#//   Tests/Cases/core/CreditsDoNotScaleResourcePaidEffects.md.
#//
#// P1 plays Kelnacca (cost 4) with 7 resources, pays 3, and deals his power — 4 — to the enemy SOR_046
#// (3/7), which survives. The second offer is declined, leaving 0 ready resources.

## GIVEN
CommonSetup: gbw/gbw/{myResources:7}
P1OnlyActions: true
WithP1Hand: HMW_036
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:3
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_036
P1GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P1RESAVAILABLE:0
P1NODECISION

---

# PaySix_TwoSeparateInstances
#// HMW_036 — "for EVERY 3" is a repeating step, and each instance picks its own target. P1 pays 6 in
#// two goes and splits the damage across two different enemy units, 4 each. Paying six and dealing 8
#// to one target would also be legal (SameEnemyUnitTwice_IsAllowed) — the point here is that the two
#// instances are independent choices, not one doubled hit.

## GIVEN
CommonSetup: gbw/gbw/{myResources:10}
P1OnlyActions: true
WithP1Hand: HMW_036
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: LOF_168:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:6
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:1:CARDID:LOF_168
P2GROUNDARENAUNIT:1:DAMAGE:4
P1RESAVAILABLE:0

---

# SameEnemyUnitTwice_IsAllowed
#// HMW_036 — nothing says the instances must hit different units. Both go into LOF_168 (8/5) for 4 + 4,
#// which is lethal; the arena empties and the card lands in its owner's discard.
#// ⚠ ONLY ONE ENEMY UNIT IS ON THE BOARD, so the mandatory target choose AUTO-RESOLVES for both
#//   instances and there is nothing to answer after the payment. Writing the target picks anyway is not
#//   a harmless extra — a spare answer lands on whatever prompt comes next. Count prompts against
#//   answers.

## GIVEN
CommonSetup: gbw/gbw/{myResources:10}
P1OnlyActions: true
WithP1Hand: HMW_036
WithP2GroundArena: LOF_168:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:6

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:LOF_168
P1RESAVAILABLE:0
P1NODECISION

---

# Decline_NothingPaidNothingDealt
#// HMW_036 — "You MAY pay". Declining the first offer costs nothing: the resources spent on Kelnacca
#// himself are gone, but the three that would have fed the ability are still ready and the enemy is
#// untouched.

## GIVEN
CommonSetup: gbw/gbw/{myResources:7}
P1OnlyActions: true
WithP1Hand: HMW_036
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_036
P2GROUNDARENAUNIT:0:DAMAGE:0
P1RESAVAILABLE:3
P1NODECISION

---

# FewerThanThreeReady_NoOfferAtAll
#// HMW_036 — DECLINE and CANNOT-PAY are different branches. With only 2 resources left after paying
#// for Kelnacca, no full 3 can be paid, so the ability can only fizzle and must not be OFFERED — this
#// asserts the absence of the prompt, not merely the absence of damage.

## GIVEN
CommonSetup: gbw/gbw/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_036
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_036
P2GROUNDARENAUNIT:0:DAMAGE:0
P1RESAVAILABLE:2
P1NODECISION

---

# NoEnemyUnit_NoOfferAtAll
#// HMW_036 — the other fizzle-only case: plenty of resources, but no enemy unit for the damage to go
#// to. Paying would spend 3 for nothing, so no offer is raised and the resources stay ready.
#// A friendly unit is seeded to prove the check is "no ENEMY unit", not "no unit at all".

## GIVEN
CommonSetup: gbw/gbw/{myResources:10}
P1OnlyActions: true
WithP1Hand: HMW_036
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:6
P1NODECISION

---

# OfferIsEnemyUnitsOnly
#// HMW_036 — the OFFER cell. "an ENEMY unit", so the pool must exclude every friendly unit including
#// Kelnacca himself. Two enemy units are seeded so the choose really prompts (with one it would
#// auto-resolve and there would be no pool to inspect) alongside two friendly ones.

## GIVEN
CommonSetup: gbw/gbw/{myResources:7}
P1OnlyActions: true
WithP1Hand: HMW_036
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: LOF_168:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:3

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# PowerIsCurrentNotPrinted_SnokeShrinksTheDamage
#// HMW_036 — "damage equal to THIS UNIT'S POWER" reads CURRENT power. SHD_037 Supreme Leader Snoke
#// gives each enemy non-leader unit -2/-2, so Kelnacca enters at 2/3 and each instance deals 2, not the
#// printed 4. An implementation reading CardPower would deal 4 here.
#// The damage is asserted on LOF_168 (8/5), which survives either way, so the section turns on the
#// NUMBER rather than on whether anything died.

## GIVEN
CommonSetup: gbw/gbw/{myResources:7}
P1OnlyActions: true
WithP1Hand: HMW_036
WithP2GroundArena: SHD_037:1:0
WithP2GroundArena: LOF_168:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:3
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_036
P1GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:1:CARDID:LOF_168
P2GROUNDARENAUNIT:1:DAMAGE:2

---

# TeamSuns_TeammatesUnitIsNotAnEnemy
#// HMW_036 — "an ENEMY unit" is team-scoped. In a 2v2 game seats 1 and 3 are partners, so seat 3's
#// unit is FRIENDLY to Kelnacca's controller and must not be in the pool, while both opposing seats
#// (2 and 4) must be. This cannot pass at two seats: at two seats there is no teammate to exclude.

## GIVEN
WithTeams: true
CommonSetup: gbw/gbw/{myResources:7}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: HMW_036
WithP3GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP4GroundArena: LOF_168:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:3

## EXPECT
P1HASDECISION
P1SELECTABLENOT:p3GroundArena-0

---

# RequestBoundary_TargetSurvivesTheBoundary
#// HMW_036 — the request-boundary cell. The payment YESNO and the target choose are two separate
#// interactive decisions with the loop's state (Kelnacca's UniqueID) riding between them, so in
#// production each answer arrives in a fresh process. Boundaries are inserted at BOTH seams.

## GIVEN
CommonSetup: gbw/gbw/{myResources:7}
P1OnlyActions: true
WithP1Hand: HMW_036
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: LOF_168:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:3
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:CARDID:LOF_168
P2GROUNDARENAUNIT:1:DAMAGE:4
P1RESAVAILABLE:0

---

# CreditsCannotPayAScaledCost_NoOfferAtAll
#// HMW_036 — the SCALED-EFFECT ruling, and the sharpest board for it. P1 holds 4 resources and 3 Credit
#// tokens. Kelnacca costs exactly 4, paid with the resources (the Credit offer is declined with DONE),
#// which leaves ZERO ready resources and THREE Credits.
#// Total payment capacity is therefore 3 — enough, if Credits counted — but a Credit is not a resource
#// (CR 3.13): defeating one pays 1 less, it does not become "a resource paid this way". So no offer is
#// raised at all, and the Credits are still sitting in the resource zone afterwards.
#// This is the board that separates SWUResourceCount(readyOnly) from SWUTotalPaymentCapacity; gating on
#// the latter — which is the RIGHT gate for an ordinary "you may pay N" — would raise the prompt here.

## GIVEN
CommonSetup: gbw/gbw
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Credits: 3
WithP1Hand: HMW_036
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:DONE

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_036
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P1RESAVAILABLE:0
P1CREDITCOUNT:3
P1NODECISION

---

# PayFourOrSeven_ExtraResourcesAreBurned
#// HMW_036 — "ANY number" is literal, and this is the section that pins it. The range offered is
#// 0..(ready resources), NOT 0..(largest useful multiple of 3), so P1 may pay all SEVEN of its
#// remaining resources. Seven buys intdiv(7,3) = TWO instances — the same two that six would have
#// bought — and the seventh resource is simply gone.
#// That is a real choice rather than a trap: exhausting a spare resource matters to anything that
#// counts EXHAUSTED resources (HMW_117 Chewbacca). A range clipped to multiples of 3 would cap the
#// answer at 6 and leave one resource ready, which is what this section would catch.
#// Both instances land on LOF_168 (8/5): 4 + 4 is lethal.

## GIVEN
CommonSetup: gbw/gbw/{myResources:11}
P1OnlyActions: true
WithP1Hand: HMW_036
WithP2GroundArena: LOF_168:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:7

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1RESAVAILABLE:0
P1NODECISION

---

# PayTwoOfAThree_BuysNothingButIsStillSpent
#// HMW_036 — the remainder rule at its sharpest. With 4 ready resources the range runs 0..4, so paying
#// 4 is offered; paying 4 buys ONE instance (intdiv(4,3)) and the 4th resource is burned. Contrast
#// PayThree_DealsPowerToAnEnemyUnit, which buys the same one instance for 3.
#// The pair is what proves the divisor is intdiv rather than a round-up or a per-resource effect.

## GIVEN
CommonSetup: gbw/gbw/{myResources:8}
P1OnlyActions: true
WithP1Hand: HMW_036
WithP2GroundArena: LOF_168:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:4

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LOF_168
P2GROUNDARENAUNIT:0:DAMAGE:4
P1RESAVAILABLE:0
P1NODECISION
