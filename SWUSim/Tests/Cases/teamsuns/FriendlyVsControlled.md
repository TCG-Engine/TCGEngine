# FriendlyOffer_IncludesTheTeammate
#// LAW_151 Profiteering Hunter — "When Played: ANOTHER FRIENDLY unit gets +1/+1 for this phase."
#// The word is "friendly", so under the ruling (spec §2) the pool spans YOU AND YOUR TEAMMATE.
#// This is the acceptance test for converting the 26 `side => 'my'` offer sites to `side => 'friendly'`.
#//
#// The assertion is the EXACT offer set, so it pins both directions at once:
#//   • the TEAMMATE at seat 3 must be IN it   (that is the conversion working)
#//   • both OPPONENTS must be OUT of it       (that is "friendly" still meaning friendly)
#//   • the Hunter itself must be OUT          ("another")
#// ⚠ TWO own units on purpose. With a single legal target the choice AUTO-RESOLVES and there is no
#// pending decision to inspect — the control failed exactly that way when first written.

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
WithP1Hand: LAW_151
WithP1GroundArena: [SOR_046:1:0 SOR_046:1:0]
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&p3GroundArena-0

---

# TwinSunsControl_FriendlyOfferIsSelfOnly
#// THE CONTROL — byte-identical fixture with SWU_MODE_TEAMS REMOVED. In a plain 4-player Twin Suns
#// game "friendly" means only your own board, so seat 3 must NOT be offered. Without this, the section
#// above could pass for a build that simply widened every pool.

## GIVEN
CommonSetup: rrk/bbw/{myResources:5}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: LAW_151
WithP1GroundArena: [SOR_046:1:0 SOR_046:1:0]
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# IBH095_DefeatAFriendlyUnit_OffersTheTeammate
#// IBH_095 You Have Failed Me — "Defeat a friendly unit. If you do, ready a friendly unit with 5 or
#// less power." USER RULING (2026-08-25): in Team Suns you MAY defeat a TEAMMATE's unit to satisfy
#// the "If you do". Both clauses say "friendly", so both span the team.
#//
#// This pins the DEFEAT clause specifically — it is a raw ZoneSearch, not the offer helper, so it was
#// NOT covered by converting the 26 `side => 'my'` sites. Two own units so the choice cannot
#// auto-resolve; the teammate at seat 3 must be in the offer and both opponents must not.

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
WithP1Hand: IBH_095
WithP1GroundArena: [SOR_046:1:0 SOR_046:1:0]
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&p3GroundArena-0

---

# TwinSunsControl_IBH095_DefeatIsSelfOnly
#// THE CONTROL — same fixture without SWU_MODE_TEAMS. In Twin Suns "friendly" is your own board only,
#// so seat 3 must not be offered.

## GIVEN
CommonSetup: rrk/bbw/{myResources:5}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: IBH_095
WithP1GroundArena: [SOR_046:1:0 SOR_046:1:0]
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# Coordinate_DoesNotCountTheTeammatesUnits
#// ⚠ THE MOST IMPORTANT NEGATIVE IN THIS PHASE. TWI_106 has "Coordinate — Ambush (Gain this keyword
#// while YOU CONTROL 3 or more units, including this one.)" — "you control", NOT "friendly", so a
#// teammate's units must NOT count (spec §6.3.1).
#//
#// Seat 1 controls ONE unit and plays TWI_106 => 2 units controlled => Coordinate is OFF.
#// The RED teammate at seat 3 fields THREE units, so a fan-out that leaked into "you control" would
#// see 5 and switch Ambush ON. That is exactly what this section exists to catch.
#//
#// ⚠ MUTATION NOTE — measured 2026-08-25. Flipping SWUControlledUnits() to the team pool does NOT red
#// this section, because Coordinate never routes through the helpers at all: IsCoordinateActive()
#// (KeywordEffects.php) counts GetUnitsInPlay($player), a direct PER-SEAT zone accessor. Coordinate is
#// therefore STRUCTURALLY immune to the my<Arena> fan-out — which is why the friendly split was safe to
#// make without auditing it. The mutation that DOES red this section is rewriting IsCoordinateActive
#// itself to count SWUFriendlyUnits(); verified. Use that one if you need to re-prove this guard.

## GIVEN
CommonSetup: gyw/bbw/{myResources:6}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: TWI_106
WithP1GroundArena: SOR_046:1:0
WithP3GroundArena: [SOR_046:1:0 SOR_046:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:1:NOTKEYWORD:Ambush

---

# Coordinate_CountsYourOwnThreeUnits
#// THE POSITIVE CONTROL for the section above — same card, but seat 1 controls TWO units and plays
#// TWI_106 for a third of its OWN, so Coordinate turns ON. Without this, "Ambush is off" above could
#// pass for a build where Coordinate never works at all.

## GIVEN
CommonSetup: gyw/bbw/{myResources:6}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: TWI_106
WithP1GroundArena: [SOR_046:1:0 SOR_046:1:0]
WithP3GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:2:HASKEYWORD:Ambush
