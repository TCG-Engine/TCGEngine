# Bounty_DrawCard
#// SHD_095 Clone Deserter (1-cost 2/3, Restore 1 + "Bounty — Draw a card"). Battlefield Marine
#// (3/3) defeats it exactly; P1 collects and draws the seeded card. Restore 1 is registry-covered.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_095:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DECKCOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# TwinSuns_TheKILLERCollectsNotSeat1
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-23. USER RULING 2026-08-23: above two seats, the player who
#// DEFEATED the unit collects its Bounty. At two seats "the opponent of the defeated unit's controller"
#// was a complete answer; above two seats owner, controller and killer are THREE DIFFERENT SEATS and the
#// old `OtherPlayer($controller)` named one of them — seat 1 for any far-seat controller.
#//
#// ⚠⚠ WHY THIS SECTION IS SEAT 3 ATTACKING SEAT 4, and not P1 attacking anyone:
#//   with P1 as the killer the old code is RIGHT BY ACCIDENT — OtherPlayer(2)==1==P1, and even
#//   OtherPlayer(3)==1==P1. A four-seat bounty section built the obvious way PASSES under the bug.
#//   (SHD_161's existing four-seat section does exactly that, and its green is worth nothing.)
#//   Only a kill where the killer is NOT seat 1 can tell the two rules apart.
#//
#// Seat 3's Battlefield Marine (3/3) attacks and defeats seat 4's Clone Deserter (2/3, "Bounty — Draw a
#// card"). SEAT 3 must be offered the bounty and draw. Seat 1 must be untouched.
#// Under the old rule: OtherPlayer(4) == 1, so SEAT 1 — a player with no involvement in the combat at
#// all — was offered the bounty and drew the card.
#// Mutation check: revert SWUBountyCollector to OtherPlayer($defeatedController) and this reds while
#// every 2-player bounty section stays green.

## GIVEN
CommonSetup: rrk/rrk/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 3
WithGamePhase: ActionPhase
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SHD_095:1:0
WithP3Deck: [SOR_141]
WithP1Deck: [SOR_141]
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P3>AttackGroundArena:0:P4G0
- P3>AnswerDecision:YES

## EXPECT
SEATCOUNT:4
P4GROUNDARENACOUNT:0
P3HANDCOUNT:1
P3DECKCOUNT:0
P1HANDCOUNT:0
P1DECKCOUNT:1
