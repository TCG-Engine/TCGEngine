#// CORE — Team Suns house rule (owner, 2026-10-12): once a team is down to ONE player while the other team still has two, the
#// survivor takes over its dead teammate's turn. The turn order keeps the dead seat's place at the table, so the TEAMS keep
#// alternating: with Blue seat 2 out, 1 → 4 (in seat 2's turn) → 3 → 4 → 1… Back at one player per team (1v1), the dead seats are
#// simply skipped again.
#//
#// Without it the full team got two actions for every one of the short team's (1 → 3 → 4 → 1…), which players called unfair.
#//
#// The survivor is still ONE player for the counters: taking a counter ends their round in BOTH of their turns, and they cannot
#// take a second one (owner). And the action phase needs a pass from every TURN, not every seat — three live seats now have four
#// turns, so three consecutive passes (4, 3, 4) must not end the phase before seat 1 has had its turn.
#//
#// There are no team rules in the CR; this is the sim's own format (AppCore/SWU/Formats.php 'teamsuns').
#// "The player to your right/left" (SWUSeatToTheRight/Left → NextLiveSeat) is a SEATING question and is NOT affected.
# BlueShort_SurvivorTakesTheDeadSeatsTurn
#// Seat 2 (Blue) is out. After seat 1, the next turn is seat 2's — taken by Blue's survivor, seat 4. (Skipping it lands on 3.)
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithLiveSeats: 134
WithActivePlayer: 1
## WHEN
- P1>Pass
## EXPECT
TURNPLAYER:4
PHASE:MAIN

---

# BlueShort_AfterTheBorrowedTurn_TheTableResumesAfterTheDeadSeat
#// Seat 4 played seat 2's turn, so the next turn is seat 3's (the seat after 2) — not seat 1's (the seat after 4).
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithLiveSeats: 134
WithActivePlayer: 1
## WHEN
- P1>Pass
- P4>Pass
## EXPECT
TURNPLAYER:3
PHASE:MAIN

---

# BlueShort_ThreePassesAreNotEveryTurn
#// Passes from 1, 4 (as seat 2), 3: three consecutive passes, which used to end a three-seat phase. Four turns are in the
#// rotation now, so the survivor's own turn comes first — the phase is still on.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithLiveSeats: 134
WithActivePlayer: 1
## WHEN
- P1>Pass
- P4>Pass
- P3>Pass
## EXPECT
TURNPLAYER:4
PHASE:MAIN

---

# BlueShort_FourPassesEndThePhase
#// …and the fourth consecutive pass, one per turn, ends it.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithLiveSeats: 134
WithActivePlayer: 1
## WHEN
- P1>Pass
- P4>Pass
- P3>Pass
- P4>Pass
## EXPECT
PHASE:RES

---

# RedShort_SurvivorCoversSeatOne_AndTheTableResumesAtSeatTwo
#// The mirror, across the table's wrap-around: Red seat 1 is out, seat 3 survives. After seat 4 comes seat 1's turn (seat 3
#// takes it), and after THAT comes seat 2 — the seat after 1, not the seat after 3.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithLiveSeats: 234
WithActivePlayer: 4
## WHEN
- P4>Pass
- P3>Pass
## EXPECT
TURNPLAYER:2
PHASE:MAIN

---

# RedShort_SurvivorCoversSeatOne
#// The first half of the section above on its own: after seat 4, seat 3 (in seat 1's turn) — not seat 2.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithLiveSeats: 234
WithActivePlayer: 4
## WHEN
- P4>Pass
## EXPECT
TURNPLAYER:3

---

# Counter_TheSurvivorIsDoneInBothTurns
#// Owner: "if the survivor takes a counter, they are done for the round." Seat 4 takes Blast in its own turn; in seat 2's turn
#// (after seat 1) it is auto-passed, and the turn goes on to seat 3. Both of the survivor's turns are spent.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithLiveSeats: 134
WithActivePlayer: 4
## WHEN
- P4>TakeCounter:blast
- P1>Pass
## EXPECT
BLASTCOUNTER:P4
TURNPLAYER:3
PHASE:MAIN

---

# Control_OneVsOne_DeadSeatsAreSkipped
#// One player per team: nobody is short, so the dead seats are skipped and two passes end the phase.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithLiveSeats: 14
WithActivePlayer: 1
## WHEN
- P1>Pass
## EXPECT
TURNPLAYER:4
PHASE:MAIN

---

# Control_OneVsOne_TwoPassesEndThePhase
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithLiveSeats: 14
WithActivePlayer: 1
## WHEN
- P1>Pass
- P4>Pass
## EXPECT
PHASE:RES

---

# Control_FreeForAll_DeadSeatIsSkipped
#// The rule is TEAM SUNS only: a free-for-all Twin Suns table with seat 2 out goes 1 → 3, as before.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithLiveSeats: 134
WithActivePlayer: 1
## WHEN
- P1>Pass
## EXPECT
TURNPLAYER:3

---

# Control_FullTeams_TableOrder
#// Nobody out: the ordinary 1 → 2 → 3 → 4 rotation.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
## WHEN
- P1>Pass
- P2>Pass
## EXPECT
TURNPLAYER:3
