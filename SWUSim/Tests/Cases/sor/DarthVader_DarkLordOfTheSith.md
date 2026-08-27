# Deploy_OnAttack_DealDamage
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
