# Dignitaries_HealScalesWithTheTEAMsOfficials
#// COVERAGE: offer=N/A (these clauses COUNT a pool, they do not offer a target) ·
#//           decline=N/A (no optional branch in either card) ·
#//           boundary=each section is paired with a byte-identical teams-OFF control, which is the
#//           N-vs-N+1 discrimination for a count ·
#//           control=the four sections ARE two control pairs ·
#//           reqboundary=N/A (no state written across a decision; both resolve inside one play)
#//
#// THE TEAM "FRIENDLY" AUDIT (2026-08-26). Phase 3 converted every friendly pool that flowed through
#// ZoneSearch, but GetUnitsInPlay is a PER-SEAT ACCESSOR, not a ZoneSearch — so seventeen cards counting
#// "each friendly X" that way were structurally out of that sweep's reach and stayed self-only.
#// They now go through SWUFriendlyUnitObjects. Its semantics are pinned exhaustively in
#// DevTools/tests/teamsuns_friendly_pools_test.php; these sections are the BEHAVIOURAL proof, sampling
#// the two shapes the group divides into — a scaled effect, and a per-unit count.
#//
#// SEC_102 Renowned Dignitaries — "When Played: Heal 2 damage from your base for each friendly Official
#// unit." She IS an Official herself (New Republic, Official) and the text has no "other", so she counts
#// herself. Seat 3 (the RED teammate) fields a second Official, so the team total is 2 => heal 4,
#// taking seat 1's base from 6 damage down to 2.

## GIVEN
CommonSetup: ggw/bbw/{myResources:10; myBaseDamage:6}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: SEC_102
WithP3GroundArena: SEC_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1BASEDMG:2

---

# TwinSunsControl_Dignitaries_CountsOnlyHerOwnSide
#// THE CONTROL — byte-identical board with SWU_MODE_TEAMS REMOVED. In plain Twin Suns seat 3 is just
#// another opponent, so only Renowned Dignitaries herself is a friendly Official: heal 2, leaving the
#// base on 4. Without this pair the section above would pass for a build that counted every Official
#// on the table.

## GIVEN
CommonSetup: ggw/bbw/{myResources:10; myBaseDamage:6}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: SEC_102
WithP3GroundArena: SEC_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1BASEDMG:4

---

# Grakchawwaa_CountsTheTEAMMATESWookiee
#// The per-unit-count shape. HMW_123 King Grakchawwaa — "When Played: For each OTHER friendly Wookiee
#// unit, resource the top card of your deck." He is a Wookiee himself but the text says OTHER, so he
#// does not count himself — seat 3's Gungi is the only qualifying unit, and exactly one card is
#// resourced. Ten resources go in, six pay for him, one is added => 11.
#// ⚠ The "other" exclusion is load-bearing here: without it he would count himself and resource 2.

## GIVEN
CommonSetup: ggw/bbw/{myResources:10}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_123
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP3GroundArena: LOF_093:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1RESCOUNT:11

---

# TwinSunsControl_Grakchawwaa_NoTeammateNoWookiee
#// THE CONTROL. Teams off, so seat 3's Gungi is an enemy Wookiee and does not count: no other friendly
#// Wookiee exists, nothing is resourced, and the count stays at the ten he was played from.

## GIVEN
CommonSetup: ggw/bbw/{myResources:10}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_123
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP3GroundArena: LOF_093:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1RESCOUNT:10
