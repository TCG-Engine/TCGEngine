# Concede_StampsConcede
#// Meta Premier (docs/superpowers/specs/2026-10-03-swusim-metapremier-ratings-design.md §4.2): the rating
#// layer must tell a concession from an inactivity removal. Both end through TriggerGameOver.

## GIVEN
CommonSetup: rrk/rrk
WithGamePhase: ActionPhase
WithActivePlayer: 1

## WHEN
- P2>Concede

## EXPECT
GAMEWINNERS:1
GAMEOVERREASON:concede
GAMEDETAIL:endReason:concede
GAMEDETAIL:pregameDone:true

---

# Kick_StampsAbandon
#// An inactivity removal is an ABANDON — a rated loss plus a penalty, not an ordinary concession.

## GIVEN
CommonSetup: rrk/rrk
WithGamePhase: ActionPhase
WithActivePlayer: 1

## WHEN
- P2>Kick

## EXPECT
GAMEWINNERS:1
GAMEOVERREASON:abandon
GAMEDETAIL:endReason:abandon

---

# BaseDefeat_StaysWin

## GIVEN
CommonSetup: rrk/rrk/{theirBaseDamage:29}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
GAMEWINNERS:1
GAMEOVERREASON:win
GAMEDETAIL:endReason:win

---

# ConcedeAfterWin_KeepsWin
#// The loser clicking Concede on the end screen must not relabel a base kill as a concession.

## GIVEN
CommonSetup: rrk/rrk/{theirBaseDamage:29}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>Concede

## EXPECT
GAMEWINNERS:1
GAMEOVERREASON:win

---

# KickAfterWin_KeepsWin
#// Same for a removal vote that lands after the game is already decided.

## GIVEN
CommonSetup: rrk/rrk/{theirBaseDamage:29}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>Kick

## EXPECT
GAMEWINNERS:1
GAMEOVERREASON:win

---

# ActionPhase_PregameDone
#// MainPhase() runs when the action phase opens, which is where pregame ends. This harness builds the
#// post-pregame state directly (it cannot hold a game mid-mulligan), so the "not yet done" half — a match
#// conceded during mulligans goes unrated — is pinned end to end in DevTools/tdd-regression/test_metapremier_queue_http.php.

## GIVEN
CommonSetup: rrk/rrk
WithGamePhase: ActionPhase
WithActivePlayer: 1

## EXPECT
PREGAMEDONE:1
#// Derived from the round + phase, never stored: DecisionQueueVariables is deterministic-RNG material, so a stored
#// flag would change every game's random stream after round 1 (final review #7).
DQVARABSENT:SWU_PREGAME_DONE
