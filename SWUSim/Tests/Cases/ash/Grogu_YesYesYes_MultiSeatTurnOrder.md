# ASH_155 Grogu — the bonus attack must not swap the turn, AT MORE THAN TWO SEATS.
#
# WHY THIS FILE EXISTS — and what it turned out to prove.
# It was written against `docs/action-close-deferrals.md` §1, which claimed "a PASS and an INITIATIVE
# CLAIM never open an action id … the gate sees id='' and short-circuits to 'allow'", making the
# per-card `SWU_SUPPRESS_AFTERACTION` flag the only thing stopping a second swap — and recorded as
# **unverified**, since removing it left the whole suite green.
#
# ⚠ THAT ROOT CAUSE WAS WRONG, and these sections are what showed it. An initiative claim DOES open an
# action id (`CustomInput.php` calls SaveUndoVersion before SWUTakeInitiative). What was missing was the
# CLOSE: the pass swapped the turn and left the id open, so the bonus attack's own close was granted.
# `SWUPassAction` now stamps it, the `PASS = 0` reset moved under the same gate, and the per-card flag
# is GONE (2026-08-31) — the sections below hold that behaviour with no card-specific help. The general
# form of this file lives in `Tests/Cases/core/ActionClose_PassEndsTheAction.md`.
#
# ⚠ WHY A 2-PLAYER SECTION IS A WEAK DETECTOR FOR THIS WHOLE FAMILY. `SWUSwapTurnPlayer()` advances via
# NextLiveSeat(). At two seats that is an INVOLUTION — swapping twice returns you to where you started —
# so a double swap shows up as "the acting player kept their turn", and any band-aid that compensates by
# swapping back, or by restoring a saved $gTurnPlayer, is indistinguishable from correct behaviour.
# At three or more seats the same double swap ADVANCES TWICE and EATS THE SEAT IN BETWEEN. Every
# compensation of that shape is therefore a 2-player-only fix, and this file is the shape that can tell
# the difference: it pins the EXACT next seat rather than "not me".

---

# TwoSeat_InitiativeBonusAttack_TurnGoesToTheOtherSeat
#// The control, and the shape the existing Grogu_YesYesYes.md sections already use. P1 claims the
#// initiative (which IS the pass that moves the turn), Grogu's bonus attack resolves, and the turn must
#// sit with P2. At two seats a second swap would land back on P1, so this section can see a double swap
#// — it just cannot tell a SKIP from a RETURN, because at two seats those are the same board.
## GIVEN
CommonSetup: rrk/rgw
WithActivePlayer: 1
WithP1GroundArena: ASH_155:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>Claim
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:3
INITIATIVECOUNTER:P1_CLAIMED
TURNPLAYER:2

---

# FourSeat_InitiativeBonusAttack_TurnGoesToTheVERYNextSeat
#// THE DISCRIMINATOR. Same line, four seats. The initiative claim is a pass, so the turn moves exactly
#// one seat: 1 → 2. If the bonus attack's close swaps a second time the turn lands on seat 3 and SEAT 2
#// NEVER GETS ITS TURN — which is the "it skipped the next player" report shape, and is invisible in
#// every 2-player fixture in the suite.
#// ⚠ TURNPLAYER, not NOEXTRAACTION, is the right assertion here — for the OPPOSITE reason to the one
#// originally written. The ledger now DOES see this path: the second close is attempted and refused,
#// so NOEXTRAACTION (which counts attempts) reports one and would fail on correct behaviour.
## GIVEN
CommonSetup: rrk/rgw
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithP1GroundArena: ASH_155:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
## WHEN
- P1>Claim
- P1>AnswerDecision:myGroundArena-1
#// ⚠ SEAT-TAGGED, not theirBase-0. At 3+ seats the attack-target pool is p{n}… — the same addressing
#// change that bit the preview-tile and split-damage bugs. A 2-player fixture cannot spell this.
- P1>AnswerDecision:p2Base-0
## EXPECT
P2BASEDMG:3
INITIATIVECOUNTER:P1_CLAIMED
TURNPLAYER:2

---

# FourSeat_InitiativeDeclineBonusAttack_TurnGoesToTheVERYNextSeat
#// The decline branch of the same "may". Declining must move the turn exactly one seat too — a
#// compensation that only covers the accept path would leave this one skipping.
## GIVEN
CommonSetup: rrk/rgw
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithP1GroundArena: ASH_155:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
## WHEN
- P1>Claim
- P1>AnswerDecision:-
## EXPECT
INITIATIVECOUNTER:P1_CLAIMED
TURNPLAYER:2

---

# FourSeat_PlainPass_TurnGoesToTheVERYNextSeat
#// The baseline with no Grogu at all: an ordinary pass moves the turn exactly one seat. This is what
#// makes the two sections above meaningful — if this one ever reds, the defect is in the pass/seat
#// rotation itself and not in the bonus-attack close.
## GIVEN
CommonSetup: rrk/rgw
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
## WHEN
- P1>Pass
## EXPECT
TURNPLAYER:2
