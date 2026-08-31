# CORE — multi-seat turn rotation. No cards, no abilities: just "whose turn is it next".
#
# WHY A CORE FILE. 152 case files already set WithSeatOrder, but every one of them is about a CARD —
# the rotation itself is only ever asserted incidentally, as a side effect of something else. These
# sections pin the rotation on its own, so a regression in NextLiveSeat/SWUSwapTurnPlayer reds here
# with an unambiguous message instead of scattering odd failures across a hundred card files.
#
# ⚠ WHY THIS MATTERS MORE THAN AT TWO SEATS. SWUSwapTurnPlayer() advances via NextLiveSeat(), which at
# two seats is an INVOLUTION — a double swap returns to the acting player and reads as "I got an extra
# action". At three or more it advances twice and the middle seat never acts. Only a fixture that pins
# the EXACT next seat can tell those apart, so every section below asserts a specific TURNPLAYER rather
# than "not me".
#
# ⚠ NO `P{n}OnlyActions` ANYWHERE IN THIS FILE. That directive claims initiative and marks every other
# seat in SWU_COUNTER_TAKEN so they auto-pass — which is precisely the rotation these sections exist to
# measure. It would make all of them vacuous.
#
# A plain pass is the probe: it is the only action that moves the turn without touching a board.

---

# ThreeSeat_Rotate_1to2
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>Pass
## EXPECT
SEATCOUNT:3
TURNPLAYER:2
PHASE:MAIN

---

# ThreeSeat_Rotate_2to3
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 2
## WHEN
- P2>Pass
## EXPECT
TURNPLAYER:3
PHASE:MAIN

---

# ThreeSeat_Rotate_3wrapsTo1
#// The wrap is the step most likely to be written as "+1" and get it wrong.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 3
## WHEN
- P3>Pass
## EXPECT
TURNPLAYER:1
PHASE:MAIN

---

# FourSeat_Rotate_1to2
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>Pass
## EXPECT
SEATCOUNT:4
TURNPLAYER:2
PHASE:MAIN

---

# FourSeat_Rotate_3to4
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 3
## WHEN
- P3>Pass
## EXPECT
TURNPLAYER:4
PHASE:MAIN

---

# FourSeat_Rotate_4wrapsTo1
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 4
## WHEN
- P4>Pass
## EXPECT
TURNPLAYER:1
PHASE:MAIN

---

# NonSequentialSeatOrder_FollowsTheORDER_NotTheSeatNumber
#// SeatOrder is the clockwise seating, and it is NOT required to be 1,2,3,4 — CreateGame can deal any
#// permutation. With order 3142, the seat after 3 is 1 and the seat after 4 is 2. A rotation written as
#// "seat + 1" passes every sequential section above and fails here, which is the whole point of this
#// one: it is the only section in the file that can catch that mistake.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithSeatOrder: 3142
WithLiveSeats: 3142
WithActivePlayer: 3
## WHEN
- P3>Pass
## EXPECT
TURNPLAYER:1
PHASE:MAIN

---

# NonSequentialSeatOrder_Wrap
#// Same order 3142; the LAST entry is seat 2, so its wrap goes back to the FIRST entry, seat 3.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithSeatOrder: 3142
WithLiveSeats: 3142
WithActivePlayer: 2
## WHEN
- P2>Pass
## EXPECT
TURNPLAYER:3
PHASE:MAIN

---

# ThreeSeat_EliminatedSeatIsSkipped
#// LiveSeats is the non-eliminated subset of SeatOrder. Seat 2 is gone, so 1's next seat is 3 — the
#// table closes up around a dead seat rather than stalling on it.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithLiveSeats: 13
WithActivePlayer: 1
## WHEN
- P1>Pass
## EXPECT
SEATLIVE:1:true
SEATLIVE:2:false
SEATLIVE:3:true
TURNPLAYER:3
PHASE:MAIN

---

# FourSeat_TwoEliminated_RotationClosesUp
#// Seats 2 and 3 eliminated: the rotation is 1 → 4 → 1. Two adjacent dead seats is the case a
#// single-step "skip if dead" check gets wrong — it has to keep walking, not step once.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithLiveSeats: 14
WithActivePlayer: 1
## WHEN
- P1>Pass
## EXPECT
SEATLIVE:2:false
SEATLIVE:3:false
TURNPLAYER:4
PHASE:MAIN

---

# FourSeat_EliminatedSeat_WrapsPastTheEnd
#// Seat 4 is dead and seat 3 is acting, so the wrap has to pass the END of SeatOrder and continue from
#// its start to reach seat 1.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithLiveSeats: 123
WithActivePlayer: 3
## WHEN
- P3>Pass
## EXPECT
TURNPLAYER:1
PHASE:MAIN
