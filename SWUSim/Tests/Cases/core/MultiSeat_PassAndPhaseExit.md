# CORE — the action phase ends when ALL LIVE players pass consecutively, not when TWO do.
#
# `SWUPassAction` ends the phase on `$consecutivePasses >= $liveCount - 1`. At two seats that is
# `>= 1`, i.e. the historical "two consecutive passes" rule — so the seat-count-aware form is
# byte-identical there and NO 2-player fixture can tell the two apart. These sections are the ones that
# can: at three seats a phase that ends after two passes robs the third seat of its turn, and at four
# seats it robs two of them.
#
# The threshold is driven by LIVE seats, not seat order, so an elimination LOWERS it — covered below.
#
# ⚠ NO `P{n}OnlyActions`: it marks the other seats as having taken a counter so they auto-pass, which
# is exactly the pass accounting under test.

---

# ThreeSeat_TwoPassesDoNotEndThePhase
#// Two of three seats have passed. The third has not, so the phase MUST still be MAIN and the turn must
#// be sitting on that third seat waiting for it. A 2-player threshold ends the phase here.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>Pass
- P2>Pass
## EXPECT
PHASE:MAIN
TURNPLAYER:3
SWUVAR:PASS:2

---

# ThreeSeat_ThreePassesEndThePhase
#// All three live seats have now passed consecutively, so the action phase is over.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>Pass
- P2>Pass
- P3>Pass
## EXPECT
PHASEISNOT:MAIN

---

# FourSeat_ThreePassesDoNotEndThePhase
#// Three of four. Seat 4 still owes an action.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>Pass
- P2>Pass
- P3>Pass
## EXPECT
PHASE:MAIN
TURNPLAYER:4
SWUVAR:PASS:3

---

# FourSeat_FourPassesEndThePhase
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>Pass
- P2>Pass
- P3>Pass
- P4>Pass
## EXPECT
PHASEISNOT:MAIN

---

# EliminationLowersTheThreshold_FourSeatsTwoAlive
#// SeatOrder is still 1234 but only seats 1 and 3 are alive, so the phase must end after TWO passes —
#// the threshold follows LIVE seats, not the original table size. A threshold read off SeatOrder would
#// leave the phase open forever waiting for seats that can never act, which soft-locks the table.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithLiveSeats: 13
WithActivePlayer: 1
## WHEN
- P1>Pass
- P3>Pass
## EXPECT
PHASEISNOT:MAIN

---

# EliminationLowersTheThreshold_OnePassIsNotEnough
#// The negative control for the section above: with two live seats, ONE pass must not end the phase.
#// Without this, a threshold that had collapsed to "any pass ends it" would still pass that section.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithLiveSeats: 13
WithActivePlayer: 1
## WHEN
- P1>Pass
## EXPECT
PHASE:MAIN
TURNPLAYER:3
SWUVAR:PASS:1

---

# AnActionRESETSThePassStreak_ThreeSeats
#// CR: the passes have to be CONSECUTIVE. Two seats pass, the third takes a real action instead, and
#// the streak returns to zero — so the two passes that follow must NOT end the phase, and seat 3 must
#// get asked again. A streak that merely accumulated would end the phase on the fourth pass overall.
#// ⚠ Seat 3 has to AFFORD its play, which is why this section uses CommonSetup3P. A seat with no base
#// or leader has no aspects, pays the full aspect penalty, and an unaffordable play is a SILENT NO-OP
#// that reads exactly like a skipped turn — it cost a real mis-diagnosis before the 3P/4P directives
#// dressed the far seats automatically.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP3Resources: 6
WithP3Hand: SOR_046
## WHEN
- P1>Pass
- P2>Pass
- P3>PlayHand:0
- P1>Pass
- P2>Pass
## EXPECT
PHASE:MAIN
TURNPLAYER:3
SWUVAR:PASS:2
P3GROUNDARENACOUNT:1
