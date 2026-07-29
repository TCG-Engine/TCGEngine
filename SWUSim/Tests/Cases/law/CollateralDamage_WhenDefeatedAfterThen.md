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
