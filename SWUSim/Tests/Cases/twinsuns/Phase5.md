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
#// ⚠ myBase/theirBase are CommonSetup OPTS, not top-level directives — written at top level they were
#// silently dropped, so seats 1 and 2 kept the base implied by the `grw` code and this section's stated
#// premise (all three seats on the same base) never actually applied. It stayed green because the two
#// defaulted seats happened to tie each other anyway. Moved into the opts block 2026-08-26; the EXPECT
#// is unchanged and still passes, now for the stated reason.
CommonSetup: grw/grw/{myBase:SOR_019; theirBase:SOR_019}
WithSeatOrder: 123
WithLiveSeats: 123
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

---

# WinnerSet_SurvivesLaterVariableWrite
#// Phase 5 — A declared winner must not be erased by the next game-variable write.
#//
#// GAMEOVER_WINNER/GAMEOVER_WINNERS live in the same decision-queue variable slot as every other
#// game variable (PASS, undo bookkeeping, ...). That slot used to be written in TWO incompatible
#// encodings — JSON by StoreVariable, pipe "K=V|K=V" by SetSWUVar — and each writer wiped the
#// other's keys. So the game scored a winner correctly and then the very next pass-counter write
#// deleted it: the match layer read "no winner", never recorded the result, and ALL FOUR seats
#// were shown "You Lost". Passing here exercises SetSWUVar (the pass counter) after the winner
#// was declared; both must coexist.

## GIVEN
CommonSetup: grw
WithSeatOrder: 1234
WithLiveSeats: 24
WithActivePlayer: 1
WithGamePhase: ActionPhase

## WHEN
- P1>DeclareWinners:2,4
- P1>Pass

## EXPECT
GAMEWINNERS:2,4

---

# WinnerSet_SurvivesRequestBoundary
#// Phase 5 — The winner set is serialized, so it survives the fresh-process boundary between
#// requests. The match layer reads it on a LATER request than the one that scored it (that read is
#// what records the result and drives every seat's end-game overlay), so a winner held only in
#// memory would be lost before anyone could act on it.

## GIVEN
CommonSetup: grw
WithSeatOrder: 1234
WithLiveSeats: 24
WithActivePlayer: 1
WithGamePhase: ActionPhase

## WHEN
- P1>DeclareWinners:2,4
- P1>SimulateRequestBoundary

## EXPECT
GAMEWINNERS:2,4

---

# SingleWinner_AlsoPopulatesWinnerSet
#// Phase 5 — A single-winner (2-player) game populates the winner SET too, so every read point
#// (match layer, end-game overlay) has ONE shape to handle regardless of format. Same final blow as
#// win_con/EndGameFinalBlow: P1's 3-power unit finishes P2's base at 27 of 30.

## GIVEN
CommonSetup: grw/grw/{theirBaseDamage:27}
P1Deck: [SOR_095]
P2Deck: [ ]
WithP1GroundArena: SOR_095:1:1
WithInitiativePlayer: 1
WithInitiativeClaimed: false

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1WIN
GAMEWINNERS:1

---

# HarnessSelfTest_FarSeatDeployedLeaderIsRealAndLinked
#// ⚠ A HARNESS SELF-TEST, not a card test — added 2026-08-24 while closing the four-seat fixture gaps.
#// `WithP{3,4}Leader: CARD:ready:deployed` used to set the leader's Deployed FLAG and splice NOTHING: no
#// arena unit, DeployedUniqueID left at 0, IsLeaderUnit() finding nothing. A far-seat deployed leader was
#// therefore HALF-MATERIALISED, and any assertion about one was quietly meaningless — the fixture claimed
#// a board state the engine could never produce.
#// This pins the capability itself so the next four-seat leader test can rely on it:
#//   • SEAT 3's deployed leader exists as a real GROUND unit, and
#//   • SEAT 4's is a real unit too — proving the splice is per-seat and not a one-off.
#// ⚠ Seats 1/2 have always had this splice; only 3/4 were unwired, which is exactly the shape of every
#//   other gap this sweep found in the harness.

## GIVEN
CommonSetup: rrk/bbw/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Leader: SHD_014:1:1
WithP4Leader: SHD_014:1:1
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN

## EXPECT
SEATCOUNT:4
P3GROUNDARENACOUNT:1
P3GROUNDARENAUNIT:0:CARDID:SHD_014
P3GROUNDARENAUNIT:0:ISLEADERUNIT
P4GROUNDARENACOUNT:1
P4GROUNDARENAUNIT:0:ISLEADERUNIT
