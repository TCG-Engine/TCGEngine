# Deploy_OnAttack_DealDamage
#// COVERAGE: offer=Deployed_OnAttack_Offer_SpansBOTHSidesAndIncludesHimself (unqualified "a unit")
#//           decline=Deploy_OnAttack_Decline · boundary=LeaderAction_NoVillainyCard /
#//           LeaderAction_VillainyPlayed (the played-a-Villainy-card gate, as a pair)
#//           control=N/A - STRUCTURAL: neither side names an owner-scoped zone, and a leader cannot be
#//           taken control of, so there is no owner-vs-controller reading to test.
#//           reqboundary=SimulateRequestBoundary_MidAttackTriggerTarget
#//           modes=2P,TwinSuns=TwinSuns_MayPickYOUROWNBase + TwinSuns_CanPickAFarSeatsBase
#//           ("deal 1 damage to A BASE" is unqualified, so it spans every seat's base)
#// SOR_010 Darth Vader — Deployed: OnAttack YES → deal 2 damage to a unit.

## GIVEN
CommonSetup: rrk/grw/{myResources:7}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:5
P2GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:EPICUSED

---

# Deploy_OnAttack_Decline
#// SOR_010 Darth Vader — Deployed: OnAttack NO → no extra damage.

## GIVEN
CommonSetup: rrk/grw/{myResources:7}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:5
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EPICUSED

---

# LeaderAction_NoVillainyCard
#// SOR_010 Darth Vader — Leader Action: No Villainy card played → exhaust + spend resource, no damage.

## GIVEN
CommonSetup: rrk/grw/{myResources:1}
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0
P1LEADER:EXHAUSTED
P1RESCOUNT:1
P1RESAVAILABLE:0

---

# LeaderAction_VillainyPlayed
#// SOR_010 Darth Vader — Leader Action: Villainy card played → deal 1 to unit + 1 to base.
#// SOR_128 (Death Star Stormtrooper) is Villainy, cost 1.
#//
#// ⚠ UPDATED 2026-08-27: this section gained one answer. "…and 1 damage to A BASE" is UNQUALIFIED — it
#// names no controller, so YOUR OWN base is a legal target too, exactly like the unit half above (whose
#// offer already spans both arenas). The code used to deal to the opponent's base unconditionally, which
#// silently removed a real choice in Premier as well as naming one seat above two. The answer below picks
#// the enemy base, so the asserted OUTCOME is unchanged — only the decision is now actually made.
#// The sibling section TwinSuns_MayPickYOUROWNBase covers the other half of the offer.

## GIVEN
CommonSetup: rrk/grw/{myResources:2;handCardIds:SOR_128}
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:1
P1LEADER:EXHAUSTED
P1RESCOUNT:2
P1RESAVAILABLE:0

---

# SimulateRequestBoundary_MidAttackTriggerTarget
#// SOR_010 Darth Vader — the deployed OnAttack "deal 2 damage to a unit" choose ends the request in
#// production: the attack is mid-resolution (base damage still owed) when the prompt goes out, so the
#// answer arrives in a fresh process that must rebuild the attack context from the serialized
#// gamestate. Mirrors Deploy_OnAttack_DealDamage with the boundary inserted before the answer.

## GIVEN
CommonSetup: rrk/grw/{myResources:7}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:5
P2GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:EPICUSED

---

# TwinSuns_MayPickYOUROWNBase
#// ⚠ The other half of the unqualified target, and the section that proves the offer is not enemy-only.
#// "Deal 1 damage to a unit and 1 damage to A BASE" — no controller is named, so the caster may choose
#// their OWN base. Before 2026-08-27 this was impossible: the handler dealt to GetOpponent() with no
#// choice at all. Here P1 picks their own base and the enemy base takes nothing.
#// (Niche in Premier, but it is the printed text — and the same reading is what makes the seat fix
#// correct above two seats, where "the opponent's base" is not even a well-defined thing.)

## GIVEN
CommonSetup: rrk/grw/{myResources:2;handCardIds:SOR_128}
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myBase-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1BASEDMG:1
P2BASEDMG:0
P1LEADER:EXHAUSTED

---

# TwinSuns_CanPickAFarSeatsBase
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — the seat half of the same fix.
#// Once "a base" is genuinely unqualified, the offer is myBase-0 plus EVERY opponent's base, so a
#// far-seat base is reachable. The old code dealt to GetOpponent(), which names one seat and returns
#// null above seat 2 — so a far-seat Vader damaged nothing at all.
#// P1 picks SEAT 4's base: it takes 1, seat 2's takes NOTHING (the legacy answer), and P1's own is clean.
#// P3 is a teammate; its base is still a legal target because the text says "a base", not "an enemy base".
## GIVEN
CommonSetup: rrk/grw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 2
WithP1Hand: SOR_128
WithP2GroundArena: SOR_095:2:0
## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
- P1>AnswerDecision:p2GroundArena-0
- P1>AnswerDecision:p4Base-0
## EXPECT
SEATCOUNT:4
P4BASEDMG:1
P2BASEDMG:0
P1BASEDMG:0

---

# Deployed_OnAttack_Offer_SpansBOTHSidesAndIncludesHimself
#// THE OFFER CELL for the deployed side, which had only take/decline sections — both of which prove the
#// BRANCH and neither the POOL.
#// "You may deal 2 damage to A UNIT" carries no controller word and no "another", so the pool is every
#// unit on the board: P1's own Marine, the enemy, AND VADER HIMSELF. Pointing it at your own board is a
#// legal if unusual play, and self-targeting is legal because the text does not say "another".
#// A pool narrowed to enemies (the obvious reading of a Villainy leader's ping) satisfies both existing
#// deployed sections, since both aim at the enemy.
#// A deployed leader is appended LAST to the ground arena, so P1 reads [SOR_095, Vader].

## GIVEN
CommonSetup: rrk/grw/{myResources:7}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0

---

# Deployed_OnAttack_KillsTheDefender_AttackThenDealsNothing
#// THE TIMING CELL. The On Attack window resolves BEFORE combat damage, so 2 damage aimed at the
#// declared defender can remove it before the attack lands — and an attack whose defender has left play
#// deals no damage and takes no counter-damage.
#// Vader (5/8) attacks SOR_128 Death Star Stormtrooper (3/1); the ping kills it outright. If the order
#// were reversed, combat would kill it first, the enemy would still be gone, and the 3-power counter
#// would have put 3 damage on Vader — so DAMAGE:0 on Vader is what separates the two orderings.
#// Neither existing deployed section can see this: both attack the BASE, where nothing can die first.
## GIVEN
CommonSetup: rrk/grw/{myResources:7}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_010
P1GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0
