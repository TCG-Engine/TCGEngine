# CollateralDamage_FirstClauseWhenDefeatedWaitsForThenClause
#// LAW_208 Collateral Damage: "Deal 2 to a unit. THEN, deal 2 to a base or another unit in the same arena."
#// P1 defeats P2's Onyx Squadron Brute (2 dmg on it) with the FIRST hit. Per CR 8.29.1 ("then": the first
#// effect resolves as completely as possible before the second) + CR 7.6.14.a/7.6.8 (a When-Defeated
#// TRIGGERS on defeat but only RESOLVES after the current ability fully finishes): P1 must resolve the
#// second "Then" damage (event completes) BEFORE Onyx's When-Defeated (P2 heals a base) resolves.
#// So right after the first hit: P1 is choosing the second target, and P2's heal must NOT be resolvable yet.
## GIVEN
CommonSetup: ngw/ngw/{myLeader:ASH_011:true:false:false:0;myBase:ASH_020;theirLeader:ASH_011:true:false:false:0;theirBase:ASH_020;theirBaseDamage:5}
P1OnlyActions: true
WithP1Resources: 3
WithP2SpaceArena: [JTL_033:1:2 JTL_239:0:0]
WithP1Hand: [LAW_208]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Deal_2_to_a_base_or_another_unit_in_the_same_arena
P2NODECISION

---

# CollateralDamage_WhenDefeatedReleasesAfterThenClause
#// Continue the sequence: once P1 resolves the second "Then" hit (deal 2 to P2's base), the whole event is
#// done, so Onyx's parked When-Defeated is NOW released to P2 (active-player-first, CR 7.6.10).
## GIVEN
CommonSetup: ngw/ngw/{myLeader:ASH_011:true:false:false:0;myBase:ASH_020;theirLeader:ASH_011:true:false:false:0;theirBase:ASH_020;theirBaseDamage:5}
P1OnlyActions: true
WithP1Resources: 3
WithP2SpaceArena: [JTL_033:1:2 JTL_239:0:0]
WithP1Hand: [LAW_208]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2HASDECISION
P2SPACEARENACOUNT:1

---

# CollateralDamage_DeferredTriggerSurvivesRequestBoundary
#// The two "then" clauses are separated by an INTERACTIVE choice — in a real game that's a fresh HTTP
#// request. The parked When-Defeated is stored in SWUVars (serialized), so it must survive the boundary:
#// still released after the second clause (not lost, not fired early).
## GIVEN
CommonSetup: ngw/ngw/{myLeader:ASH_011:true:false:false:0;myBase:ASH_020;theirLeader:ASH_011:true:false:false:0;theirBase:ASH_020;theirBaseDamage:5}
P1OnlyActions: true
WithP1Resources: 3
WithP2SpaceArena: [JTL_033:1:2 JTL_239:0:0]
WithP1Hand: [LAW_208]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirBase-0
## EXPECT
P2HASDECISION
P2SPACEARENACOUNT:1

---

# CollateralDamage_KarisNemikDiscloseWaitsForThenClause
#// The user's sibling example: Karis Nemik's (SEC_148) "When Defeated: you may disclose → create a Spy
#// token" is itself a When-Defeated ability, so it rides the same deferral. Defeating it with Collateral
#// Damage's FIRST hit must NOT offer the disclose until the whole event resolves.
## GIVEN
CommonSetup: ngw/ngw/{myLeader:ASH_011:true:false:false:0;myBase:ASH_020;theirLeader:ASH_011:true:false:false:0;theirBase:ASH_020}
P1OnlyActions: true
WithP1Resources: 3
WithP2GroundArena: [SEC_148:0:0 SEC_148:0:0]
WithP1Hand: [LAW_208]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Deal_2_to_a_base_or_another_unit_in_the_same_arena
P2NODECISION

---

# CollateralDamage_ParkedWhenDefeatedMustNotResolveAfterTheGameIsWON
#// The post-win resolution-halt case, and the parked-trigger deferral is what sets it up.
#// P2's base sits at 28/30 and P2's K-2SO (SOR_145, 4 HP) already has 2 damage. P1's Collateral Damage
#// defeats K-2SO with the first hit — his When Defeated is PARKED until the whole event resolves — and
#// the "Then" hit puts P2's base at exactly 30. The game is decided at that instant: P1 WINS.
#// K-2SO's parked When Defeated must therefore never resolve. It is not cosmetic — it reads "For each
#// opponent, choose one: either deal 3 damage to that player's base, or that player discards a card",
#// and P1's base is at 27/30, so resolving it after the win would put P1 at 30 as well and flip a clean
#// win into a mutual loss. No prompt for either player; P1 is the winner.
#// (K-2SO is the only unit in play, so the first clause's "deal 2 damage to a unit" auto-resolves onto
#// him and takes no answer — the single answer below is the "Then" clause's base pick.)

## GIVEN
CommonSetup: ngw/ngw/{
  myLeader:ASH_011:true:false:false:0;
  myBase:ASH_020;
  myBaseDamage:27;
  theirLeader:ASH_011:true:false:false:0;
  theirBase:ASH_020;
  theirBaseDamage:28
}
P1OnlyActions: true
WithP1Resources: 3
WithP2GroundArena: SOR_145:1:2
WithP1Hand: [LAW_208]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1WIN
P1NODECISION
P2NODECISION
P1BASEDMG:27

---

# CollateralDamage_ParkedWhenDefeatedStillResolvesWhenTheGameIsNOTWon
#// Boundary partner for the section above: identical board except P2's base starts at 27, so the "Then"
#// hit leaves it at 29 — one short of lethal. The game is NOT decided, so K-2SO's parked When Defeated
#// is released as normal and P2 gets its choose. This is what proves the halt above is caused by the WIN
#// and not by the trigger being lost somewhere. (Same auto-resolved first clause as above.)

## GIVEN
CommonSetup: ngw/ngw/{
  myLeader:ASH_011:true:false:false:0;
  myBase:ASH_020;
  myBaseDamage:27;
  theirLeader:ASH_011:true:false:false:0;
  theirBase:ASH_020;
  theirBaseDamage:27
}
P1OnlyActions: true
WithP1Resources: 3
WithP2GroundArena: SOR_145:1:2
WithP1Hand: [LAW_208]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2HASDECISION
P2GROUNDARENACOUNT:0

---

# CollateralDamage_FirstClausePool_AnyUnitEitherSideEitherArena
#// LAW_208 Collateral Damage — "Deal 2 damage to a unit." The first clause carries NO restriction word at
#// all: no controller scope, no arena, no non-leader. The board therefore seats one unit of every kind so
#// the pool has to be the complete set — a friendly ground unit, a friendly SPACE unit, an enemy ground
#// unit, an enemy SPACE unit, and P2's DEPLOYED LEADER (a leader unit is still "a unit", so it must be IN;
#// contrast the second clause, which is arena-scoped, and Double-Cross, which says "non-leader"). Anything
#// missing from this pool is an invented restriction.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: LAW_208

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P2GROUNDARENAUNIT:1:ISLEADERUNIT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0

---

# CollateralDamage_SecondClausePool_SameArenaAnotherUnitOrEitherBase
#// COVERAGE: offer=CollateralDamage_FirstClausePool_AnyUnitEitherSideEitherArena (unrestricted first pool,
#//           leader unit included) + CollateralDamage_SecondClausePool_SameArenaAnotherUnitOrEitherBase
#//           ("another" + same-arena + either base) · decline=N/A (both clauses are mandatory chooses, no
#//           "you may") · control=N/A (no control-change text) · boundary=the parked-When-Defeated pair
#//           (...MustNotResolveAfterTheGameIsWON vs ...StillResolvesWhenTheGameIsNOTWon) plus
#//           FirstClauseWhenDefeatedWaitsForThenClause vs WhenDefeatedReleasesAfterThenClause ·
#//           reqboundary=CollateralDamage_DeferredTriggerSurvivesRequestBoundary.
#// LAW_208 — "Then, deal 2 damage to A BASE or ANOTHER unit in the SAME ARENA." Three restrictions, each
#// with a violator on the board. The first hit lands on P2's SOR_046 (3/7, survives), so: SOR_046 itself is
#// OUT on "another" even though it is still in play; both SPACE units (P1's SOR_237, P2's SOR_225) are OUT
#// on "same arena"; the remaining GROUND units are IN — P1's own SOR_095 (the clause is not controller-
#// scoped) and P2's DEPLOYED LEADER at theirGroundArena-1; and "a base" means EITHER base, so myBase-0 and
#// theirBase-0 are both IN. The existing ...WaitsForThenClause section only reads this decision's tooltip.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: LAW_208

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1HASDECISION
P2GROUNDARENAUNIT:1:ISLEADERUNIT
P1SELECTABLEEXACT:myBase-0&theirBase-0&myGroundArena-0&theirGroundArena-1
