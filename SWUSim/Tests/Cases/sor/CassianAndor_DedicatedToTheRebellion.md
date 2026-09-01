# BaseDamage_Draw
#// COVERAGE: offer=DeployedDrawOffer_IsPoollessAndOnlyTheControllerIsAsked (the reactive read while
#//           still PENDING: the prompt names the ability, its legal-target set is EMPTY — the
#//           executable form of "pool-less" — and P2 is never asked) +
#//           LeaderActionIsNotOfferedOnTheDeployedBody (the clickable unit-action list, which no
#//           answer-path section can see: the front side's leader Action must not reappear on the
#//           deployed body, with SOR_094 Bail Organa seeded as the passing control) +
#//           BaseDamage_Draw + DeclineDraw (the same offer answered both ways)
#//           · decline=DeclineDraw (deployed "you may");
#//           the front action has no decline (using it is the choice; no-op use covered by
#//           LeaderAction_2BaseDamage_NoDraw) · boundary=LeaderAction_2BaseDamage_NoDraw (2, below) +
#//           LeaderAction_3BaseDamage_Draw (3, at threshold) · reqboundary=LeaderAction_PhaseReset_NoDraw
#//           + Deployed_OncePerRound_ResetsNextRound (damage counter + once-per-round flag cross the
#//           regroup's request boundaries and reset exactly once) · control=N/A (both sides live on the
#//           LEADER — leader units never change control, and the reactive/action are seat-bound to it)
#// SOR_013 Cassian Andor (deployed Leader Unit, 4/6) — "When you deal damage to an enemy base: You may
#// draw a card." P1 deploys Cassian (6 resources) and attacks P2's base (Saboteur, 4 power). The base
#// takes 4, and the reactive offers P1 a draw → YES → P1 draws 1 (deck 1 → 0, hand 0 → 1).

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Deck: SOR_128

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4
P1HANDCOUNT:1
P1DECKCOUNT:0
P1LEADER:EPICUSED

---

# DeclineDraw
#// SOR_013 Cassian Andor (deployed) — the draw is optional ("You may"). Cassian deploys and attacks
#// P2's base; the reactive offers a draw, P1 declines (NO) → no card drawn (deck stays 1, hand stays 0).

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Deck: SOR_128

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:4
P1HANDCOUNT:0
P1DECKCOUNT:1
P1LEADER:EPICUSED

---

# OncePerRound
#// SOR_013 Cassian Andor (deployed) — "Use this ability only once each round." Two enemy-base hits in
#// the same round; Cassian's reactive draws only for the FIRST. P1 deploys Cassian (ground) and has an
#// Alliance X-Wing (SOR_237) in space; Cassian attacks P2's base (4) → draw (YES), then the X-Wing
#// attacks P2's base (2) → no second offer. P1 drew exactly 1 (deck 2 → 1, hand 1), base took 4+2=6.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1SpaceArena: SOR_237:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_237

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:6
P1HANDCOUNT:1
P1DECKCOUNT:1
P1LEADER:EPICUSED

---

# LeaderAction_2BaseDamage_NoDraw
#// SOR_013 Cassian Andor — the threshold is 3 (not "any"). P1's Alliance X-Wing (SOR_237, 2 power)
#// deals only 2 to P2's base, below the bar. The leader action is still used — Cassian exhausts and
#// pays 1 resource (1 → 0) — but the condition fails, so NO card is drawn (deck stays 1, hand stays 0).
#// Distinguishes "3 or more" from a buggy ">0" / ">=1".

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1SpaceArena: SOR_237:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:2
P1HANDCOUNT:0
P1DECKCOUNT:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# LeaderAction_3BaseDamage_Draw
#// SOR_013 Cassian Andor (leader) — Action [1 resource, Exhaust]: If you've dealt 3 or more damage to
#// an enemy base this phase, draw a card. P1's Battlefield Marine (SOR_095, 3 power) attacks P2's base
#// for 3, meeting the threshold; P1 then uses the leader action — pays 1 resource (1 → 0), Cassian
#// exhausts, and the condition is met so P1 draws 1 (deck 1 → 0, hand 0 → 1).

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:1
P1DECKCOUNT:0
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# LeaderAction_NoResource_NoOp
#// SOR_013 Cassian Andor — the leader Action costs 1 resource. With 0 ready resources it is a full
#// no-op: the cost can't be paid, so the action never starts — Cassian stays READY (action not spent),
#// nothing is drawn, and no decision is pending. Unaffordable-cost guard.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_128

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1NODECISION
P1HANDCOUNT:0
P1DECKCOUNT:1

---

# LeaderAction_AbilityDamage_Accumulates
#// SOR_013 Cassian Andor (leader) — "dealt 3 or more damage to an enemy base this phase" counts
#// EVENT/ABILITY damage and ACCUMULATES across separate damage events. Two SHD_178 Daring Raids
#// (2 each) hit P2's base for 2+2=4 this phase; neither alone reaches 3, so only the running total
#// unlocks the draw. The leader action then pays 1 (3 → 0 after 1+1+1), Cassian exhausts, P1 draws 1.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SHD_178 SHD_178
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:4
P1HANDCOUNT:1
P1DECKCOUNT:0
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# LeaderAction_PhaseReset_NoDraw
#// SOR_013 Cassian Andor (leader) — "this phase": base damage dealt LAST action phase does not carry
#// over. P1's Battlefield Marine deals 3 to P2's base, then both players pass through the regroup
#// into a NEW action phase; P1 uses the leader action there. The counter was reset at the phase
#// change, so the condition fails: Cassian exhausts and pays 1, but NO extra card is drawn (hand
#// stays at the 2 regroup-draw cards, deck 3 → 1 from the regroup draw only).

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_128 SOR_128 SOR_128]
WithP2Deck: [SOR_128 SOR_128 SOR_128]

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:2
P1DECKCOUNT:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# LeaderAction_OverwhelmToBase_Counts
#// SOR_013 Cassian Andor (leader) — Overwhelm excess counts toward the 3-damage threshold. P1's
#// K-2SO (SOR_145, 4/4 Overwhelm) attacks the 3/1 Death Star Stormtrooper: the defender dies and the
#// 3 excess goes to P2's base — that's ALL the base damage this phase, and it satisfies the "3 or
#// more" condition. The leader action pays 1, Cassian exhausts, and P1 draws 1 (deck 1 → 0).

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SOR_145:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:3
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:3
P1HANDCOUNT:1
P1DECKCOUNT:0
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# LeaderAction_IndirectToOpponent_Counts
#// SOR_013 Cassian Andor (leader) — INDIRECT damage to the enemy base counts toward the threshold.
#// P1 plays JTL_222 Kimogila Heavy Fighter (When Played: 3 indirect to a player) aimed at the
#// opponent; P2 has no units, so all 3 land on P2's base. The leader action then draws: Kimogila is
#// Cunning (uncovered by Aggression/Heroism Cassian + Command base) → 4+2=6, plus 1 for the action
#// = 7 resources → 0 left.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1Hand: JTL_222
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:1
P1DECKCOUNT:0
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# LeaderAction_OwnBaseDamage_NotCounted
#// SOR_013 Cassian Andor (leader) — only damage to an ENEMY base counts. P1 plays JTL_222 Kimogila
#// and aims the 3 indirect at ITSELF, assigning all 3 to its own base (pool: own base + the
#// just-played Kimogila). The leader action is still usable but the condition fails: Cassian
#// exhausts and pays 1, NO card is drawn (deck stays 1, hand 0).

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1Hand: JTL_222
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:You
- P1>AnswerDecision:myBase-0:3
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:3
P2BASEDMG:0
P1HANDCOUNT:0
P1DECKCOUNT:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# Deployed_OverwhelmExcess_Triggers
#// SOR_013 Cassian Andor (deployed Leader Unit) — the "When you deal damage to an enemy base"
#// reactive fires on OVERWHELM excess, not just direct base attacks. Cassian is already deployed
#// (ground index 1); P1's K-2SO (4/4 Overwhelm, index 0) attacks the 3/1 Death Star Stormtrooper —
#// 3 excess hits P2's base and the draw offer fires → YES → P1 draws 1 (deck 1 → 0).

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013:1:1;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_145:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:3
P2GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DECKCOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_013

---

# Deployed_OwnBaseDamage_NoTrigger
#// SOR_013 Cassian Andor (deployed) — damage to YOUR OWN base does not fire the reactive. P1 plays
#// JTL_222 Kimogila aimed at itself and assigns all 3 to its own base (pool: own base + deployed
#// Cassian + Kimogila). No draw offer appears: hand stays 0, deck stays 1, no pending decision.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013:1:1;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: JTL_222
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:You
- P1>AnswerDecision:myBase-0:3

## EXPECT
P1BASEDMG:3
P2BASEDMG:0
P1HANDCOUNT:0
P1DECKCOUNT:1
P1NODECISION

---

# Deployed_OncePerRound_ResetsNextRound
#// SOR_013 Cassian Andor (deployed) — the reactive fires on ANY amount of enemy-base damage (2 here,
#// below the front side's 3-damage bar) and its once-per-round limit RESETS at the next action
#// phase. P1's Alliance X-Wing (2 power) hits P2's base → offer → YES (draw 1). Both players pass
#// through the regroup (each draws 2, declines the optional resource); next phase the readied X-Wing
#// hits the base again → the offer fires AGAIN → YES. Hand 1+2+1=4, P1 deck 5 → 1, base 2+2=4.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013:1:1;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: SOR_237:1:0
WithP1Deck: [SOR_128 SOR_128 SOR_128 SOR_128 SOR_128]
WithP2Deck: [SOR_128 SOR_128 SOR_128]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4
P1HANDCOUNT:4
P1DECKCOUNT:1
P1NODECISION

---

# Deployed_IndirectToOpponent_Triggers
#// Deployed Cassian's "When you deal damage to an enemy base: you may draw" fires for NON-COMBAT
#// damage too: JTL_222's 3 indirect all assigned to P2's base raises the draw offer; YES draws.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:SOR_013:1:1; myResources:4}
P1OnlyActions: true
WithP1Hand: JTL_222
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myBase-0:3
- P1>Drain
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:1
P1DECKCOUNT:1

---

# SimulateRequestBoundary_OncePerRoundFlagAndDrawOffer
#// SOR_013 Cassian Andor (deployed) — the "you may draw" prompt ends the request in production, and the
#// once-per-round flag has to survive both that boundary AND the regroup. Mirrors
#// Deployed_OncePerRound_ResetsNextRound with a boundary inserted before each YES: the pending draw must
#// still resolve after the round-trip, and the reset must still happen exactly once. Hand 1+2+1 = 4,
#// deck 5 → 1, base 2+2 = 4.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013:1:1;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: SOR_237:1:0
WithP1Deck: [SOR_128 SOR_128 SOR_128 SOR_128 SOR_128]
WithP2Deck: [SOR_128 SOR_128 SOR_128]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AttackSpaceArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4
P1HANDCOUNT:4
P1DECKCOUNT:1
P1NODECISION

---

# TwinSuns_ThreeToANYEnemyBaseArmsTheDraw
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — "If you've dealt 3 or more damage to AN enemy base this phase".
#// EXISTENTIAL, and the threshold is PER BASE — never a sum across bases. It read only
#// SWU_BASEDMG_AMT_{OtherPlayer($player)}, i.e. seat 2, so damage dealt to any other seat did not count.
#// Here the ONLY base damaged is SEAT 4's (3 damage), so the draw can only happen if every opponent's
#// tally is checked. Mutation-verified: restricting the loop back to OtherPlayer() reddens this.

## GIVEN
CommonSetup: grw/bbk/{myLeader:SOR_013}
SkipPreGame: true
WithTeams: true
P1OnlyActions: true
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 6
WithP1GroundArena: SOR_046:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:P4B
- P1>UseLeaderAbility

## EXPECT
SEATCOUNT:4
P4BASEDMG:3
P1HANDCOUNT:1

---

# LeaderActionIsNotOfferedOnTheDeployedBody
#// SOR_013 Cassian Andor — THE OFFER-LIST cell, which no other section in this file can see. The front
#// side's "Action [1 resource, Exhaust]" is a LEADER action; the deployed side carries only the
#// reactive "When you deal damage to an enemy base", so once Cassian is on the board his body must NOT
#// appear in the clickable unit-action list. The harness's UseLeaderAbility/UseUnitAbility commands
#// invoke their handlers directly and never consult that list, so an action wrongly registered on the
#// deployed body (or a deployed ability wrongly dropped from it) is invisible to every assertion above.
#// SOR_094 Bail Organa ("Action [Exhaust]: Give an Experience token to another friendly unit") is the
#// PASSING CONTROL: the list must be exactly his mzID, which proves the list was computed rather than
#// empty by construction. Deployed leaders seat at the END of the arena, so Bail is myGroundArena-0
#// and the Cassian body is myGroundArena-1.

## GIVEN
CommonSetup: grw/bbk/{myLeader:SOR_013;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SOR_094:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1UNITACTIONSEXACT:myGroundArena-0
P1GROUNDARENACOUNT:2
P1LEADER:DEPLOYED
P1NODECISION

---

# DeployedDrawOffer_IsPoollessAndOnlyTheControllerIsAsked
#// SOR_013 Cassian Andor (deployed) — WHAT the reactive offers, read while the decision is still
#// pending instead of answered. Cassian deploys and attacks P2's base for 4; the "When you deal damage
#// to an enemy base: You may draw a card" reactive fires. Intended: P1 holds a pending decision whose
#// prompt names the ability, its legal-target set is EMPTY because the draw picks nothing, and P2 is
#// never asked — the offer belongs to the player who dealt the damage, not the player who took it.
#// The empty-set assertion is the executable form of "this card has no target pool": a rebuild that
#// turned the draw into a targeted choice, or that queued the prompt on the damaged seat, would be
#// invisible to BaseDamage_Draw and DeclineDraw, which both answer the decision without inspecting it.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Deck: SOR_128

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Cassian Andor: draw a card?
P1SELECTABLEEXACT:
P2NODECISION
P2BASEDMG:4
P1DECKCOUNT:1
