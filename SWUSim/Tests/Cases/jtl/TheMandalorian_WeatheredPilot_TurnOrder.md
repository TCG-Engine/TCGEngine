# JTL_210 The Mandalorian — Weathered Pilot: playing it must pass the turn to the NEXT SEAT, once.
#
# WHY THIS FILE EXISTS — bug report #1021, game 4161: "pilot mandalorian was played on the ground to
# exhaust 2 units, it skipped the next players turn."
#
# CONFIRMED FROM THE GAME LOG, not from the reporter's description. Game 4161 is a 4-seat game with
# SeatOrder [1,2,3,4] and P4 holding initiative, so round 5's order is P4 → P1 → P2 → P3 → P4. The log
# records, consecutively and with NO pass by P3 anywhere in the round:
#     115: P4 played Ki-Adi-Mundi
#     116: P1's Chio Fain attacked
#     117: P2 played JTL_210 The Mandalorian
#     118: P4 deployed Director Krennic     <-- P3 never acted
#     119: P1 played Ninth Sister
# One action, two seats advanced.
#
# ⚠ WHY THE EXISTING JTL_210 FILE COULD NOT CATCH THIS. TheMandalorian_WeatheredPilot.md uses
# `P1OnlyActions: true`, which claims initiative so the opponent auto-passes — making a DOUBLE turn
# swap indistinguishable from a single one. TURNPLAYER is structurally blind in those fixtures (see the
# assertion's own note in SchemaTestRunner). Every section here therefore OMITS that directive, and
# pairs TURNPLAYER with NOEXTRAACTION, which reads the action-close ledger directly and is strictly
# stronger — it sees a second close even when a compensating swap hides the symptom.

---

# TwoPlayer_PlayedAsUnit_PassesOnce
#// The simplest shape: 2 seats, no pilot host in play so the card plays as a unit with no
#// unit-vs-upgrade prompt. If the double-close is generic to this card's when-played chain, it shows
#// here; if this passes, the defect needs the pilot CHOICE or the multi-seat table, and the sections
#// below say which.
## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 14
WithP1Hand: JTL_210
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
NOEXTRAACTION
TURNPLAYER:2

---

# TwoPlayer_PilotChoiceThenUnit_PassesOnce
#// The same board WITH a friendly vehicle, so the unit-vs-upgrade Piloting prompt actually fires and is
#// answered "play as a unit". This is the branch the live game took: P2 had a deployed leader on the
#// ground, which is a legal pilot host, so the choice was offered there and NOT in the section above.
#// If the extra close rides on that dispatch, this is where it appears.
## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 14
WithP1Hand: JTL_210
WithP1GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Unit
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
NOEXTRAACTION
TURNPLAYER:2

---

# FourSeat_PlayedAsUnit_PassesToTheVeryNextSeat
#// The reported table shape. P1 acts, so the turn must land on seat 2 — not seat 3. A single extra
#// close skips exactly one seat, which in a 2-player game is invisible (it swaps back to you and the
#// harness's other assertions still hold) but in a 4-seat game is the whole bug.
## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithP1Resources: 14
WithP1Hand: JTL_210
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_095:1:0
WithP4GroundArena: SOR_108:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p2GroundArena-0&p3GroundArena-0
## EXPECT
NOEXTRAACTION
TURNPLAYER:2

---

# Control_IG2000_SameDecisionShape_PassesOnce
#// THE DISCRIMINATOR. JTL_140 IG-2000 has the IDENTICAL when-played chain — an MZMULTICHOOSE followed
#// by a dontSkipOnPass CUSTOM — but is NOT a pilot. In game 4161's own log P1 played IG-2000 twice and
#// the turn passed correctly both times, so if this control stays green while the sections above go
#// red, the defect is in the PILOT dispatch path and not in the multi-choose shape.
## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 14
WithP1Hand: JTL_140
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
## EXPECT
NOEXTRAACTION
TURNPLAYER:2

---

# RequestBoundary_TwoPlayer_PlayedAsUnit_PassesOnce
#// ⚠ THE AXIS THE SECTIONS ABOVE CANNOT SEE. In a real game the player answers the exhaust prompt in a
#// SEPARATE HTTP REQUEST — the play and the answer are not one PHP process. Everything the close gate
#// keeps in a plain global is gone by then; only serialized SWUVars survive. `_SWUActionCloseGate`'s
#// nesting check reads $GLOBALS['gSWUActionDepth'], which resets to 0 across that boundary, while its
#// SWU_ACTION_ID / SWU_ACTION_CLOSED ledger is serialized and does not. Resolving both in one process,
#// as every section above does, exercises a code path the live game never takes.
## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 14
WithP1Hand: JTL_210
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
NOEXTRAACTION
TURNPLAYER:2

---

# RequestBoundary_FourSeat_PassesToTheVeryNextSeat
#// The reported table shape WITH the boundary: 4 seats, P1 acts, the turn must land on seat 2.
## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithP1Resources: 14
WithP1Hand: JTL_210
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_095:1:0
WithP4GroundArena: SOR_108:1:0
## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:p2GroundArena-0&p3GroundArena-0
## EXPECT
NOEXTRAACTION
TURNPLAYER:2

---

# RequestBoundary_PilotChoiceThenUnit_PassesOnce
#// The pilot unit-vs-upgrade choice AND the exhaust prompt each answered across their own boundary —
#// the closest model of the live sequence, where every prompt is its own round trip.
## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 14
WithP1Hand: JTL_210
WithP1GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:Unit
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
NOEXTRAACTION
TURNPLAYER:2
