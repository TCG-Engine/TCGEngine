# AllThreeClauses
#// IC27_046 Vigilant Defense (Event, cost 4, Vigilance/Vigilance, Tactic)
#// "Give a friendly unit +0/+3 and Sentinel for this phase.
#//  Heal 3 damage from a friendly unit or base.
#//  Defeat an enemy unit with 3 or less remaining HP."
#// THREE INDEPENDENT mandatory clauses (Cunning Ploy's chain): each pool is built when its clause runs, and a
#// clause with no legal target falls through to the next. +0/+3 is the registered IC27_046 STAT_BUFF and Sentinel
#// the ready-made SENTINEL grant, both for this phase. "friendly" spans the team (units AND bases); "enemy" is
#// 'their'. "remaining HP" = current HP minus damage, read when clause 3 runs.
#//
#// COVERAGE: offer=Offer_Clause1_FriendlyUnitsOnly + Offer_Clause2_FriendlyUnitsAndOwnBase +
#//           Offer_Clause3_RemainingHpThreeOrLess ·
#//           decline=N/A (no "you may"; all three are mandatory) ·
#//           boundary=Offer_Clause3_RemainingHpThreeOrLess (3 remaining in, 4 out — on a damaged 4/7 and an
#//           undamaged 3/3) ·
#//           control=N/A (no owner-scoped zone) ·
#//           reqboundary=AnsweredAcrossRequestBoundary ·
#//           modes=2P,TeamSuns (text says "friendly" and "enemy") · TeamSuns=TeamSuns_TeammatesUnitAndBaseAreFriendly
#//
#// Clause 1 -> P1's Consular Security Force (3/7 -> 3/10, Sentinel). Clause 2 -> P1's Battlefield Marine
#// (2 damage -> 0). Clause 3 -> the only legal enemy (Imperial Dark Trooper on 2 remaining) auto-resolves.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_046
WithP1GroundArena: SOR_095:1:2
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:1
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:HP:10
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P1NODECISION

---

# Offer_Clause1_FriendlyUnitsOnly

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_046
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# Offer_Clause2_FriendlyUnitsAndOwnBase
#// Clause 2's pool: every friendly unit and P1's base — never the enemy's base or units.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_046
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&myBase-0

---

# Offer_Clause3_RemainingHpThreeOrLess
#// Clause 3's pool, at the boundary: an undamaged Imperial Dark Trooper (3 remaining — in), Industrious Team on
#// 4 damage (3 remaining — in), a second Industrious Team on 3 damage (4 remaining — out). P1 has no unit, so
#// clause 1 fizzles and clause 2's only target (P1's base) auto-resolves.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_046
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: LAW_124:1:4
WithP2GroundArena: LAW_124:1:3

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# HealTheBase
#// Clause 2 may heal P1's base instead of a unit: 5 damage -> 2.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_046
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:2
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# NoFriendlyUnit_LaterClausesStillResolve
#// No friendly unit: clause 1 fizzles, but the heal (base, auto) and the defeat still happen.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_046
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:2
P2GROUNDARENACOUNT:0
P1NODECISION

---

# NoLegalEnemy_ThirdClauseFizzles
#// No enemy unit with 3 or less remaining HP: nothing is defeated and nothing is left pending. (Clause 1
#// auto-resolves onto the lone Marine; clause 2 always holds P1's base, so it is answered.)

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_046
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1NODECISION

---

# EnemyLeaderUnit_CanBeDefeated
#// Value class: an enemy deployed leader unit with 3 or less remaining HP is a legal target (SOR_010 Darth
#// Vader deployed 5/8, on 5 damage -> 3 remaining). It returns to its leader zone.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4;theirLeader:SOR_010:1:1:0:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_046

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2LEADER:NOTDEPLOYED

---

# BuffAndSentinel_ExpireAtEndOfPhase

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_046
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0
- P1>Pass

## EXPECT
PHASEISNOT:MAIN
P1GROUNDARENAUNIT:0:HP:7
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# AnsweredAcrossRequestBoundary

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_046
WithP1GroundArena: SOR_095:1:2
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:1
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:HP:10
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1

---

# TeamSuns_TeammatesUnitAndBaseAreFriendly
#// Team Suns: clause 1's only candidate is teammate P3's unit (auto-resolves onto it — P1 has no unit); clause 2
#// then offers P3's unit and P3's base as well as P1's base.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:4
WithP4Base: SOR_019:0
WithP1Hand: IC27_046
WithP3GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1HASDECISION
P1SELECTABLEEXACT:p3GroundArena-0&myBase-0&p3Base-0
