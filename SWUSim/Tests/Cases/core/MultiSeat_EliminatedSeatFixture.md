#// CORE — `WithEliminatedSeats`, the fixture directive for "this seat was already dead when the test
#// starts". It runs the REAL engine path (SWUEliminateSeat → _SWUEliminationCleanup) rather than a
#// hand-rolled approximation, so a fixture can never drift from what an elimination actually does.
#//
#// ⚠ WHY IT EXISTS, AND WHY `WithLiveSeats` IS NOT ENOUGH. WithLiveSeats writes the live-seat LIST and
#// nothing else, so the dead seat keeps its units, its base, its decision queue and its TempZone. Every
#// consumer that filters by LiveSeats looks right; every consumer that reads a seat's board DIRECTLY —
#// GetBase($seat), a raw arena scan, the `their<Zone>` accessor — sees a board that cannot exist in a
#// real game. That is a fixture convenience that silently disables a whole assertion class, so the two
#// directives are pinned side by side below on an identical board.

# LiveSeatsAlone_LeavesTheDeadSeatsBoardStanding
#// THE CONTRAST PARTNER (and the reason the new directive is worth having). `WithLiveSeats: 12` marks
#// seat 3 not-live, but its unit is STILL IN ITS ARENA and its base is still readable. This section
#// documents the existing behaviour deliberately — it must keep passing, because plenty of fixtures
#// legitimately want "not live" without the cleanup.

## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithLiveSeats: 12
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_046:1:0

## WHEN

## EXPECT
SEATCOUNT:3
SEATLIVE:3:false
P3GROUNDARENACOUNT:1

---

# EliminatedSeat_UnitsAndBaseAreGone
#// The same board through `WithEliminatedSeats: 3`. Now the cleanup has actually run: seat 3's arena is
#// EMPTY and it is no longer live. Compare P3GROUNDARENACOUNT with the section above — 1 there, 0 here,
#// on byte-identical seeding. That single number is the whole difference between the two directives.

## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithEliminatedSeats: 3
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_046:1:0

## WHEN

## EXPECT
SEATCOUNT:3
SEATLIVE:3:false
SEATLIVE:1:true
SEATLIVE:2:true
P3GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1

---

# EliminatedSeat_MindControlledUnitReturnsToItsOwnersDiscard
#// The cleanup's second branch: a unit the dead seat CONTROLLED but did not OWN goes to its OWNER's
#// discard rather than vanishing (CR — it leaves play, trigger-free). Seat 3 controls a unit owned by
#// seat 2; after the elimination seat 3's arena is empty and seat 2 has it in discard.
#// ⚠ Without this section the fix could set every unit on the dead board to `removed` and still look
#// correct, quietly destroying a card that belongs to a living player.

## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithEliminatedSeats: 3
WithActivePlayer: 1
WithP3GroundArenaControlled: SOR_046:2

## WHEN

## EXPECT
SEATCOUNT:3
P3GROUNDARENACOUNT:0
P2DISCARDCOUNT:1

---

# FourSeat_TwoEliminatedSeats
#// The directive takes a DIGIT STRING, exactly like WithSeatOrder / WithLiveSeats, so more than one
#// seat can start dead. Seats 3 and 4 are both eliminated; only seats 1 and 2 remain.

## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithEliminatedSeats: 34
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0

## WHEN

## EXPECT
SEATCOUNT:4
SEATLIVE:1:true
SEATLIVE:2:true
SEATLIVE:3:false
SEATLIVE:4:false
P3GROUNDARENACOUNT:0
P4GROUNDARENACOUNT:0

---

# ThreeSeat_DefeatedPlayerDoesNotDeclareAWinner
#// `WithDefeatedPlayer` is a SEPARATE, two-player directive: base damage = base HP, and the other
#// player wins. It computed that winner as `$dp === 1 ? 2 : 1`, so at three seats a defeated SEAT 3
#// declared SEAT 1 the winner out of nowhere.
#//
#// Above two seats a full base does not mean anyone has won — it means that seat is eliminated, which
#// is what `WithEliminatedSeats` is for. So the directive now sets the damage and declares NO winner.
#// ⚠ NOWINNER, not NOGAMEWINNER. They read DIFFERENT things: NOWINNER is the 2-player `$gWinner`
#// scalar that this directive writes; NOGAMEWINNER is the Twin Suns winner SET (SWUGetGameWinners).
#// Asserting only the set let the old hardcode survive mutation — it sets the scalar, which the set
#// never sees. Both are asserted here so neither half can drift.

## GIVEN
CommonSetup3P: grw/ggk/ggk
SkipPreGame: true
WithDefeatedPlayer: 3
WithActivePlayer: 1

## WHEN

## EXPECT
SEATCOUNT:3
P3BASEDMG:30
NOWINNER
NOGAMEWINNER
