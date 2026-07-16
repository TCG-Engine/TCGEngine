# BaseDefeat_EliminatesNotWins
#// Phase 5 — A base reaching 0 HP eliminates that seat instead of ending the game

#// P1's 3-power unit attacks P3's base (pre-damaged to 27 of 30). The lethal hit must ELIMINATE P3,
#// not declare an instant winner — P1 and P2 are still live.

## GIVEN
CommonSetup: grw/ggk
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_229:1:0
WithP3Base: SOR_019:27

## WHEN
- P1>AttackGroundArena:0:P3B

## EXPECT
SEATLIVE:3:false
SEATLIVE:1:true
SEATLIVE:2:true

---

# EliminateSeat_ControlledUnitReturns
#// Phase 5 — A unit the eliminated seat controls-but-doesn't-own goes to its owner's discard

#// P3 controls a unit owned by P2 (mind-controlled onto P3's board). Eliminating P3 must send
#// that unit to P2's discard, and it must NOT remain on P3's board.

## GIVEN
CommonSetup: grw
WithSeatOrder: 123
WithLiveSeats: 123
WithP3Base: SOR_019
WithP3ControlledUnit: SOR_229:2
WithActivePlayer: 1

## WHEN
- P1>EliminateSeat:3

## EXPECT
SEATLIVE:3:false
P3GROUNDCOUNT:0
P2DISCARDCOUNT:1

---

# EliminateSeat_ReleasesCounter
#// Phase 5 — An eliminated seat's held counter returns to center

## GIVEN
CommonSetup: grw
WithSeatOrder: 123
WithLiveSeats: 123
WithP3Base: SOR_019
WithActivePlayer: 3

## WHEN
- P3>TakeCounter:blast
- P1>EliminateSeat:3

## EXPECT
BLASTCOUNTER:AVAILABLE
SEATLIVE:3:false

---

# EliminateSeat_RemovesFromLiveSeats
#// Phase 5 — Eliminating a seat removes it from LiveSeats (order unchanged)

## GIVEN
CommonSetup: grw
WithSeatOrder: 123
WithLiveSeats: 123
WithP3Base: SOR_019
WithActivePlayer: 1

## WHEN
- P1>EliminateSeat:3

## EXPECT
SEATCOUNT:3
SEATLIVE:1:true
SEATLIVE:2:true
SEATLIVE:3:false

---

# EliminateSeat_RemovesOwnedUnits
#// Phase 5 — Eliminating a seat removes its owned units from play

## GIVEN
CommonSetup: grw
WithSeatOrder: 123
WithLiveSeats: 123
WithP3Base: SOR_019
WithP3GroundArena: SOR_229:1:0
WithActivePlayer: 1

## WHEN
- P1>EliminateSeat:3

## EXPECT
SEATLIVE:3:false
P3GROUNDCOUNT:0

---

# FinalShowdown_Eliminates
#// Phase 5 — Final Showdown eliminates the loser, not "opponent wins"

#// Seat 3 carries the SWU_SHD208_LOSE marker. At the start of the regroup phase the Final Showdown
#// lose-check fires: in Twin Suns P3 is ELIMINATED (not "P1/P2 wins outright") — P1 and P2 stay live.

## GIVEN
CommonSetup: grw/ggk
WithSeatOrder: 123
WithLiveSeats: 123
WithP3Base: SOR_019
WithP3GlobalEffect: SWU_SHD208_LOSE
WithActivePlayer: 1

## WHEN
- P1>RunRegroupStart

## EXPECT
SEATLIVE:3:false
SEATLIVE:1:true
SEATLIVE:2:true

---

# FourPlayer_FirstElimEndsAtPhase
#// Phase 5 — 4-player: first elimination ends the game at phase end by highest base HP

#// P1 (3-power unit) kills P3's base (27/30). P3 is eliminated + P1 heals 5 (already at 0, capped).
#// At phase end the game ends: among the live seats P1 (base 30) beats P2 (30-20=10) and P4 (30-10=20),
#// winning outright. P2 and P4 are NOT declared winners.

## GIVEN
CommonSetup: grw/ggk/{theirBaseDamage:20}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_229:1:0
WithP3Base: SOR_019:27
WithP4Base: SOR_019:10

## WHEN
- P1>AttackGroundArena:0:P3B
- P1>ScorePhaseEnd

## EXPECT
SEATLIVE:3:false
SEATLIVE:1:true
SEATLIVE:2:true
SEATLIVE:4:true
GAMEWINNERS:1

---

# HealFiveOnKO
#// Phase 5 — The eliminator heals 5 from their own base

#// Same KO as Phase5_BaseDefeat, but P1's base starts at 10 damage. After eliminating P3, P1 (the
#// last to damage that base) heals 5 → base damage 10 → 5.

## GIVEN
CommonSetup: grw/ggk/{myBaseDamage:10}
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_229:1:0
WithP3Base: SOR_019:27

## WHEN
- P1>AttackGroundArena:0:P3B

## EXPECT
SEATLIVE:3:false
P1BASEDMG:5

---

# Scoring_HighestHPWins
#// Phase 5 — After first elimination, highest remaining base HP wins at phase end

#// P1 and P2 have the same base (equal HP); P2's base is pre-damaged 25, so after P3 is
#// eliminated P1 has the strictly-highest remaining HP and wins outright (no tie).

## GIVEN
CommonSetup: grw/grw/{theirBaseDamage:25}
WithSeatOrder: 123
WithLiveSeats: 123
WithP3Base: SOR_019
WithActivePlayer: 1

## WHEN
- P1>EliminateSeat:3
- P1>ScorePhaseEnd

## EXPECT
GAMEWINNERS:1

---

# Scoring_LastStandingImmediate
#// Phase 5 — Dropping to one live seat scores immediately (no phase-end needed)

## GIVEN
CommonSetup: grw
WithSeatOrder: 123
WithLiveSeats: 123
WithP3Base: SOR_019
WithActivePlayer: 1

## WHEN
- P1>EliminateSeat:2
- P1>EliminateSeat:3

## EXPECT
GAMEWINNERS:1
SEATLIVE:2:false
SEATLIVE:3:false

---

# Scoring_TieShares
#// Phase 5 — Tied highest base HP shares the victory

## GIVEN
CommonSetup: grw
WithSeatOrder: 123
WithLiveSeats: 123
myBase: SOR_019
theirBase: SOR_019
WithP3Base: SOR_019
WithActivePlayer: 1

## WHEN
- P1>EliminateSeat:3
- P1>ScorePhaseEnd

## EXPECT
GAMEWINNERS:1,2

---

# SelfElimination_NoHeal
#// Phase 5 — Self-elimination (no damager) heals nobody

#// Eliminating a seat with no killer (state-based / self-defeat) must NOT heal anyone. P1's base
#// starts at 10 damage and stays there.

## GIVEN
CommonSetup: grw/ggk/{myBaseDamage:10}
WithSeatOrder: 123
WithLiveSeats: 123
WithP3Base: SOR_019
WithActivePlayer: 1

## WHEN
- P1>EliminateSeat:3

## EXPECT
SEATLIVE:3:false
P1BASEDMG:10

---

# WinnerSet_StoresMultiple
#// Phase 5 — The winner set stores one or many seats

## GIVEN
CommonSetup: grw
WithSeatOrder: 1234
WithLiveSeats: 24
WithActivePlayer: 1

## WHEN
- P1>DeclareWinners:2,4

## EXPECT
GAMEWINNERS:2,4
