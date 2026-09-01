# Deploy_DebuffExpiresBeforeRegroupSweep
#// COVERAGE: offer=LeaderAction_Offer_SpansBOTHSides (pending pick: every unit in play, both sides and
#//           both arenas) · decline=N/A (no "you may" on either side — the leader Action's target pick
#//           is mandatory once the action is taken, and the deployed side is a static replacement
#//           effect with nothing to accept or refuse) · control=N/A (a leader unit never changes
#//           control, and the +0/+2 is a phase-duration buff that expires with the phase rather than
#//           tracking a controller — LeaderAction_BuffIsForTHISPhaseOnly) ·
#//           boundary=EpicDeploy_FourResources_Refused (one under the printed 5, no deploy) vs
#//           Deploy_SurvivesLethalInActionPhase / Deploy_DefeatedAtRegroup (deploying at exactly 5),
#//           plus the phase edge in LeaderAction_BuffIsForTHISPhaseOnly ·
#//           reqboundary=SimulateRequestBoundary_DebuffCrossesToRegroupSweep
#// SOR_004 Chirrut Îmwe — interaction of his HP-survival rule with the regroup ordering.
#// P2 deals 4 damage to the deployed Chirrut (3/5) with Open Fire, then shrinks him -2/-2 with
#// Make an Opening (effective HP 3 → no remaining HP). During the action phase he survives (immune).
#// At regroup the -2/-2 debuff is removed BEFORE the defeat sweep, so his HP is back to 5 and 5-4=1
#// remaining HP — Chirrut LIVES. (Targeting him at all relies on the deployed-leader ZoneSearch fix.)

## GIVEN
CommonSetup: gbw/brk/{
  myLeader:SOR_004;
  theirLeader:SOR_011;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 5
WithP2Resources: 6
WithP2Hand: SOR_172
WithP2Hand: SOR_076

## WHEN
- P1>DeployLeader
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>PlayHand:0
- P1>Pass
- P2>Pass

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_004
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:HP:5
P1LEADER:DEPLOYED

---

# Deploy_DefeatedAtRegroup
#// SOR_004 Chirrut Îmwe — Deployed: he survives lethal combat damage during the action phase
#// (see Chirrut_Deploy_SurvivesLethalInActionPhase) but "during the regroup phase, if he has no
#// remaining HP, defeat him." After both players pass, RegroupPhaseStart defeats the over-damaged
#// Chirrut — he leaves the arena and the leader returns NOT deployed.

## GIVEN
CommonSetup: gbw/brw/{
  myLeader:SOR_004;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP2GroundArena: SOR_213:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:0
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P1LEADER:NOTDEPLOYED

---

# Deploy_SurvivesLethalInActionPhase
#// SOR_004 Chirrut Îmwe — Deployed: "During the action phase, this unit isn't defeated by
#// having no remaining HP." Chirrut (3/5) attacks Syndicate Lackeys (5/4); he takes 5 combat
#// damage (HP 5 → no remaining HP) but SURVIVES because it is still the action phase.

## GIVEN
CommonSetup: gbw/brw/{
  myLeader:SOR_004;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP2GroundArena: SOR_213:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_004
P1GROUNDARENAUNIT:0:DAMAGE:5
P1LEADER:DEPLOYED
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# Deployed_DiesToTakedown
#// SOR_004 Chirrut Îmwe — Deployed: he survives lethal combat damage during the action phase
#// (see Chirrut_Deploy_SurvivesLethalInActionPhase) but "during the regroup phase, if he has no
#// remaining HP, defeat him." After both players pass, RegroupPhaseStart defeats the over-damaged
#// Chirrut — he leaves the arena and the leader returns NOT deployed.

## GIVEN
CommonSetup: gbw/bbw/{
  myLeader:SOR_004;
  theirLeader:SOR_004:1:1:1:7;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SOR_077

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2LEADER:NOTDEPLOYED

---

# LeaderAction_BuffUnit
#// SOR_004 Chirrut Îmwe — Leader Action [Exhaust]: Give a unit +0/+2 for this phase.
#// One friendly unit on board → auto-targets it; HP rises by 2 (power unchanged), leader exhausts.

## GIVEN
CommonSetup: gbw/brw/{
  myLeader:SOR_004;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:5
P1LEADER:EXHAUSTED

---

# SimulateRequestBoundary_DebuffCrossesToRegroupSweep
#// SOR_004 Chirrut Îmwe — Open Fire's target prompt ends P2's request in production, and the -2/-2 from
#// Make an Opening then has to survive several more requests before the regroup ordering resolves. Mirrors
#// Deploy_DebuffExpiresBeforeRegroupSweep with a boundary before the Open Fire target answer and another
#// before the debuff event: the 4 damage still lands, the debuff still expires BEFORE the defeat sweep, and
#// Chirrut still lives at 5 HP with 4 damage.

## GIVEN
CommonSetup: gbw/brk/{
  myLeader:SOR_004;
  theirLeader:SOR_011;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 5
WithP2Resources: 6
WithP2Hand: SOR_172
WithP2Hand: SOR_076

## WHEN
- P1>DeployLeader
- P2>PlayHand:0
- P2>SimulateRequestBoundary
- P2>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>SimulateRequestBoundary
- P2>PlayHand:0
- P1>Pass
- P2>Pass

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_004
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:HP:5
P1LEADER:DEPLOYED

---

# EpicDeploy_FourResources_Refused
#// SOR_004 Chirrut Îmwe — "Epic Action: If you control 5 or more resources, deploy this leader."
#// Every deploy in this file runs at 5 resources, so the threshold was only ever seen from ABOVE the
#// line; this is the negative one resource below it. At 4 the Epic Action does nothing: no leader unit
#// is created, Chirrut stays on the leader zone, and the once-per-game Epic slot is NOT burned by the
#// refused attempt. The resources are untouched — the clause is a threshold, not a cost.

## GIVEN
CommonSetup: gbw/brw/{
  myLeader:SOR_004;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:0
P1LEADER:NOTDEPLOYED
P1LEADER:EPICAVAILABLE
P1RESCOUNT:4
P1RESAVAILABLE:4

---

# LeaderAction_Offer_SpansBOTHSides
#// SOR_004 Chirrut Îmwe — "Action [Exhaust]: Give A UNIT +0/+2 for this phase." The target word is
#// unqualified as to controller, so the pool is every unit in play on either side, in either arena.
#// LeaderAction_BuffUnit has a single friendly unit and auto-targets it, which a friendly-only filter
#// would satisfy just as well. Here P1 has a ground unit and P2 has one in each arena; the pick is left
#// PENDING so the exact pool can be read — all three units, both sides, both arenas.

## GIVEN
CommonSetup: gbw/brw/{
  myLeader:SOR_004;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Give_a_unit_+0/+2_for_this_phase
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirSpaceArena-0

---

# LeaderAction_BuffIsForTHISPhaseOnly
#// SOR_004 Chirrut Îmwe — "+0/+2 FOR THIS PHASE". LeaderAction_BuffUnit only ever reads the buffed
#// stats inside the same action phase, so a buff wired as permanent would pass it. Here the round is
#// played out to the regroup phase after the buff: Battlefield Marine (3/3) is 3/5 while the phase
#// lasts and must be back to its printed 3/3 once the phase has ended.
#// Both decks are seeded so the regroup draw does not run either player out of cards (an empty deck at
#// regroup deals 6 to that player's own base and would confuse the reading).

## GIVEN
CommonSetup: gbw/brw/{
  myLeader:SOR_004;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_046 SOR_237]
WithP2Deck: [SOR_095 SOR_046 SOR_237]

## WHEN
- P1>UseLeaderAbility
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
PHASE:RES
