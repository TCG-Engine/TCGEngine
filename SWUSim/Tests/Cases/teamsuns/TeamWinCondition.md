# FirstKO_DoesNotEndTheGame
#// THE headline rule: in Team Suns the round does NOT end when a player is eliminated. Seat 2 is
#// knocked out and the game continues with three live seats and no winner. In Twin Suns this same
#// elimination sets SWU_TS_GAME_ENDING and scores at the next phase boundary.
#// ⚠ NOGAMEWINNER ALONE IS VACUOUS HERE — measured: it stays green with the team gate mutated off,
#// because Twin Suns' scoring is DEFERRED to a phase boundary this fixture never reaches, so "no
#// winner yet" is true under both rules. SWUVAR:SWU_TS_GAME_ENDING: is the discriminator: Team Suns
#// must never set the flag at all.

## GIVEN
CommonSetup: rrk/bbw/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0

## WHEN
- P1>EliminateSeat:2

## EXPECT
SEATCOUNT:4
NOGAMEWINNER
SWUVAR:SWU_TS_GAME_ENDING:
SEATLIVE:1:true
SEATLIVE:2:false
SEATLIVE:3:true
SEATLIVE:4:true

---

# SecondKO_OnATeam_EndsTheGameImmediately
#// Both BLUE seats are knocked out, so RED wins the moment the second one falls — no deferred
#// end-of-phase scoring and no base-HP comparison. The winner is a SET: both surviving Red seats.

## GIVEN
CommonSetup: rrk/bbw/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0

## WHEN
- P1>EliminateSeat:2
- P1>EliminateSeat:4

## EXPECT
GAMEWINNERS:1,3

---

# ATeamWinsEvenWithOneMemberKnockedOut
#// "A team can still win if their teammate is knocked out." RED loses seat 3 first, then wipes BLUE.
#// The winner set is the SURVIVING team's LIVE seats — seat 1 alone, not both Red seats.
#// ⚠ THIS SECTION CANNOT DISCRIMINATE THE TEAM GATE, and that is documented rather than papered over.
#// Measured: it stays GREEN with `if (SWUIsTeamGame())` mutated to `if (false)`. When exactly one seat
#// survives, the two rules CONVERGE COMPLETELY — Twin Suns' single-survivor safety net scores highest
#// base HP among live=[1] and reaches the same winner, and _SWUScoreTwinSunsEndOfPhase() CONSUMES
#// SWU_TS_GAME_ENDING on its way out, so the flag reads empty under both rules too. Neither the winner
#// nor the flag can tell them apart. It is kept because it pins the RULE ("a team can still win if
#// their teammate is knocked out"); the gate itself is discriminated by FirstKO_DoesNotEndTheGame and
#// SecondKO_OnATeam_EndsTheGameImmediately, and the intermediate state by OneMemberDown_GameContinues.

## GIVEN
CommonSetup: rrk/bbw/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0

## WHEN
- P1>EliminateSeat:3
- P1>EliminateSeat:2
- P1>EliminateSeat:4

## EXPECT
GAMEWINNERS:1
SWUVAR:SWU_TS_GAME_ENDING:

---

# OneMemberDown_GameContinues
#// The DISCRIMINATING half of "a team can still win if their teammate is knocked out": stop while TWO
#// seats are still live, one per team. Team Suns must have declared nothing and set no flag. Twin Suns
#// would have set SWU_TS_GAME_ENDING on the very first elimination — and with two seats live its
#// safety net does NOT fire, so the flag is still '1' and this section reds under the gate mutation.

## GIVEN
CommonSetup: rrk/bbw/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0

## WHEN
- P1>EliminateSeat:3
- P1>EliminateSeat:2

## EXPECT
NOGAMEWINNER
SWUVAR:SWU_TS_GAME_ENDING:
SEATLIVE:1:true
SEATLIVE:2:false
SEATLIVE:3:false
SEATLIVE:4:true

---

# EliminatingAnOpponentHealsTheKillerFive
#// CR §12.6.2 is unchanged in Team Suns: the eliminating player heals 5 from their own base.
#// P1's base starts at 8 damage and drops to 3.

## GIVEN
CommonSetup: rrk/bbw/{myBaseDamage:8}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0

## WHEN
- P1>EliminateSeat:2:1

## EXPECT
P1BASEDMG:3

---

# ASameTeamKillerHealsNobody
#// A teammate finishing off their own partner heals no one — same carve-out as CR §12.6.2's
#// self-elimination rule. Seat 1 "kills" its RED teammate at seat 3; seat 1's base damage is untouched.
#// Unreachable by attack (a teammate cannot be attacked) but reachable via card effects.

## GIVEN
CommonSetup: rrk/bbw/{myBaseDamage:8}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0

## WHEN
- P1>EliminateSeat:3:1

## EXPECT
P1BASEDMG:8

---

# TwinSunsControl_FirstKOStillEndsTheGame
#// THE CONTROL — the same first elimination in a NON-team 4-player game must still flag the game as
#// ending and score by base HP. This is what proves the change is gated on SWU_MODE_TEAMS and that
#// Twin Suns' deferred scoring was not broken.

## GIVEN
CommonSetup: rrk/bbw/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0

## WHEN
- P1>EliminateSeat:2

## EXPECT
SEATCOUNT:4
SEATLIVE:2:false
