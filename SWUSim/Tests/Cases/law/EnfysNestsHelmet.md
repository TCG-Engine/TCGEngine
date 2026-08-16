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
