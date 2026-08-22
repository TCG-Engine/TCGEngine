# OpponentPlaysForFree
#// Stolen AT-Hauler: opponent may play it from owner's discard for free
#// JTL_221 (Stolen AT-Hauler, 3/5) starts with 3 pre-existing damage.
#// SOR_237 (Alliance X-Wing, 3/2) attacks it — JTL_221 is defeated, gains OTPF.
#// P2 has 0 resources — they can still play JTL_221 from P1's discard for free (OTPF).
#// After playing, P2's space arena has JTL_221 and P1's discard is empty.

## GIVEN
CommonSetup: grw/grw
WithP1SpaceArena: JTL_221:1:3
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>Pass
- P2>AttackSpaceArena:0:0
- P2>PlayFromOpponentDiscard:0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:JTL_221
P1DISCARDCOUNT:0
P2RESAVAILABLE:0

---

# SetsOtpfOnDefeat
#// Stolen AT-Hauler: When Defeated sets OTPF on discard entry
#// JTL_221 (Stolen AT-Hauler, 3/5) starts with 3 pre-existing damage.
#// SOR_237 (Alliance X-Wing, 3/2) attacks it — power 3 is enough to kill it (needs 2 more).
#// JTL_221 attacks back: power 3 >= SOR_237 HP 2, so SOR_237 also dies.
#// After defeat, P1's discard entry for JTL_221 should have Modifier:OTPF.

## GIVEN
CommonSetup: grw/grw
WithP1SpaceArena: JTL_221:1:3
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>Pass
- P2>AttackSpaceArena:0:0

## EXPECT
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:JTL_221
P1DISCARDUNIT:0:MODIFIER:OTPF

---

# StealBackAndForth
#// Stolen AT-Hauler: opponent may play it from owner's discard for free
#// JTL_221 (Stolen AT-Hauler, 3/5) starts with 3 pre-existing damage.
#// SOR_237 (Alliance X-Wing, 2/3) attacks it — JTL_221 is defeated, gains OTPF.
#// P2 has 0 resources — they can still play JTL_221 from P1's discard for free (OTPF).
#// P1 then attacks with their 5 power space unit
#// P2 claims initiative
#// P1 is now allowed to play AT Hauler from their own discard pile for free

## GIVEN
CommonSetup: grw/yrw
WithP1SpaceArena: JTL_221:1:3
WithP1SpaceArena: JTL_153
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P2>PlayFromOpponentDiscard:0
- P1>AttackSpaceArena:0:0
- P2>Claim
- P1>PlayFromDiscard:0

## EXPECT
P1SPACEARENACOUNT:2
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:1:CARDID:JTL_221
P1DISCARDCOUNT:0

---

# FreePlayExpiresAtPhaseEnd
#// Stolen AT-Hauler: the free-play permission is "For THIS phase." JTL_221 (3 pre-damage) is defeated by
#// SOR_237, setting OTPF on P1's discard entry. But if the opponent does NOT play it before the phase ends,
#// the permission expires — both players pass, the action phase ends, and RegroupPhaseStart clears the
#// discard-entry modifier (OTPF → ''). JTL_221 is still in P1's discard, no longer free-playable.

## GIVEN
CommonSetup: grw/grw
WithP1SpaceArena: JTL_221:1:3
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>Pass
- P2>AttackSpaceArena:0:0
- P1>Pass
- P2>Pass

## EXPECT
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:JTL_221
P1DISCARDUNIT:0:MODIFIER:

---

# CanBeCycled
#// Stolen AT-Hauler: the free-play permission RE-ARMS on every defeat within the round, so the unit can be
#// cycled — played and defeated multiple times in the same round. JTL_221 (3 pre-damage) starts in P1's space arena.
#// 1st defeat: P2's SOR_237 (2 power) attacks it → P1's discard, OTPF for P2. P2 replays it for free
#// (now P2-controlled, still P1-owned). 2nd defeat: P1's JTL_153 (5 power) attacks it → back to P1's
#// discard, and the When Defeated FIRES AGAIN — its controller was P2, so it chooses opponent P1, re-arming
#// the free play for P1. P1 then plays it from their own discard for free. End state: P1 has both JTL_153
#// and JTL_221 in space, P2's board is empty, P1's discard is empty, and P1 paid nothing (0 resources).

## GIVEN
CommonSetup: grw/grw
WithP1SpaceArena: JTL_221:1:3
WithP1SpaceArena: JTL_153
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>Pass
- P2>AttackSpaceArena:0:0
- P1>Pass
- P2>PlayFromOpponentDiscard:0
- P1>AttackSpaceArena:0:0
- P2>Claim
- P1>PlayFromDiscard:0

## EXPECT
P1SPACEARENACOUNT:2
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:1:CARDID:JTL_221
P1DISCARDCOUNT:0
P1RESAVAILABLE:0

---

# FromDiscardToHand_NotFreeFromHand
#// Stolen AT-Hauler: the free-play permission is DISCARD-ONLY. Once the card is moved from the discard pile
#// to HAND, it is no longer free — it must be paid for. First the StealBackAndForth setup arms P1's free play: JTL_221
#// (3 pre-damage) trades with SOR_237, P2 replays it for free, P1's JTL_153 (5 power) defeats it again →
#// back to P1's discard with the free-play permission for P1 (its controller was P2). But instead of
#// playing it free from discard, P1 plays SHD_260 Street Gang Recruiter (When Played: return an Underworld
#// card from discard to hand) to pull JTL_221 (Underworld) into hand. Playing it from hand then costs its
#// full 3 (P1: 10 → SHD_260 costs 5 → 5, then JTL_221 costs 3 → 2). The discard permission did NOT carry.

## GIVEN
CommonSetup: yrw/grw
WithP1SpaceArena: JTL_221:1:3
WithP1SpaceArena: JTL_153
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: SHD_260
WithP1Resources: 10

## WHEN
- P1>AttackSpaceArena:0:0
- P2>PlayFromOpponentDiscard:0
- P1>AttackSpaceArena:0:0
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:JTL_221
P1DISCARDCOUNT:0
P1RESAVAILABLE:2

---

# PlayedFreeThenBounced_NotFreeAgain
#// Stolen AT-Hauler: the free play is a ONE-SHOT permission. After it is played for free from the discard
#// pile and then returned to hand, replaying it costs full price. The StealBackAndForth setup arms P1's free
#// play (JTL_221 trades with SOR_237, P2 replays free, JTL_153 defeats it again → P1's discard, permission
#// for P1). P1 plays it from discard FOR FREE (resources unchanged). P2 then Waylays (SOR_222) it back to
#// P1's hand. Replaying it from hand now costs its full 3 (P1: 5 → 2), proving the permission was consumed.

## GIVEN
CommonSetup: yrw/yrw
WithP1SpaceArena: JTL_221:1:3
WithP1SpaceArena: JTL_153
WithP2SpaceArena: SOR_237:1:0
WithP2Hand: SOR_222
WithP1Resources: 5
WithP2Resources: 3

## WHEN
- P1>AttackSpaceArena:0:0
- P2>PlayFromOpponentDiscard:0
- P1>AttackSpaceArena:0:0
- P2>Pass
- P1>PlayFromDiscard:0
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-1
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:JTL_221
P1DISCARDCOUNT:0
P1RESAVAILABLE:2

---

# BlankedBeforeDefeat_NoGrant
#// Stolen AT-Hauler: if its abilities are BLANKED before it is defeated, the When Defeated grant never
#// fires and no free-play permission is set. P2 plays JTL_244 (There Is No Escape — "choose up to 3
#// units; they lose all abilities this round"), targeting P1's JTL_221 (3 pre-damage) to blank it. P2 then
#// attacks it with SOR_237 (2 power) — enough to finish it (3+2 = 5 HP). Because it was blanked, the When
#// Defeated does NOT run, so the discard entry carries NO OTPF modifier (contrast SetsOtpfOnDefeat).
#// OnCardDiscarded skips its dispatch when the source unit has lost all abilities.

## GIVEN
CommonSetup: grw/yrk
WithP2Hand: JTL_244
WithP2Resources: 3
WithP1SpaceArena: JTL_221:1:3
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>Pass
- P2>AttackSpaceArena:0:0

## EXPECT
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:JTL_221
P1DISCARDUNIT:0:MODIFIER:

---

# SimulateRequestBoundary_FreePlayPermission
#// JTL_221 Stolen AT-Hauler — the When Defeated grant raises no decision (one opponent), but production
#// ends the request at every ACTION, so the "for this phase they may play this from its owner's discard for
#// free" permission is written by P2's attack and read by P2's next action in a fresh process. Mirrors
#// OpponentPlaysForFree with the boundary inserted between the defeating attack and the free play: the
#// OTPF modifier on P1's discard entry must survive serialization so P2 (0 resources) can still play it.

## GIVEN
CommonSetup: grw/grw
WithP1SpaceArena: JTL_221:1:3
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>Pass
- P2>AttackSpaceArena:0:0
- P2>SimulateRequestBoundary
- P2>PlayFromOpponentDiscard:0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:JTL_221
P1DISCARDCOUNT:0
P2RESAVAILABLE:0

---

# TwinSuns_AFarSeatsDiscardPileIsReachableAtAll
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-23 (Pass 0, the OTPF/OTPP/OTPN seam).
#// `$entry->Modifier = 'OTPF'` says WHAT may be done and never BY WHOM, and every reader resolved the
#// pile with `OtherPlayer($player)` — ONE seat. So a permission stamped on a FAR seat's pile could be
#// played by NOBODY: seat 1 looked at seat 2's pile, seats 2 and 4 looked at seat 1's. The card sat in
#// seat 3's discard for the rest of the game and the ability silently did nothing.
#// Fixed by the client sending the pile's seat (already in the rendered mzID, "p3Discard-N") and
#// SWUPlayFromOpponentDiscard taking ?int $ownerSeat.
#// SEAT 2 defeats SEAT 3's AT-Hauler, so the permission lands on SEAT 3's pile; JTL_221's auto-picked
#// "opponent" is SWUChooseOpponent(3) = SEAT 1, which is the grantee. Seat 1 must be able to reach seat
#// 3's pile and play it free (0 resources).
#// ⚠ THE ATTACKER MUST NOT BE THE GRANTEE. cardDiscardedHandlers receives the ACTING player, and the card
#//   branches `($opponent === $player) ? 'TPF' : 'OTPF'` — the "someone stole my card" case. If seat 1
#//   both attacks and is the grantee the entry gets TPF (play from your OWN discard) and this path is
#//   never exercised at all.
#// ⚠ THIS SECTION ALSO PINS A SECOND BUG, found while writing it: the play path handed the pile off as
#//   the literal "theirDiscard-N", and GetZoneObject resolves that with the legacy `$playerID == 1 ? 2 : 1`.
#//   So the permission was correctly located on seat 3's pile and then ACTIVATED SEAT 2's discard entry at
#//   the same index — a different card entirely entering the wrong arena. Fixed by SWUForeignDiscardMzID().
#// ⚠ A 2-player version CANNOT FAIL — with one opponent OtherPlayer() already names the only pile.
#// Mutation check: drop $ownerSeat, or revert SWUForeignDiscardMzID to the bare "theirDiscard-N", and
#// this reds; all nine 2-player sections stay green either way.

## GIVEN
CommonSetup: grw/grw/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 2
WithGamePhase: ActionPhase
WithP2SpaceArena: SOR_237:1:0
WithP3SpaceArena: JTL_221:1:3
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P2>AttackSpaceArena:0:P3S0
- P1>PlayFromOpponentDiscard:P3:0

## EXPECT
SEATCOUNT:4
P3DISCARDCOUNT:0
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_221
P1RESAVAILABLE:0

---

# TwinSuns_TheGrantIsTaggedToONESeatAndDoesNotLeak
#// ⚠ THE SEAT-COUNT CELL — the other half of the same seam. A bare 'OTPF' names no grantee, so above two
#// seats the free play was available to whichever opponents happened to resolve OtherPlayer() onto that
#// pile — not to the one seat the card chose. The modifier now RECORDS its grantee
#// (SWUBuildDiscardModifier → "OTPF@1" above two seats; the bare string at ≤2 seats, so
#// SetsOtpfOnDefeat's `MODIFIER:OTPF` stays byte-identical).
#//
#// ⚠ THE ENCODING TRAP: a trailing DIGIT on a Modifier already means a COST DISCOUNT — 'TPP2' is
#//   TWI_201's "at cost, 2 resources less". So the grantee CANNOT be a bare number ('OTPF3' would read
#//   as OTPF-minus-3). It is '@N', which composes with the discount suffix and cannot collide.
#//
#// Seat 2 defeats SEAT 3's AT-Hauler. JTL_221's "Choose an opponent" is still an AUTO-PICK (its
#// interactive picker is card work, not seam work — see the card file's STILL OWED note), so the grant
#// goes to SWUChooseOpponent(3) = SEAT 1. Seat 2 therefore must NOT be able to play it, and the card
#// stays in seat 3's pile.
#// Mutation check: drop the SWUDiscardModifierGrantsTo gate and seat 2 steals the play — this reds.

## GIVEN
CommonSetup: grw/grw/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 2
WithGamePhase: ActionPhase
WithP2SpaceArena: SOR_237:1:0
WithP3SpaceArena: JTL_221:1:3
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P2>AttackSpaceArena:0:P3S0
- P2>PlayFromOpponentDiscard:P3:0

## EXPECT
SEATCOUNT:4
P3DISCARDCOUNT:1
P3DISCARDUNIT:0:MODIFIER:OTPF@1
P2SPACEARENACOUNT:0
P1SPACEARENACOUNT:0

---

# TwinSuns_ControllerNamesWhoMayReplayIt
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-24, completing the card the OTPF seam left half-done.
#// "When Defeated: CHOOSE AN OPPONENT. For this phase, they may play this unit from its owner's discard
#// pile for free." The seam made the grant RECORD its grantee ("OTPF@n"); this makes the controller
#// actually CHOOSE that seat instead of it auto-picking the first live opponent.
#// ⚠⚠ THE SOR_016 GATE, and why: cardDiscardedHandlers is DELIBERATELY SYNCHRONOUS — the Modifier must
#// land on the discard entry BEFORE any decision-queue drain, so a picker cannot simply be queued inside
#// it without reordering OpponentPlaysForFree. So ≤2 seats stamps immediately and is byte-identical
#// (SetsOtpfOnDefeat's `MODIFIER:OTPF` is untouched), and only >2 seats stamps a PROVISIONAL grant and
#// then queues the picker to RE-STAMP it. The provisional grant means the permission always exists, even
#// if the pick never resolves.
#// Seat 2 defeats seat 3's AT-Hauler; the controller (seat 3) names SEAT 4, so the entry must read OTPF@4
#// rather than the provisional OTPF@1.
#// ⚠ A 2-player version CANNOT FAIL — one opponent means no choice, which is exactly why the gate exists.
#// Mutation check: remove the >2-seat re-stamp branch and the modifier stays OTPF@1.

## GIVEN
CommonSetup: grw/grw/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithActivePlayer: 2
WithP2SpaceArena: SOR_237:1:0
WithP3SpaceArena: JTL_221:1:3
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P2>AttackSpaceArena:0:P3S0
- P3>AnswerDecision:P4

## EXPECT
SEATCOUNT:4
P3DISCARDCOUNT:1
P3DISCARDUNIT:0:MODIFIER:OTPF@4
