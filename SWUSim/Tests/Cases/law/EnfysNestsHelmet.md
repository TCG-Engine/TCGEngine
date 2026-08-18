# OnAttackBuffAnother
#// LAW_186 Enfys Nest's Helmet (Upgrade, +0/+2) — grants "On Attack: You may give another unit +3/+0 for
#// this phase." SEC_080 (index 0) wears the Helmet and attacks the base; on attack P1 gives the other
#// friendly SEC_080 (index 1) +3/+0 → its power becomes 6.

## GIVEN
CommonSetup: brk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_186
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:POWER:6

---

# OnAttackBuffAnother_SurvivesTheRequestBoundary
#// LAW_186 Enfys Nest's Helmet — request-boundary guard. Identical to OnAttackBuffAnother except the game
#// round-trips through serialization (SimulateRequestBoundary) while the "you may give another unit +3/+0"
#// pick is still pending (MZMAYCHOOSE, so it does not auto-resolve even with a single legal target). In a
#// real game that answer arrives in a fresh process, so the pending +3/+0-for-this-phase payload the
#// upgrade's On Attack queued must be serialized state, not a transient in-memory continuation. The other
#// SEC_080 must still end at 6 power.

## GIVEN
CommonSetup: brk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_186
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:POWER:6

---

# OfferPool_AnotherUnitAnySideAnyArena
#// LAW_186 Enfys Nest's Helmet — offer assertion for the granted "On Attack: You may give ANOTHER unit
#// +3/+0 for this phase". The only printed restriction is "another": no controller word and no arena
#// word, so the pool is every unit in play EXCEPT the attached host. Discriminating board — the host
#// SEC_080 wearing the Helmet (must be OUT), a second friendly GROUND unit, a friendly SPACE unit, an
#// enemy GROUND unit and an enemy SPACE unit (all four must be IN). This is the shape that catches a
#// pool silently narrowed to "friendly" or to the host's own arena. The pick is left UNANSWERED so the
#// pending pool can be read.
#// COVERAGE: offer=OfferPool_AnotherUnitAnySideAnyArena (pending SELECTABLEEXACT; the host is the "out",
#//           and both sides x both arenas are the "in") · reqboundary=OnAttackBuffAnother_SurvivesThe
#//           RequestBoundary (the pending +3/+0 payload round-trips through serialization) ·
#//           control=NOT COVERED (the buff is a phase-duration effect on the chosen unit; no section
#//           takes control of the host or the target mid-effect) · boundary pair=OfferPool_AnotherUnit
#//           AnySideAnyArena (host excluded from a 5-unit board) vs OnAttackBuffAnother (the single
#//           legal target is the one other friendly unit) · decline=NOT COVERED (MZMAYCHOOSE, so it can
#//           be declined; no decline section exists yet)

## GIVEN
CommonSetup: brk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_186
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_178:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SEC_213:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:LAW_186

---

# AttachPool_NonVehicleEitherSide_VehiclesExcluded
#// LAW_186 Enfys Nest's Helmet — "Attach to a non-Vehicle unit." The restriction names a TRAIT and no
#// controller, so the legal-host pool is every non-Vehicle unit on either side, and Vehicles are out.
#// Discriminating board: friendly SEC_080 (non-Vehicle) and enemy SOR_046 (non-Vehicle) must be IN;
#// friendly SEC_214 Skyhopper Canyon Runner and enemy ASH_261 Noti Mobile Pod (both Vehicle) must be OUT.
#// Playing the Helmet from hand with four possible hosts keeps the attach choose genuinely pending — the
#// existing sections all seed the upgrade directly and never exercise the attach path at all.

## GIVEN
CommonSetup: brk/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SEC_214:1:0]
WithP2GroundArena: [SOR_046:1:0 ASH_261:1:0]
WithP1Hand: LAW_186

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# DeclineTheBuff_NothingChanges
#// LAW_186 Enfys Nest's Helmet — the granted ability is "You MAY give another unit +3/+0", so the pick is
#// declinable (MZMAYCHOOSE) and declining is a legal, complete resolution: the attack still happens and
#// every unit keeps its printed power. Same board as OnAttackBuffAnother, which takes the other branch.

## GIVEN
CommonSetup: brk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_186
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:1:POWER:3
P2BASEDMG:3

---

# BuffIsPhaseDuration_GoneNextActionPhase
#// LAW_186 Enfys Nest's Helmet — "+3/+0 for THIS PHASE". The buffed unit is 6 power during the phase the
#// Helmet's On Attack resolved in (asserted by OnAttackBuffAnother) and must be back to its printed 3
#// once the next action phase starts. Both decks are seeded so the regroup draws do not add the CR 6.1
#// empty-deck damage on top of the attack.
#// The pass chain is P1 pass -> both resource passes -> P2 pass: P2 auto-claims initiative when it passes
#// first, so its trailing pass is what actually opens the next action phase.

## GIVEN
CommonSetup: brk/rrk/{}
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_186
WithP1GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass

## EXPECT
P1GROUNDARENAUNIT:1:POWER:3

---

# StolenHost_GrantResolvesForTheNEWController
#// LAW_186 Enfys Nest's Helmet — the ability is granted to the ATTACHED UNIT, so it travels with the host
#// across a control change: whoever controls the host when it attacks gets the "give another unit +3/+0"
#// offer, read from THEIR seat. The host SEC_080 is owned by P1 (it still wears P1's Helmet) but sits in
#// P2's arena, and P2 attacks with it — the offer is raised on P2's queue and its pool is every unit
#// except the host, so P2's own second unit and P1's unit are both in.

## GIVEN
CommonSetup: brk/rrk/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArenaControlled: SEC_080:1
WithP2GroundArenaUpgrade: 0:LAW_186
WithP2GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P2SELECTABLEEXACT:myGroundArena-1&theirGroundArena-0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:LAW_186
