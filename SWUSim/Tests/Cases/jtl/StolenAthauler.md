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
