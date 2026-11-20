# OnAttack_DealOne_Survives_GetsExperience
#// IC27_014 Dryden Vos — Thumb-Spiking the Scale (1/4 Ground, Vigilance/Villainy, Underworld, unique)
#// "On Attack: You may deal 1 damage to a friendly unit. If it survives, give an Experience token to it."
#// SWUOfferUnitTarget(side friendly, may) -> IC27_014#0, which deals the 1 (Dryden is the damage source) and
#// then re-finds the unit by UniqueID: only if it is still in play does it get the token. "a friendly unit"
#// has no "another", so Dryden himself is legal; "friendly" spans the team.
#//
#// COVERAGE: offer=Offer_FriendlyUnitsOnly_DrydenIncluded ·
#//           decline=Decline_NothingHappens ·
#//           boundary=TargetDies_NoToken_NotEvenToTheShiftedUnit (1 HP remaining dies) paired with
#//           OnAttack_DealOne_Survives_GetsExperience (survives) ·
#//           control=N/A (no owner-scoped zone, no "your") ·
#//           reqboundary=OnAttack_AnsweredAcrossRequestBoundary ·
#//           modes=2P,TeamSuns (text says "friendly") · TeamSuns=TeamSuns_TeammatesUnit_IsFriendly
#//
#// Dryden attacks P2's base and picks P1's Imperial Dark Trooper (3/3): 1 damage, survives, +1 Experience.

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_014:1:0   # idx 0 — Dryden 1/4
WithP1GroundArena: SEC_080:1:0    # idx 1 — Imperial Dark Trooper 3/3

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2BASEDMG:1
P1NODECISION

---

# Offer_FriendlyUnitsOnly_DrydenIncluded
#// The pool is every friendly unit — Dryden himself, P1's Imperial Dark Trooper and P1's TIE/ln Fighter in
#// space — and no enemy unit. Left pending.

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_014:1:0
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&mySpaceArena-0

---

# Decline_NothingHappens

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_014:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P2BASEDMG:1
P1NODECISION

---

# TargetDies_NoToken_NotEvenToTheShiftedUnit
#// "If it survives" — a Death Star Stormtrooper (3/1) dies to the 1 damage, so no token is given. The
#// Imperial Dark Trooper behind it slides into its index when the arena compacts; it must NOT get the token
#// (the survivor check is by identity, not by position).

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_014:1:0   # idx 0 — Dryden
WithP1GroundArena: SOR_128:1:0    # idx 1 — Death Star Stormtrooper 3/1
WithP1GroundArena: SEC_080:1:0    # idx 2 — Imperial Dark Trooper 3/3

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1

---

# Shield_PreventsTheDamage_UnitSurvives_GetsToken
#// A Shield prevents the 1 damage; the unit survives, so it gets the token. (Judgement call on a preview card:
#// "If it survives" names an outcome, and the unit is still in play.)

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_014:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 1:SOR_T02

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# SelfTarget_TokenLandsBeforeCombatDamage
#// Dryden may pick himself: 1 damage (4 HP), survives, Experience — and On Attack resolves before combat
#// damage, so he hits P2's base for 2, not 1.

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_014:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2BASEDMG:2

---

# OnAttack_AnsweredAcrossRequestBoundary

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_014:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# TeamSuns_TeammatesUnit_IsFriendly
#// Team Suns: teammate P3's unit is in the pool and can take the damage + token; enemies P2/P4 are not offered.

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: IC27_014:1:0
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SEC_080:1:0
WithP4GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:p2Base-0

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&p3GroundArena-0

---

# DamageSource_IsDryden_CorsairMakesItUnpreventable
#// Dryden is the SOURCE of the 1 damage, and he is an Underworld card: with ASH_196 Gorian Shard's Corsair in
#// play the Shield no longer prevents it. The unit takes 1, keeps its Shield, survives, and gets the token.
#// Partner: Shield_PreventsTheDamage_UnitSurvives_GetsToken (same board, no Corsair).

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_014:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 1:SOR_T02
WithP1SpaceArena: ASH_196:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
