# Decline_NoDamage
#// SOR_097 Admiral Ackbar — the When Played damage is optional ("You may"). Declining (AnswerDecision:-)
#// deals nothing.
#// COVERAGE: offer=Offer_AnyUnitOnTheTable_BothSidesIncludingDeployedLeaders (decision left pending;
#//           the pool is every unit in play on both sides, deployed leader and Ackbar included) ·
#//           decline=Decline_NoDamage ("You may" — declining deals nothing and leaves no decision) ·
#//           control=StolenUnit_CountsForItsNEWController_NotItsOwner (the count follows control, and
#//           the enemy unit in the same arena is excluded) ·
#//           boundary pair=ZeroFriendlyUnitsInTheTARGETSArena (0) +
#//           CountIncludesAckbarHimself_AloneHeDealsExactlyOne (1) +
#//           ADeployedLeaderCountsAsAUnitYouControl_TwoNotOne (2) + GroundCount_DealsDamage (3), with
#//           SpaceArenaCount proving the count is taken from the TARGET's arena, not Ackbar's ·
#//           reqboundary=GroundCount_DealsDamage / SpaceArenaCount — the amount is computed when the
#//           TARGET answer arrives, a request after the play that put Ackbar on the board, and the
#//           count read then already includes him

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_097

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# GroundCount_DealsDamage
#// SOR_097 Admiral Ackbar (Command/Heroism unit, cost 3, 1/4, Rebel/Official) — "Restore 1. When
#// Played: You may deal damage to a unit equal to the number of units you control in its arena."
#// P1 plays Ackbar into a ground arena that already has 2 friendly units → 3 friendly ground units
#// (incl. Ackbar). Targeting an enemy GROUND unit deals 3 (the friendly ground count). LAW_124 (4/7)
#// survives at DAMAGE:3.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_097

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:3
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# SpaceArenaCount
#// SOR_097 Admiral Ackbar — the damage equals the number of units you control in the CHOSEN unit's
#// arena, NOT your total units. P1 has 3 ground units (incl. Ackbar) but only 1 space unit. Targeting
#// an enemy SPACE unit deals 1 (the friendly SPACE count), proving it counts the target's arena.
#// JTL_069 (4/7) survives at DAMAGE:1.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0
WithP1Hand: SOR_097

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:1

---

# Restore1_HealsYourBaseOnAttack
#// Intended: "Restore 1 (When this unit attacks, heal 1 damage from your base.)" — Ackbar's other
#// clause, which no When Played section touches. P1's base starts on 3 damage; Ackbar attacks the enemy
#// base and the Restore heals exactly 1 (3 → 2) while P2's base takes his 1 power. Healing exactly 1
#// and not the whole bar is the quantity half.

## GIVEN
CommonSetup: ggw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: SOR_097:1:0    # Admiral Ackbar 1/4, Restore 1

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:2
P2BASEDMG:1

---

# Offer_AnyUnitOnTheTable_BothSidesIncludingDeployedLeaders
#// Intended: "deal damage to A UNIT" is unqualified — it names no controller and no arena, so every
#// unit in play is a legal target: P1's Battlefield Marine, P1's deployed Leia (a leader is a unit once
#// deployed), Ackbar himself, P1's Alliance X-Wing in space, and both of P2's units. The decision is
#// left PENDING so the pool itself is asserted rather than one branch of it. A pool narrowed to enemy
#// units, or to Ackbar's own ground arena, would fail here while still passing every existing section.
#// (The deployed leader seats after the plain arena lines, so ground reads Marine / Leia / Ackbar.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: JTL_069:1:0
WithP1Hand: SOR_097

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&myGroundArena-2&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0

---

# TargetingYourOWNUnit_IsLegalAndUsesTheSameArenaCount
#// Intended: because "a unit" is unqualified, P1 may aim the damage at their own board — and the count
#// is still "units you control in ITS arena", which is the same number either way. P1's Battlefield
#// Marine (3/3) and Ackbar are the two friendly ground units, so the Marine takes 2 and survives, while
#// P2's Wampa across the table is untouched. Every existing section fires at an enemy, so nothing
#// proved the friendly half of the pool actually resolves.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # friendly Battlefield Marine 3/3
WithP2GroundArena: SOR_164:1:0    # enemy Wampa — must stay clean
WithP1Hand: SOR_097

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# CountIncludesAckbarHimself_AloneHeDealsExactlyOne
#// Intended: Ackbar is himself a unit you control in that arena, so with no other friendly ground unit
#// the amount is 1 — not 0. This is the N=1 floor of the count and the lower half of the pair with
#// GroundCount_DealsDamage (3 friendly ground units → 3). LAW_124 Industrious Team (4/7) survives on
#// 1 damage, so the number is readable rather than being hidden by a defeat.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_097

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# ADeployedLeaderCountsAsAUnitYouControl_TwoNotOne
#// Intended: a deployed leader is a unit you control, so it is counted. Same board as
#// CountIncludesAckbarHimself_AloneHeDealsExactlyOne except that Leia is deployed in the ground arena:
#// the count goes 1 → 2 and Industrious Team takes 2 instead of 1. The two sections differ by nothing
#// but the deployed leader, so the extra point of damage can only come from counting her.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;myLeaderDeployed:true}
P1OnlyActions: true
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_097

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# ZeroFriendlyUnitsInTheTARGETSArena_DealsNothingAndAsksNothingMore
#// Intended N=0 boundary, and the counterpart of the existing SpaceArenaCount (1 friendly space unit →
#// 1 damage). Ackbar lands in the GROUND arena and P1 has no space units at all, so aiming the damage
#// at the enemy Munificent Frigate in SPACE counts zero and deals zero — it does not fall back to
#// Ackbar's own arena (which would deal 1) and it does not fall back to P1's total unit count. Nothing
#// further is asked once the target is answered.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP2SpaceArena: JTL_069:1:0
WithP1Hand: SOR_097

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:1
P1SPACEARENACOUNT:0
P1NODECISION

---

# StolenUnit_CountsForItsNEWController_NotItsOwner
#// Intended: "the number of units YOU CONTROL in its arena" is a control count, not an ownership count.
#// P1 controls a Battlefield Marine that P2 still OWNS, so P1's ground count is Marine + Ackbar = 2 and
#// the enemy Industrious Team takes 2 — one more than CountIncludesAckbarHimself_AloneHeDealsExactlyOne
#// on the same board without it. P2's own Industrious Team sits in the same arena and does NOT count
#// for P1, which is the other half of the reading.
#// (A `Controlled` unit seats before Ackbar is played, so it is index 0 and Ackbar index 1.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_095:2    # P1 CONTROLS it, P2 OWNS it
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_097

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:2
