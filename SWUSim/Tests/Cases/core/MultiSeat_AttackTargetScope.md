# CORE — who a unit may attack at 3+ seats, and how teams and Sentinel narrow it.
#
# `SWUGetAllValidAttackTargets` is one of the consumers that inherits `OpponentsOf()`, so it is the
# place a teammate leaking into the opponent list becomes a rules break you can actually see: you could
# attack your own partner. It also has to union across SEVERAL opponents while applying each one's
# Sentinel and base separately — a per-opponent property that simply does not exist at two seats, where
# there is only ever one "them".
#
# Counting model used throughout: from a GROUND unit, each live opponent contributes its ground units
# plus its base. Space units are not reachable from the ground arena.
#
# ⚠ Every seat needs a BASE for these counts to mean anything, and CommonSetup3P/4P supplies one per
# seat from its aspect code. Before those directives existed each far seat needed a hand-written
# WithP{n}Base here, and forgetting one silently LOWERED the expected count instead of failing loudly.

---

# ThreeSeat_UnionsAcrossBothOpponents
#// Seats 2 and 3 each field one ground unit and hold a base: 2 units + 2 bases = 4 targets. At two
#// seats this number can only ever be "one opponent's worth", so the union is untested there.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
## WHEN
## EXPECT
ATTACKTARGETS:1:G:0:4

---

# FourSeat_UnionsAcrossAllThreeOpponents
#// 3 units + 3 bases = 6.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
## WHEN
## EXPECT
ATTACKTARGETS:1:G:0:6

---

# TeamSuns_TeammateIsNotAttackable
#// THE RULES CLAIM. Same four-seat board as above, now in teams: seat 3 is seat 1's partner, so its
#// unit AND its base drop out of the pool entirely — 2 units + 2 bases = 4, not 6. If this ever reads
#// 6, a player can attack their own teammate.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
## WHEN
## EXPECT
OPPONENTSOF:1:2,4
ATTACKTARGETS:1:G:0:4

---

# ThreeSeat_SentinelNarrowsONLYThatOpponent
#// Sentinel is PER OPPONENT and this is the shape that proves it. Seat 2 fields a Sentinel (SHD_029
#// Pyke Sentinel) alongside a plain unit, so attacks against SEAT 2 are restricted to the Sentinel
#// alone — its plain unit and its base become illegal. Seat 3 has no Sentinel and is completely
#// unaffected, still offering its unit and its base.
#//   seat 2 contributes 1 (the Sentinel only)  +  seat 3 contributes 2 (unit + base)  =  3
#// At two seats one player's Sentinel locks down the ONLY opponent, so "restricts globally" and
#// "restricts that opponent" are the same number and neither can be distinguished.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_029:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
## WHEN
## EXPECT
ATTACKTARGETS:1:G:0:3

---

# ThreeSeat_SentinelOnEachOpponent_BothNarrow
#// Both opponents field a Sentinel, so each contributes exactly its Sentinel: 1 + 1 = 2. The control
#// for the section above — it shows the narrowing is applied per opponent rather than once globally.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_029:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SHD_029:1:0
WithP3GroundArena: SOR_046:1:0
## WHEN
## EXPECT
ATTACKTARGETS:1:G:0:2

---

# EliminatedOpponentContributesNothing
#// Seat 3 is eliminated, so only seat 2's unit and base remain: 2 targets. A pool built from SeatOrder
#// rather than LiveSeats would still offer the dead seat's board.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithLiveSeats: 12
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
## WHEN
## EXPECT
SEATLIVE:3:false
ATTACKTARGETS:1:G:0:2
