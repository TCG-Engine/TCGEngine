# KNOWNBUG_UnqualifiedChooseAUnit_CannotSeeTheTeammate
#// ⚠⚠ THIS SECTION IS RED ON PURPOSE. It documents a REAL, LIVE BUG opened by Phase 2 and closed by
#// Phase 3. Do NOT delete, skip, or weaken it — it is the regression guard for the fix.
#//
#// SOR_172 Open Fire reads "Deal 4 damage to a unit." — UNQUALIFIED. Per CR (and the repo's own
#// unqualified-target-words rule) that names no controller, so it may target ANY unit on the table:
#// your own, your teammate's, and both opponents'.
#//
#// THE BUG: target pools are built as my<Arena> + their<Arena> (_SWUCollectUnitTargets side=>'any' ->
#// SWUAllUnits(null)). Phase 2 narrowed their<Arena> to exclude the teammate, but my<Arena> is still
#// only YOUR OWN seat — so a teammate's units are in NEITHER pool and are unreachable by ANY
#// unqualified effect. Measured: the pool comes back as
#//     [myGroundArena-0 & p2GroundArena-0 & p4GroundArena-0]
#// with p3GroundArena-0 (the RED teammate at seat 3) missing entirely.
#//
#// THE FIX is Phase 3's my<Arena> friendly fan-out: once my<Arena> spans the team, 'any' becomes
#// my-team + their-team = every unit on the table and this section goes green with no edit.
#//
#// ⚠ This also shows the spec's §10 phase boundary was wrong: Phase 2 is NOT independently shippable.
#// Narrowing "enemy" without widening "friendly" leaves a hole, so teamsuns must stay disabled (or
#// Phase 3 must land) before anyone plays the format.

## GIVEN
CommonSetup: rrk/bbw/{myResources:5}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: SOR_172
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&p2GroundArena-0&p3GroundArena-0&p4GroundArena-0
