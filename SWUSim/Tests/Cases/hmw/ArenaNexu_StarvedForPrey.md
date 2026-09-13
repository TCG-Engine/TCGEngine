# SelfTarget_GritAddsThree_AndItReadies
#// HMW_182 Arena Nexu, Starved For Prey — Unit (Ground) 2/6, cost 4, [Aggression], Creature, unique.
#// "Grit
#//  On Attack: You may deal 3 damage to a friendly Creature unit (including this one) and ready this unit.
#//  Use this ability only once each round."
#//
#// COVERAGE: offer=Offer_FriendlyCreaturesIncludingItself
#//           decline=Decline_NothingHappens + DecliningSpendsNothing_PoggleReadiesIt_OfferedAgain
#//           boundary=OncePerRound_ASecondAttackIsNotOffered (the budget: 1st use vs 2nd) +
#//                    NextRound_TheBudgetRefreshes (the round edge)
#//           control=N/A (structural — On Attack resolves for the attacker's controller, so "friendly" and
#//                   "this unit" are read from whoever is attacking with it; no owner-scoped zone)
#//           reqboundary=AcrossARequestBoundary (the Nexu rides the continuation by UniqueID)
#//           modes=2P,TeamSuns (text says "a FRIENDLY Creature unit" — TeamSuns_ATeammatesCreatureIsALegalPick)
#//                 · TwinSuns=N/A (no player reference)
#//
#// Grit is registry-wired ($Grit_Cards) — it is what makes the self-target worth it: On Attack resolves
#// before combat damage, so 3 damage on itself is +3 power for THIS attack.
#// ★ "Deal 3 … AND ready this unit" — joined by "and": the ready does not depend on the damage landing
#// (Malakili_PreventsTheThree_ItStillReadies). ★ USER RULING (2026-09-07): "once each round" is spent by
#// USING it; a decline spends nothing. The budget belongs to the COPY.
#//
#// This section: it attacks the base and picks ITSELF — 3 damage, so 2 + 3 = 5 into the base — and it is
#// ready again after the attack.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_182:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:HMW_182
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:READY
P1NODECISION

---

# Offer_FriendlyCreaturesIncludingItself
#// The pool, left pending: FRIENDLY units with the Creature trait, "including this one".
#//   myGroundArena-0  the Nexu itself                  → in
#//   myGroundArena-1  HMW_083 Batcher, a Creature      → in
#//   myGroundArena-2  SOR_128, not a Creature          → out
#//   theirGroundArena-0  an ENEMY Beast (Creature)     → out

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: [HMW_182:1:0 HMW_083:0:0 SOR_128:0:0]
WithP2GroundArena: HMW_T03:0:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# Decline_NothingHappens
#// The printed "You may": declining deals nothing and does not ready it. It swings for its printed 2 and
#// ends the attack exhausted.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_182:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# OncePerRound_ASecondAttackIsNotOffered
#// Used once (itself, 5 to the base, ready again), it attacks a SECOND time the same round: no offer —
#// the round's use is spent. The 3 damage is still on it, so Grit makes it 5 again: 10 in total. It ends
#// exhausted this time.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_182:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:10
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# DecliningSpendsNothing_PoggleReadiesIt_OfferedAgain
#// ★ USER RULING: a DECLINE spends nothing. It attacks and declines (2 to the base, exhausted), HMW_012
#// Poggle's front readies it with 1 damage, and it attacks again — the offer is back.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1;myLeader:HMW_012:1}
P1OnlyActions: true
WithP1GroundArena: HMW_182:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-
- P1>UseLeaderAbility
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Deal_3_damage_to_a_friendly_Creature_unit_and_ready_this_unit
P2BASEDMG:2

---

# NextRound_TheBudgetRefreshes
#// The round edge: used in round one, offered again in round two. (P2 holds the claimed initiative and
#// leads the new round, hence its Pass.) Both decks seeded so the regroup draw is not a deck-out.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_182:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Deal_3_damage_to_a_friendly_Creature_unit_and_ready_this_unit

---

# AcklayCombo_ThreeToTheAcklay_TwoToTheBase
#// The arena pairing: it gives the 3 to HMW_156 Arena Acklay (5/6), which survives and deals 2 to each
#// enemy base. P2's base: Nexu's own 2 + the Acklay's 2 = 4. The Nexu is ready again.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: [HMW_182:1:0 HMW_156:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:HMW_156
P1GROUNDARENAUNIT:1:DAMAGE:3
P1GROUNDARENAUNIT:0:READY
P2BASEDMG:4

---

# Malakili_PreventsTheThree_ItStillReadies
#// LOF_108 Malakili: "If a friendly Creature unit would deal damage to a friendly unit, prevent that
#// damage." The Nexu IS a Creature, so its 3 to the Acklay is prevented — no damage, so the Acklay does
#// not trigger (judge ruling: prevented damage is not dealt). But "and ready this unit" is not an "If you
#// do": the Nexu still readies. P2's base takes only the Nexu's own 2.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: [HMW_182:1:0 HMW_156:1:0 LOF_108:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:0:READY
P2BASEDMG:2

---

# SelfDamageIsLethal_TheAttackDealsNothing
#// With 3 damage already on it (3 remaining), picking itself kills it during its own On Attack: it is
#// gone before combat damage, so the base takes nothing, and there is no unit left to ready.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_182:1:3

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDUNIT:0:CARDID:HMW_182
P2BASEDMG:0

---

# AcrossARequestBoundary
#// The On Attack pick is answered in a fresh process; the Nexu rides the continuation by UniqueID.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_182:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:READY

---

# TeamSuns_ATeammatesCreatureIsALegalPick
#// TEAM SUNS: "friendly" is the team, so seat 3's Beast is in the pool beside the Nexu; the opponents'
#// Beasts (seats 2 and 4) are not.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: HMW_182:1:0
WithP3GroundArena: HMW_T03:0:0
WithP2GroundArena: HMW_T03:0:0
WithP4GroundArena: HMW_T03:0:0

## WHEN
- P1>AttackGroundArena:0:p2Base-0

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&p3GroundArena-0
