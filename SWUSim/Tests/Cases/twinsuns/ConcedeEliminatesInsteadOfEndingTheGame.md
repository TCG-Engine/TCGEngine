# Concede_AtFourSeats_EliminatesTheConcedingSeat_NotAnInstantWin
#// Bug report #985: "conceding P1 gave P2 an auto-win, but P3 and P4 were still playing."
#//
#// ROOT CAUSE: the concede entry point ends the game with a two-seat assumption.
#//     function TriggerGameOver($loserPlayer) {
#//         $winner = ($loserPlayer == 1) ? 2 : 1;      // <- no seat-count branch at all
#//         SWUDeclareGameWinner($winner);
#//     }
#// EngineActionRunner's input 10006 (Concede) calls exactly that, so P1 conceding declared P2 the
#// outright winner and stopped a game two other people were still playing.
#//
#// The CORRECT shape already exists one file over, for base defeat (CombatLogic ~line 500), and that
#// site is properly guarded:
#//     if (SeatCountForGame() > 2) SWUEliminateSeat(...); else SWUDeclareGameWinner(...);
#// Concede simply never got the same branch. SWUEliminateSeat owns the whole Twin Suns rule — drop the
#// seat from LiveSeats, release any counter it held, and flag deferred end-of-phase scoring (CR 12.7).
#//
#// A concession has no killer, so nobody heals 5 — SWUEliminateSeat's own "self-elimination /
#// no-damager heals no one" carve-out — which is why $killer is passed as null.
#//
#// Here seat 1 concedes a four-seat game. Seat 1 goes out; seats 2, 3 and 4 are ALL still live, and no
#// instant winner is declared.

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
SEATLIVE:1:false
SEATLIVE:2:true
SEATLIVE:3:true
SEATLIVE:4:true

---

# Concede_AtFourSeats_TheOpponentDoesNotInstantlyWin
#// The half the reporter actually saw. The section above proves seat 1 left; this one proves the GAME
#// did not end with seat 2 handed the victory.
#// NOWINNER is the exact property that was violated before the fix — a bystander seat
#// cannot win merely because another seat quit, and with three seats still live nothing is decided yet.

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
NOWINNER
SEATLIVE:1:false

---

# Concede_AtTWOSeats_StillHandsTheWinToTheOpponent
#// THE PREMIER CONTROL, and the reason the fix is a seat-count BRANCH rather than a replacement.
#// At two seats a concession genuinely is an immediate loss — there is nobody else left to play — so
#// this must keep working exactly as before. It passes both before and after the fix, and it is what
#// stops the Twin Suns branch from being "fixed" by simply deleting the win.
#// ⚠ SWUEliminateSeat returns immediately at <= 2 seats, so routing everything through it would have
#// silently made a 2-player concede do NOTHING. This section is what catches that.

## GIVEN
CommonSetup: rrk/bbw
WithActivePlayer: 1
WithInitiativePlayer: 1

## WHEN
- P1>Concede

## EXPECT
P2WIN

---

# Concede_AtTWOSeats_TheOtherDirectionToo
#// The mirror, because `($loserPlayer == 1) ? 2 : 1` is right in both directions at two seats and a
#// rewrite could easily preserve only the one the first section happens to exercise.

## GIVEN
CommonSetup: rrk/bbw
WithActivePlayer: 1
WithInitiativePlayer: 1

## WHEN
- P2>Concede

## EXPECT
P1WIN

---

# Concede_AtFourSeats_NOBODYHeals_ThereIsNoEliminator
#// A base defeat heals whoever landed the killing blow 5 (CR 12.6.2). A CONCESSION has no damager, so
#// that reward must not be handed out — nobody "beat" the player who quit.
#//
#// SWUEliminateSeat only heals when `$killer !== null`, and TriggerGameOver passes null. That is easy
#// to state and easy to get wrong later (passing the acting seat, or the next seat in order, would look
#// perfectly reasonable), and nothing else in this file would notice — the other sections assert who is
#// live and who won, never base damage.
#//
#// Every surviving seat starts on damage and must END on the same damage: seat 2 on 6, seat 3 on 7,
#// seat 4 on 8. Three distinct values so a heal applied to the WRONG seat is still visible.

## GIVEN
CommonSetup: rrk/bbw/{theirBaseDamage:6; myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP3Base: SOR_019:7
WithP4Base: SOR_019:8

## WHEN
- P1>Concede

## EXPECT
SEATLIVE:1:false
P2BASEDMG:6
P3BASEDMG:7
P4BASEDMG:8
