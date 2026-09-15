# Front_PlaysAFringeUnitAtOneLess_AndWeakensIt
#// HMW_002 Maz Kanata — Eclectic Pirate Queen. Leader (Ground), cost 6, 4/4, [Command][Cunning].
#// Traits: Underworld. Unique.
#// FRONT:  "Action [Exhaust]: Play a Fringe or Underworld unit from your hand. It costs [1 resource]
#//          less. Give a Weakness token to it."
#// EPIC:   "Epic Action: If you control 6 or more resources, deploy this leader."
#// DEPLOY: "Hidden (This unit can't be attacked if she was deployed this phase.)
#//          Action: Play a Fringe or Underworld unit from your hand. It costs [1 resource] less.
#//          Give a Weakness token to it."
#//
#// COVERAGE (front): offer=Front_Offer_FringeOrUnderworldUnitsOnly_AffordableAtTheDiscount ·
#//           decline=Front_Decline_NothingIsPlayed_AndTheLeaderIsStillExhausted ·
#//           boundary=Front_AffordableOnlyBecauseOfTheDiscount /
#//                    Front_OneShortEvenWithTheDiscount_NotOffered ·
#//           no-valid-target=Front_EmptyHand_NoOffer_AndTheLeaderStillExhausts ·
#//           dispatch-path=Front_APilotIsPlayedAsAUNIT_NotAsAnUpgrade ·
#//           interaction=Front_TheWeaknessDefeatsAOneHPUnitAsItArrives ·
#//           reqboundary=RequestBoundary_TheHandChoiceSurvivesIntoThePlay
#// COVERAGE (deployed): Deployed_PlaysAnUnderworldUnitAndWeakensIt_WithoutExhausting ·
#//           Deployed_TheActionIsRepeatable_AndTheSecondWeaknessLandsOnTheSecondUnit ·
#//           Deployed_Hidden_SheCannotBeAttackedThePhaseSheDeploys ·
#//           offer/decline/boundary=shared with the front side — both sides call the SAME pool builder
#//           and the SAME continuation, which is asserted by the two sides producing identical results
#//           on identical hands; the difference between them is the COST, and that is what the two
#//           deployed sections above exist to pin.
#// COVERAGE (epic): Epic_DeployAtSixResources / Epic_BlockedAtFiveResources
#// COVERAGE control=N/A — STRUCTURAL: Maz is a LEADER (front and deployed), and leaders cannot change control;
#//           scope=NormalPlay_NoDiscount_NoWeakness_MazStaysReady (the grant rides the Action only)
#// COVERAGE modes=2P only — "play a unit from YOUR hand" and "give a Weakness token to IT" name no
#//           player and carry no friendly/enemy word; nothing here fans out or narrows by seat.
#//
#// ⚠⚠ THE TWO SIDES DIFFER IN EXACTLY ONE PLACE, AND IT IS NOT IN THE EFFECT. The front costs
#// [Exhaust]; the deployed side is a BARE "Action:" with no cost at all (confirmed against the printed
#// card, both faces). That makes the deployed Action REPEATABLE — hand and resources are the only
#// limit. Charging the front side's [Exhaust] on the deployed side is a documented recurring defect in
#// this exact shape (SHD_013 Han Solo and SHD_016 Fennec Shand both shipped with it), and it turns a
#// repeatable engine into a once-per-turn one while every other section still passes.
#//
#// ⚠ PLAY-FROM-HAND IS DECLINABLE even though no "you may" is printed: the hand is a hidden zone, so a
#// player can never be forced to reveal that they held a playable card (user ruling 2026-08-15).
#// Declining still costs the activation price — here, the front side's exhaust.
#//
#// This section is the plain front-side positive. SOR_210 Swoop Racer is a 4/3 Fringe unit costing 3,
#// Cunning — on-aspect for Maz — so 3 − 1 = 2 is paid out of 5, and the Weakness token (HMW_T02, a
#// -1/-1 Token Upgrade) leaves it a 3/2.

## GIVEN
CommonSetup: gyk/rrk/{myLeader:HMW_002;myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_210

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_210
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:2
P1RESAVAILABLE:3
P1HANDCOUNT:0
P1LEADER:EXHAUSTED

---

# Front_Offer_FringeOrUnderworldUnitsOnly_AffordableAtTheDiscount
#// OFFER CELL, and the only section that can pin the filter. Answering one card proves the branch,
#// never the pool. The hand holds one of every class the wording must and must not reach:
#//   myHand-0  SOR_247 Underworld Thug   Underworld, cost 2, colourless   → IN
#//   myHand-1  SOR_210 Swoop Racer       Fringe,     cost 3, Cunning      → IN
#//   myHand-2  SOR_095 Battlefield Marine  Rebel/Trooper — WRONG TRAIT    → out (and affordable at 4−1
#//             of 5, so it is excluded by trait and not by price)
#//   myHand-3  SOR_222 Waylay            Cunning EVENT, affordable         → out ("a UNIT")
#//   myHand-4  LAW_164 Mercenary Fleet   Underworld but costs 9 → 8 of 5   → out (unaffordable)
#// So the OR is real in both directions, the type gate is real, and the affordability gate is real.
#// ⚠ Affordability is measured with SWUTotalPaymentCapacity, not a bare ready-resource count: a Credit
#// token or a SEC_122 Droid can pay a play cost, and an offer gated on ready resources alone would hide
#// a card the player can genuinely afford.

## GIVEN
CommonSetup: gyk/rrk/{myLeader:HMW_002;myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_247
WithP1Hand: SOR_210
WithP1Hand: SOR_095
WithP1Hand: SOR_222
WithP1Hand: LAW_164

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myHand-0&myHand-1

---

# Front_Decline_NothingIsPlayed_AndTheLeaderIsStillExhausted
#// DECLINE BRANCH. ⚠ Nothing on this card prints "you may", and the offer is still declinable: "play a
#// card from your HAND" is always optional in SWUSim because the hand is a hidden zone and a player
#// cannot be compelled to reveal that they were holding something playable (user ruling 2026-08-15).
#//
#// The other half of that ruling is the price: declining does NOT refund the activation cost, so Maz
#// is exhausted all the same. A mandatory MZCHOOSE here would auto-resolve onto the lone Swoop Racer
#// and play it — which is exactly what a `-` answer must not do.

## GIVEN
CommonSetup: gyk/rrk/{myLeader:HMW_002;myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_210

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:5
P1LEADER:EXHAUSTED

---

# Front_EmptyHand_NoOffer_AndTheLeaderStillExhausts
#// NO-VALID-TARGET CELL. With nothing in hand there is no offer at all — no prompt is raised and no
#// resources move — but the Action was still ACTIVATED, so the exhaust is spent.
#//
#// That is the CR 6.4.587.c reading and it is deliberate: the front side's cost is [Exhaust], which is
#// state-changing, so the ability stays usable with no legal target and simply fizzles. (Same call as
#// HMW_003 Doctor Hemlock's front side in this set.)
#//
#// ⚠ MEASURED: no per-card mutation can red this section — the empty-pool fizzle lives in the SHARED
#// SWUOfferDiscountPlay helper, so there is nothing card-specific to break. It stands as the guard that
#// this card reaches that helper at all and does not crash or hang on an empty hand;
#// Front_OneShortEvenWithTheDiscount_NotOffered is the same shape with a NON-empty hand and does bite
#// (measured: it reds when the offer's affordability gate is loosened).

## GIVEN
CommonSetup: gyk/rrk/{myLeader:HMW_002;myResources:5}
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:5
P1LEADER:EXHAUSTED

---

# Front_AffordableOnlyBecauseOfTheDiscount
#// BOUNDARY PAIR, low half. The Swoop Racer costs 3 and there are only TWO resources — so the card is
#// playable if and only if the discount is real, and it must be exactly 1: at −1 the price is 2 and
#// every resource is spent.
#//
#// P1RESAVAILABLE:0 is what pins the SIZE of the discount rather than merely its existence. A discount
#// of 2 would leave 1 resource behind; a discount of 0 would leave the card in hand.

## GIVEN
CommonSetup: gyk/rrk/{myLeader:HMW_002;myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_210

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_210
P1GROUNDARENAUNIT:0:POWER:3
P1RESAVAILABLE:0
P1HANDCOUNT:0

---

# Front_OneShortEvenWithTheDiscount_NotOffered
#// BOUNDARY PAIR, high half — one resource fewer than the section above. 3 − 1 = 2 against a single
#// resource, so the Racer is not affordable even discounted and must not be OFFERED: the Action
#// fizzles with the card still in hand.
#//
#// Without this partner an offer that ignored affordability entirely would pass the section above, and
#// the player would be handed a card they cannot pay for — the play then fails and the Action is spent
#// for nothing.

## GIVEN
CommonSetup: gyk/rrk/{myLeader:HMW_002;myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_210

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED

---

# Front_TheWeaknessDefeatsAOneHPUnitAsItArrives
#// INTERACTION CELL. Weakness is a −1/−1 Token Upgrade, so a unit with 1 HP is at 0 remaining the
#// instant it is given one — and nothing defeats a shrunk unit on its own. The continuation has to run
#// the state-based shrink sweep, or ASH_190 Peridea Bandit (4/1) sits on the board as a 3/0.
#//
#// The Bandit is Cunning/Villainy against Maz's Command/Cunning board: Cunning is covered, Villainy is
#// not, so it costs 2 + 2 = 4, minus 1 = 3 of 5. The 2 resources left over are the control that proves
#// the play actually happened and the section is not passing because nothing was played at all.

## GIVEN
CommonSetup: gyk/rrk/{myLeader:HMW_002;myResources:5}
P1OnlyActions: true
WithP1Hand: ASH_190

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1RESAVAILABLE:2

---

# Front_APilotIsPlayedAsAUNIT_NotAsAnUpgrade
#// DISPATCH-PATH CELL. The ability says "play a Fringe or Underworld UNIT", so a card with Piloting
#// taken through Maz must enter as a UNIT and may not be routed down the pilot-as-upgrade path — even
#// with a legal host sitting right there.
#//
#// JTL_215 BoShek is Fringe + Pilot, 3/4, cost 3 Cunning (on-aspect → 3 − 1 = 2 of 5), and P1's space
#// arena holds SOR_237 Alliance X-Wing, a Vehicle with no pilot. BoShek lands in the GROUND arena as a
#// unit carrying his Weakness (2/3), the X-Wing gains nothing, and no play-mode prompt is raised.

## GIVEN
CommonSetup: gyk/rrk/{myLeader:HMW_002;myResources:5}
P1OnlyActions: true
WithP1Hand: JTL_215
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_215
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:3

---

# Deployed_PlaysAnUnderworldUnitAndWeakensIt_WithoutExhausting
#// ⚠ THE DEPLOYED SIDE'S COST IS THE HEADLINE. The front side reads "Action [Exhaust]"; the deployed
#// side reads a bare "Action:" — no cost of any kind. So Maz uses her deployed ability and stays READY,
#// free to attack or use it again in the same phase.
#//
#// Charging the front side's exhaust here is the default behaviour of the unit-Action dispatcher, and
#// it is a documented recurring defect in exactly this shape (SHD_013 Han Solo, SHD_016 Fennec Shand
#// both shipped with it). This section is the cheap half of the guard; the repeatability section below
#// is the sharp one.
#//
#// Deploying costs nothing (the 6 resources are a CONDITION, not a price), so all 6 survive the deploy
#// and only the Thug's discounted 1 is spent. SOR_247 Underworld Thug is 2/3 and colourless — no
#// aspect penalty from either side — so it is exactly 2 − 1 = 1, and weakened it reads 1/2.

## GIVEN
CommonSetup: gyk/rrk/{myLeader:HMW_002;myResources:6}
P1OnlyActions: true
WithP1Hand: SOR_247

## WHEN
- P1>DeployLeader
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_002
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:CARDID:SOR_247
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:HMW_T02
P1GROUNDARENAUNIT:1:POWER:1
P1GROUNDARENAUNIT:1:HP:2
P1RESAVAILABLE:5

---

# Deployed_TheActionIsRepeatable_AndTheSecondWeaknessLandsOnTheSecondUnit
#// ⚠⚠ THE SHARPEST SECTION ON THIS CARD, and it pins TWO defects at once.
#//
#// (1) REPEATABILITY. With no cost printed, the deployed Action may be used again in the same phase.
#//     If the dispatcher's default exhaust is charged, the second use is simply impossible and this
#//     section is the only one that notices.
#//
#// (2) WHICH UNIT GETS THE SECOND TOKEN. "Give a Weakness token to IT" means the unit THIS use just
#//     played. The obvious way to find that unit — mark it as it enters play and then search for the
#//     marker — is wrong, because the marker lasts the whole PHASE: the second use finds the FIRST
#//     Thug again and stacks a second Weakness on it while the unit actually played takes none. That
#//     is the documented failure of this exact family (SHD_013 Han Solo dealt its 2 damage to the wrong
#//     unit for exactly this reason, and it stayed invisible while its deployed Action wrongly
#//     self-exhausted — one use per turn can never expose it). The fix is a before/after snapshot of
#//     what was already in play, AND the marker to disambiguate when one play puts several units on
#//     the board.
#//
#// Two identical Thugs make the wrong answer unmistakable: correct is one Weakness each (1/2 and 1/2),
#// the marker-only bug is two on the first (0/1) and none on the second (2/3).

## GIVEN
CommonSetup: gyk/rrk/{myLeader:HMW_002;myResources:6}
P1OnlyActions: true
WithP1Hand: SOR_247
WithP1Hand: SOR_247

## WHEN
- P1>DeployLeader
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:HMW_002
P1GROUNDARENAUNIT:1:CARDID:SOR_247
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:POWER:1
P1GROUNDARENAUNIT:1:HP:2
P1GROUNDARENAUNIT:2:CARDID:SOR_247
P1GROUNDARENAUNIT:2:UPGRADECOUNT:1
P1GROUNDARENAUNIT:2:POWER:1
P1GROUNDARENAUNIT:2:HP:2
P1HANDCOUNT:0
P1RESAVAILABLE:4

---

# Deployed_Hidden_SheCannotBeAttackedThePhaseSheDeploys
#// The deployed side's other printed line. Hidden needs no per-card code — the generator registered
#// 'HMW_002' in $Hidden_Cards and the keyword has generic coverage under Tests/Cases/keywords/ — but
#// nothing else in this file proves the registration actually reached MAZ, and a leader's deployed-side
#// keywords come from a different text field than a unit's.
#//
#// Maz is the only unit P1 controls, so if she were attackable she is what an enemy attack would find.
#// Hidden takes her off the board for the phase she deployed, leaving P1's base as the only legal
#// target — so the Knight of Ren's 4 lands on the base and Maz is untouched.
#//
#// ⚠ This section, and the Epic pair below, were GREEN on the pre-implementation check — correctly so.
#// Both behaviours come from generated data and engine defaults rather than from anything written for
#// this card, so there is no card-specific line to mutate. They are REGRESSION guards: they fail if the
#// generator stops registering Maz's deployed keyword, or if SWUDeployLeader's threshold stops being
#// the leader's printed cost.

## GIVEN
CommonSetup: gyk/rrk/{myLeader:HMW_002;myResources:6;theirResources:4}
WithActivePlayer: 1
WithP2GroundArena: LOF_084:1:0

## WHEN
- P1>DeployLeader
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:4
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_002
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# Epic_DeployAtSixResources
#// EPIC BOUNDARY PAIR, low half. "Epic Action: If you control 6 or more resources, deploy this leader."
#// Maz's printed cost is 6, and SWUDeployLeader's threshold IS the printed cost — so this needs no
#// per-card code. The pair exists so a future change to that default cannot silently alter this card.

## GIVEN
CommonSetup: gyk/rrk/{myLeader:HMW_002;myResources:6}
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_002

---

# Epic_BlockedAtFiveResources
#// EPIC BOUNDARY PAIR, high half — exactly one resource short. Five is not "6 or more", so the deploy
#// must not happen and Maz stays in the leader zone. Without this partner a threshold of 5, or of 0,
#// would pass the section above.

## GIVEN
CommonSetup: gyk/rrk/{myLeader:HMW_002;myResources:5}
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENACOUNT:0

---

# RequestBoundary_TheHandChoiceSurvivesIntoThePlay
#// REQUEST-BOUNDARY CELL. The hand choose ends the request: the discount, the "which unit did I just
#// play" bookkeeping and the pending Weakness all have to survive into a fresh process. Anything the
#// handler parked in an in-memory global between the offer and the answer is empty by the time the
#// unit is played, and the section would show an unweakened unit — or a full-price one — rather than
#// an error.
#//
#// Same GIVEN and same answer as the opening section; only the boundary line is inserted.

## GIVEN
CommonSetup: gyk/rrk/{myLeader:HMW_002;myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_210

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_210
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:2
P1RESAVAILABLE:3

---

# NormalPlay_NoDiscount_NoWeakness_MazStaysReady
#// SCOPE CELL. The discount and the Weakness ride Maz's ACTION only. Playing SOR_247 Underworld Thug
#// (colourless, 2) through the ordinary Play a Card action costs the full 2 of 2 resources, gets no
#// Weakness, and leaves Maz ready.

## GIVEN
CommonSetup: gyk/rrk/{myLeader:HMW_002;myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_247

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_247
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:0
P1LEADER:READY

---

# KilledOnArrivalByTheWeakness_ItsWhenPlayedStillResolves
#// It was PLAYED, so its When Played resolves even though the Weakness defeats it on arrival. SHD_209
#// Criminal Muscle (2/1, Cunning, 1 → 0 with the discount) dies at once, and its "You may return a
#// non-unique upgrade to its owner's hand" still lets P1 take the Shield token off its own Marine.

## GIVEN
CommonSetup: gyk/rrk/{myLeader:HMW_002;myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_209
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDUNIT:0:CARDID:SHD_209
P1TEMPZONECOUNT:0

---

# KilledOnArrivalByTheWeakness_ItsWhenDefeatedResolves
#// …and its When Defeated fires. SHD_164 Rhokai Gunship (2/1 space, Aggression — off-aspect here, so
#// 2 + 2 − 1 = 3 of 3) dies to the Weakness and deals its 1 to P2's base.

## GIVEN
CommonSetup: gyk/rrk/{myLeader:HMW_002;myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_164
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDUNIT:0:CARDID:SHD_164
P2BASEDMG:1
P1RESAVAILABLE:0

---

# KilledOnArrivalByTheWeakness_TheTurnPassesOnce
#// The same Criminal Muscle play without P1OnlyActions (which hides a double turn swap): the action-close
#// gate refuses a second close attempt here, and the turn is P2's exactly once.

## GIVEN
CommonSetup: gyk/rrk/{myLeader:HMW_002;myResources:1}
WithP1Hand: SHD_209
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
TURNPLAYER:2
