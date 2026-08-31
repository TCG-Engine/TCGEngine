# CORE — Team Suns scope: who is an opponent, and which zones "their"/"team" reach.
#
# Teams are SEAT PARITY: seats 1+3 are one team, 2+4 the other (`SWUTeamOf` = `$seat % 2`). Outside a
# team game every helper is inert — `SWUTeamOf` returns the seat itself, so each player is their own
# team and Twin Suns / Premier degenerate to current behaviour.
#
# `OpponentsOf()` is the single cascade point the engine documents: `SWUGetAllValidAttackTargets`,
# ZoneSearch's `their<Zone>` fan-out, "each opponent" effects, `SWUQueueChooseOpponent`'s eligible pool,
# `SWUOpponentsWithCards` and the blast counter all read it. So a teammate leaking into that one list
# leaks everywhere at once — which is why this file asserts the list DIRECTLY rather than through a
# card, and pairs every team assertion with the SAME BOARD in non-team Twin Suns.
#
# The three zone scopes, from seat 1's view, are deliberately different sizes so no two can be confused:
#   my<Zone>    → seat 1 only               (self, in every format)
#   team<Zone>  → seats 1 + 3               (self + live teammates)
#   their<Zone> → seats 2 + 4               (live opponents, teammate EXCLUDED)

---

# TeamsAreSeatParity_OpponentsExcludeTheTeammate
#// The core claim, asserted on the list itself. Seat 1's opponents are 2 and 4 — never 3.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
## WHEN
## EXPECT
SEATCOUNT:4
OPPONENTSOF:1:2,4
OPPONENTSOF:2:1,3
OPPONENTSOF:3:2,4
OPPONENTSOF:4:1,3

---

# TwinSuns_NoTeams_EveryOtherSeatIsAnOpponent
#// THE CONTROL. Identical four-seat board with WithTeams omitted: every other seat is an opponent. If
#// this and the section above ever agree, the team filter has either stopped applying or started
#// applying to non-team games.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
## EXPECT
SEATCOUNT:4
OPPONENTSOF:1:2,3,4
OPPONENTSOF:3:1,2,4

---

# ZoneScopes_My_Team_Their_AreThreeDifferentSets
#// One ground unit on every seat, so each scope returns a different count and none can be mistaken for
#// another: my=1 (seat 1), team=2 (seats 1+3), their=2 (seats 2+4).
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
## WHEN
## EXPECT
ZONESEARCH:1:myGroundArena:1
ZONESEARCH:1:teamGroundArena:2
ZONESEARCH:1:theirGroundArena:2

---

# ZoneScopes_NonTeamFourSeat_TheirReachesEveryoneElse
#// The same board without teams: "their" now spans all three opponents, and "team" degenerates to
#// "my". This is the pair that proves the team filter is what moved seat 3, not some counting change.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
## WHEN
## EXPECT
ZONESEARCH:1:myGroundArena:1
ZONESEARCH:1:teamGroundArena:1
ZONESEARCH:1:theirGroundArena:3

---

# ZoneScopes_TeammateElimination_ShrinksTeamNotTheir
#// Seat 3 (the teammate) is eliminated. "team" falls back to just seat 1 while "their" is untouched at
#// seats 2+4 — the two scopes must move independently, and a live-seat filter applied to the wrong one
#// would show up here as the counts swapping which side lost a member.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithLiveSeats: 124
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
## WHEN
## EXPECT
SEATLIVE:3:false
OPPONENTSOF:1:2,4
#// ⚠ Seat 2's list is what makes this section DISCRIMINATING. Everything above it happens to hold with
#// the team filter disabled too (seat 3 is dead either way), so on its own the section was insensitive
#// to the very thing it is named for — measured by mutating SWUTeamOf to ignore teams and watching it
#// stay green. Seat 2 still has a LIVE teammate in seat 4, so its opponents are [1] with teams and
#// [1,4] without.
OPPONENTSOF:2:1
ZONESEARCH:1:teamGroundArena:1
ZONESEARCH:1:theirGroundArena:2

---

# ZoneScopes_OpponentElimination_ShrinksTheirNotTeam
#// The mirror: seat 2 (an opponent) is eliminated. Now "their" drops to seat 4 alone while "team" keeps
#// both 1 and 3.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithLiveSeats: 134
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
## WHEN
## EXPECT
SEATLIVE:2:false
OPPONENTSOF:1:4
ZONESEARCH:1:teamGroundArena:2
ZONESEARCH:1:theirGroundArena:1

---

# TurnRotationIgnoresTeams
#// Teams change WHO IS AN OPPONENT, not the seating. The turn still goes round the table 1 → 2 → 3 → 4;
#// it does not hop between teammates or play the teams alternately. Asserted here because it is the
#// obvious wrong implementation and nothing else in the suite pins it.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
## WHEN
- P1>Pass
## EXPECT
TURNPLAYER:2

---

# TurnRotationIgnoresTeams_FromTheTeammateSeat
#// And from seat 3, the next seat is 4 — its opponent — not seat 1, its partner.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 3
## WHEN
- P3>Pass
## EXPECT
TURNPLAYER:4
