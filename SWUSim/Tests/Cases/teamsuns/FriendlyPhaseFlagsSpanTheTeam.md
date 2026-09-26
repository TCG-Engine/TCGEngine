#// TEAM SUNS — "a friendly X happened this phase" spans YOU AND YOUR TEAMMATE.
#//
#// Owner ruling, 2026-09-26: the word "friendly" means the same thing in a phase-history condition as it
#// does in a target pool (spec §2) — a teammate's unit is friendly, you simply do not control it. The
#// pool half was converted in the Phase 3 "friendly" sweep; the FLAG half was never touched, so every
#// "if a friendly unit was defeated this phase" still read only the caster's own seat.
#//
#// The flags (SWU_FRIENDLY_DEFEATED / _LEFT_PLAY / _ATTACKED / _HEROISM_DEFEATED / _UPGRADE_DEFEATED,
#// SWU_REBEL_DEFEATED, SWU_IMPERIAL_DEFEATED, SWU_ATTACKER_DEFEATED) are all stamped on the CONTROLLER
#// of the unit the thing happened to, so a teammate's event lands on the teammate's seat and a self-only
#// read cannot see it. SWUTeamFlagCount() is the one place that now spans the team; outside a team game
#// SWUTeammatesOf() returns [], so Premier and plain Twin Suns are byte-identical.
#//
#// ⚠ EVERY POSITIVE SECTION HERE IS PAIRED WITH A NON-TEAM CONTROL on a byte-identical board. Without
#// the control, a read that simply counted every seat would pass the positives and quietly break
#// four-player Twin Suns, where seat 3 is an ENEMY.

# FriendlyDefeated_TeammatesUnitCounts
#// SEC_083 ISB Shuttle — "When Played: If a friendly unit was defeated this phase, create a Spy token."
#// P2 kills the unit belonging to seat 3, who is P1's TEAMMATE. Nothing of P1's own died. The Spy must
#// still be created.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 2
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: SEC_083
WithP2GroundArena: SOR_039:1:0
WithP3GroundArena: SOR_128:1:0

## WHEN
- P2>AttackGroundArena:0:P3G0
- P3>Pass
- P4>Pass
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P3GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:SEC_083
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_T01

---

# TwinSunsControl_FriendlyDefeated_SelfOnly
#// THE CONTROL — byte-identical to the section above with SWU_MODE_TEAMS REMOVED. In plain 4-player Twin
#// Suns seat 3 is an OPPONENT, so its unit dying is not "a friendly unit was defeated" and ISB Shuttle
#// must create NOTHING. This is what stops the fix from degenerating into "count every seat".

## GIVEN
CommonSetup: ggk/rrk/{myResources:3}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 2
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: SEC_083
WithP2GroundArena: SOR_039:1:0
WithP3GroundArena: SOR_128:1:0

## WHEN
- P2>AttackGroundArena:0:P3G0
- P3>Pass
- P4>Pass
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P3GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:SEC_083
P1GROUNDARENACOUNT:0

---

# FriendlyAttacker_PoolIncludesTheTeammatesUnit
#// LOF_005 Morgan Elsbeth — "Action [Exhaust]: Choose a FRIENDLY unit that attacked this phase."
#// A DIFFERENT mechanism from the sections above: a per-unit flag (SWU_ATTACKED_{uid}) rather than a
#// per-seat counter, so it exercises the other half of the conversion.
#//
#// ⚠ THIS ONE WAS HALF-CONVERTED, which is the worst shape. The POOL had already been widened to
#// SWUFriendlyUnits() by the Phase 3 sweep — with a comment saying so — but the flag beside it was still
#// read on the CASTER's seat, and SWU_ATTACKED_{uid} lives on the ATTACKER's controller's seat. So the
#// teammate's unit entered the pool and was then filtered straight back out. Offer and gate must be one
#// predicate.
#//
#// BOTH of P3's units attack; P1's own SOR_046 does not. The pool must be EXACTLY the teammate's two —
#// which pins both directions: the teammate is IN, and a friendly unit that did not attack is OUT.
#//
#// ⚠ TWO teammate attackers on purpose. With a single candidate SWUQueueChooseTarget AUTO-RESOLVES and
#// runs straight on to the play-a-unit step, leaving nothing pending to assert — the section read as a
#// false RED exactly that way when first written, on a board where the fix was already in.

## GIVEN
CommonSetup: ggk/rrk/{myResources:5; myLeader:LOF_005}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 3
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0

## WHEN
- P3>AttackGroundArena:0:P2B
- P4>Pass
- P1>Pass
- P2>Pass
- P3>AttackGroundArena:1:P2B
- P4>Pass
- P1>UseLeaderAbility

## EXPECT
SEATCOUNT:4
P1SELECTABLEEXACT:p3GroundArena-0&p3GroundArena-1

---

# TwinSunsControl_FriendlyAttacker_PoolIsSelfOnly
#// THE CONTROL — same board, SWU_MODE_TEAMS REMOVED. Seat 3 is now an ENEMY, so no FRIENDLY unit
#// attacked this phase and Morgan's ability finds nothing: the action is still spent (CR: an [Exhaust]
#// cost changes game state) but no choice is offered.

## GIVEN
CommonSetup: ggk/rrk/{myResources:5; myLeader:LOF_005}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 3
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0

## WHEN
- P3>AttackGroundArena:0:P2B
- P4>Pass
- P1>Pass
- P2>Pass
- P3>AttackGroundArena:1:P2B
- P4>Pass
- P1>UseLeaderAbility

## EXPECT
SEATCOUNT:4
P1NODECISION
P1LEADER:EXHAUSTED
