# Intimidator_TeammatesResourceGoesToTheTeammatesHand
#// LAW_140 Intimidator: "When Played: Return any number of friendly resources to their owners' hands." In
#// Team Suns a teammate's resource is friendly (user ruling 2026-08-26). Found by the 2026-09-11 game-log
#// pass: when a resource carries no Owner (fixtures, freshly-resourced cards), the handler fell back to the
#// ACTING player — so seat 3's card landed in seat 1's hand. SWUReturnResourceToHand already falls back to
#// the seat NAMED BY THE mzID; Intimidator now does the same.

## GIVEN
CommonSetup: grk/bbw/{myResources:11}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3Resources: 1
WithP1Hand: LAW_140

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3Resources-0

## EXPECT
P1HANDCOUNT:0
P3HANDCOUNT:1

---

# Lando_TeammatesResourceGoesToTheTeammatesHand
#// SOR_197 Lando Calrissian: "When Played: Return up to 2 friendly resources to their owners' hands." Same
#// fallback bug and fix as Intimidator above.

## GIVEN
CommonSetup: yyw/bbw/{myResources:6}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3Resources: 1
WithP1Hand: SOR_197

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3Resources-0

## EXPECT
P1HANDCOUNT:0
P3HANDCOUNT:1
