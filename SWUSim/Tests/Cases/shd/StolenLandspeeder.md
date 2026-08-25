# Bounty_ReplayFromDiscard_Exp
#// SHD_161 Stolen Landspeeder — full loop. P1 plays it from hand → P2 takes control. P1's Consular
#// Security Force (3/7) then defeats it (3 ≥ HP 2; counter 3 back). P1 owns the defeated unit (it
#// went to P1's discard), so collecting the bounty plays it from P1's discard FOR FREE and gives it
#// an Experience token (3/2 → 4/3). This replay is from DISCARD, not hand, so the When Played
#// control-flip does NOT fire — it stays under P1's control.

## GIVEN
CommonSetup: grw/grw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SHD_161

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SHD_161
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:3
P1DISCARDCOUNT:0

---

# PlayedFromHand_OpponentControls
#// SHD_161 Stolen Landspeeder — "When Played: If you played this unit from your hand, an opponent
#// takes control of it." P1 plays it from hand (1 resource, Aggression covered by the rw leader);
#// it moves to P2's ground arena, still exhausted from entering play.

## GIVEN
CommonSetup: grw/grw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_161

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SHD_161
P2GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:0

---

# TwinSuns_BountyGoesToTheKILLER_WhoDoesNotOwnIt
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-24. This section was **UNWRITABLE** until today: it needs a
#// far-seat owner/controller SPLIT (`WithP3GroundArenaControlled`) and a FAR SEAT as the sole actor
#// (`P4OnlyActions`), and the harness supported neither — the defect was invisible by construction.
#//
#// SHD_161 stacks two independent seat rules, which is what makes it the sharp case:
#//   • "An opponent takes control of it" ⇒ OWNER and CONTROLLER are different seats.
#//   • "Bounty — IF YOU OWN THIS UNIT, play it from your discard for free…" ⇒ the reward is gated on the
#//     COLLECTOR owning it.
#// So the answer depends on OWNER, CONTROLLER and KILLER all at once — three distinct seats above two.
#//
#// Board: the Landspeeder is CONTROLLED by seat 3 and OWNED by seat 1. SEAT 4 attacks and defeats it.
#//   • USER RULING (killer collects) ⇒ collector = SEAT 4, who does NOT own it ⇒ bounty collected but the
#//     "if you own this unit" reward does NOT fire. The card stays in seat 1's discard.
#//   • Old rule (OtherPlayer(controller 3) = 1) ⇒ collector = SEAT 1, who DOES own it ⇒ it would be
#//     replayed for free with an Experience token. That is the bug, and it is a real swing: a free 4/3.
#// ⚠ Seat 4 is the killer precisely so that killer ≠ owner ≠ controller. With seat 1 attacking, both rules
#//   agree and the section would pass against the bug — the same accident that makes the pre-existing
#//   four-seat bounty section on this card worthless as evidence.
#// ⚠ A 2-player version CANNOT EXIST — three distinct seats are required.
#// Mutation check: revert SWUBountyCollector to OtherPlayer($defeatedController) and this reds.

## GIVEN
CommonSetup: grw/grw/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
P4OnlyActions: true
WithP3GroundArenaControlled: SHD_161:1:1
WithP4GroundArena: SOR_046:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P4>AttackGroundArena:0:P3G0
- P4>AnswerDecision:YES

## EXPECT
SEATCOUNT:4
P3GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P4GROUNDARENACOUNT:1

---

# TwinSuns_CasterChoosesWhoTakesTheLandspeeder
#// ⚠ THE SEAT-COUNT CELL for the take-control half — added 2026-08-24. "When Played: if you played this
#// unit from your hand, AN OPPONENT takes control of it." OtherPlayer() picked one silently, so on a
#// four-seat table the caster could not choose who to arm with a 3/2 — and this card is played FOR that
#// drawback, so aiming it is the whole decision.
#// ⚠ NO $eligible filter: any live opponent can take control of a ground unit. The one near-miss —
#//   SWUTakeControlOfUnit returning '' when LAW_149 Rey blocks it — is a property of the UNIT, not of any
#//   opponent, so it does not vary per seat and must not become a per-opponent gate.
#// ⚠ Carried by UID: the pick is interactive and the arena can reindex before the continuation runs.
#// P1 plays it and gives it to SEAT 3. It must land on seat 3's board, not seat 2's, while remaining
#// OWNED by P1 — that Owner is what the Bounty's "if you own this unit" reward later reads.
#// ⚠ A 2-player version CANNOT FAIL — one opponent means no choice to get wrong.
#// Mutation check: revert to OtherPlayer() and this reds.

## GIVEN
CommonSetup: grw/grw/{myResources:1}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Hand: SHD_161
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:4
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P3GROUNDARENACOUNT:1
P3GROUNDARENAUNIT:0:CARDID:SHD_161
