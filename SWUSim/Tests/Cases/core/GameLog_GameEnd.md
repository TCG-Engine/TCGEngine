# BaseDefeated_ByCombat_WritesTheWinLine
#// Game-log follow-up (2026-09-11). SWUDeclareGameWinner only set the end-game overlay's flash message, so
#// an ordinary base kill ended the game with NO log line (only Confidence in Victory and Final Showdown wrote
#// one, themselves). The WIN line is now written in SWUDeclareGameWinner, once, for every path.

## GIVEN
CommonSetup: rrk/rrk/{theirBaseDamage:29}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
GAMEWINNERS:1
LOGCONTAINS:P1 wins the game (P2's base was defeated)
LOGCOUNT:1:wins the game
#// The win is decided during combat damage, BEFORE the ATTACK summary is written — it must still read last.
LASTLOGCONTAINS:P1 wins the game

---

# Concede_TwoSeats_WritesTheWinLine
#// Concede (EngineActionRunner input 10006 → TriggerGameOver) called GameLogEvent / WriteLog, which SWUSim
#// never defines — a concession left no trace in the log at all.

## GIVEN
CommonSetup: rrk/rrk
WithGamePhase: ActionPhase
WithActivePlayer: 1

## WHEN
- P2>Concede

## EXPECT
GAMEWINNERS:1
LOGCONTAINS:P1 wins the game (P2 conceded)

---

# Concede_FourSeats_ConcededThenEliminated
#// Twin Suns: a concession eliminates the seat (the game goes on), so the log says who conceded, then the
#// existing elimination line. (Fixture from twinsuns/ConcedeEliminatesInsteadOfEndingTheGame.md.)

## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP3Base: SOR_019
WithP4Base: SOR_019

## WHEN
- P1>Concede

## EXPECT
NOGAMEWINNER
LOGCONTAINS:P1 conceded
LOGCONTAINS:Player 1 has been eliminated!
LOGCOUNT:0:wins the game

---

# FinalShowdown_OneWinLine
#// The card-specific WIN lines moved INTO SWUDeclareGameWinner's reason — guard against a second line.
#// SHD_208 Final Showdown: "... At the start of the regroup phase, you lose the game."

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SHD_208

## WHEN
- P1>PlayHand:0
- P1>Pass

## EXPECT
P2WIN
LOGCONTAINS:P2 wins the game (P1 — Final Showdown)
LOGCOUNT:1:wins the game
