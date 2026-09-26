# ConcedeDoesNotArmTheWinCondition
#// Owner ruling 2026-09-26: neither a KICK nor a CONCEDE counts toward the Twin Suns win condition.
#//
#// CR 12.7.1 ends the game at the next phase boundary once a player is ELIMINATED, scoring by highest
#// remaining base HP. Both administrative exits routed straight through SWUEliminateSeat, so either
#// one handed the game to whoever happened to lead on base HP while everybody else was still playing.
#// Reported on game 1311538 (a KICK; the log reads "P2 was removed for inactivity" and only then
#// "Player 2 has been eliminated!"), and concede is the same shape — concede to end the game and gift
#// the leader a win.
#//
#// A conceding player is still OUT: they leave LiveSeats. What must not happen is the remaining
#// players having the result decided for them.

## GIVEN
CommonSetup: grw/ggk/{myBase:SOR_019}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP3Base: SOR_019
WithP4Base: SOR_019
WithP1Deck: [SOR_180 SOR_182 SOR_184]
WithP3Deck: [SOR_180 SOR_182 SOR_184]
WithP4Deck: [SOR_180 SOR_182 SOR_184]
WithActivePlayer: 1
WithGamePhase: ActionPhase

## WHEN
- P2>Concede
- P1>ScorePhaseEnd

## EXPECT
SEATLIVE:2:false
SEATLIVE:1:true
SEATLIVE:3:true
SEATLIVE:4:true
NOGAMEWINNER
SWUVAR:SWU_TS_GAME_ENDING:

---

# RealEliminationStillArmsTheWinCondition
#// THE CONTROL. A genuine elimination must still end the game at the phase boundary — the ruling
#// narrows WHICH exits count, it does not switch CR 12.7.1 off. Without this, the section above
#// passes for a build that has simply stopped ending Twin Suns games at all.
#// P3 and P4 carry base damage, so the survivor ranking is unambiguous and P1 wins outright.

## GIVEN
CommonSetup: grw/ggk/{myBase:SOR_019}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP3Base: SOR_019:6
WithP4Base: SOR_019:9
WithP1Deck: [SOR_180 SOR_182 SOR_184]
WithP3Deck: [SOR_180 SOR_182 SOR_184]
WithP4Deck: [SOR_180 SOR_182 SOR_184]
WithActivePlayer: 1
WithGamePhase: ActionPhase

## WHEN
- P1>EliminateSeat:2
- P1>ScorePhaseEnd

## EXPECT
GAMEWINNERS:1

---

# RealElimination_WarnsThatTheGameIsEnding
#// Owner request 2026-09-26: players had no way to know an elimination put the game on a timer. In
#// 1311538 the removal and the abrupt ending were three turns apart, so nothing on screen connected
#// them. A REAL elimination now says so in the log.

## GIVEN
CommonSetup: grw/ggk/{myBase:SOR_019}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP3Base: SOR_019
WithP4Base: SOR_019
WithActivePlayer: 1
WithGamePhase: ActionPhase

## WHEN
- P1>EliminateSeat:2

## EXPECT
LOGCONTAINS:Player 2 has been eliminated!
LOGCONTAINS:The game will end at the end of this phase
LOGCOUNT:1:The game will end at the end of this phase
#// The TYPE is the styling: the client builds the row class as 'swu-log-' + type, and ELIMINATED is
#// the red one. Owner, 2026-09-26: "only genuine eliminations should be red to create the urgency of
#// the end game."
LOGTYPE:ELIMINATED:Player 2 has been eliminated!

---

# Concede_DoesNotWarnThatTheGameIsEnding
#// The warning must track the RULING, not the word "eliminated": a conceding player still leaves the
#// game and still gets an eliminated line, but the game is NOT ending, so promising that it will
#// would be a lie shown to every remaining player.

## GIVEN
CommonSetup: grw/ggk/{myBase:SOR_019}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP3Base: SOR_019
WithP4Base: SOR_019
WithActivePlayer: 1
WithGamePhase: ActionPhase

## WHEN
- P2>Concede

## EXPECT
SEATLIVE:2:false
LOGCONTAINS:Player 2 has been eliminated!
LOGCOUNT:0:The game will end at the end of this phase
#// …and NEUTRAL, not red. A concession removes the seat but creates no end-game urgency, so it must
#// not wear the colour that means "this phase is the last one" (owner, 2026-09-26).
LOGTYPE:REMOVED:Player 2 has been eliminated!
