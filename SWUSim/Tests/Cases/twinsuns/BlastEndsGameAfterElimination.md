# BlastDamageCountsInEndOfPhaseScoring
#// Twin Suns — from the report on game 1311538 (2026-09-26): "game ended abruptly when P3 took the
#// Blast counter". INVESTIGATED AND NOT A BUG. This file pins the correct behaviour so nobody
#// "fixes" it later, and covers the one step that had no test.
#//
#// CR 12.7.1: "Once one player is eliminated, the game will end once the current phase ends. The
#// player with the most HP remaining on their base at the end of the current phase wins the game."
#// CR 12.5.1.c: in a three-player game the third player NECESSARILY takes the last counter, ending
#// the phase. In 1311538 seat 2 had been eliminated earlier, so the game was already destined to end
#// at the next phase boundary; P3's Blast was simply the action that reached it.
#//
#// ⚠ WHAT WAS UNCOVERED, AND WHAT THIS SECTION IS FOR: the blast's damage must be counted by the
#// scoring. Blast deals 1 to EACH opponent's base, so it is dealt DURING the phase and therefore
#// lands before "HP remaining at the end of the phase" is read. Phase5.md's elimination section
#// reaches the boundary with a bare ScorePhaseEnd and so can never see this ordering.
#//
#// All three live bases are EQUAL here on purpose, so the blast is what decides the result: without
#// its damage this is a three-way tie that SHARES the victory (the control below), and with it P3
#// wins alone. Verified against the real board of 1311538, where the damage loop runs before the
#// scoring read (remain 26/30/26 -> P3).
#//
#// ⚠ WithEliminatedSeats, NOT WithLiveSeats — only a REAL elimination arms SWU_TS_GAME_ENDING, and
#// that flag is the whole mechanism; WithLiveSeats just writes the list and nothing would score.

## GIVEN
CommonSetup: grw/ggk/{myBase:SOR_019}
WithSeatOrder: 1234
WithEliminatedSeats: 2
WithP3Base: SOR_019
WithP4Base: SOR_019
WithP1Deck: [SOR_180 SOR_182 SOR_184]
WithP3Deck: [SOR_180 SOR_182 SOR_184]
WithP4Deck: [SOR_180 SOR_182 SOR_184]
WithActivePlayer: 3
WithGamePhase: ActionPhase

## WHEN
- P3>TakeCounter:blast
- P1>ScorePhaseEnd

## EXPECT
P1BASEDMG:1
P3BASEDMG:0
P4BASEDMG:1
GAMEWINNERS:3

---

# NoBlast_TiedBasesShareTheVictory
#// THE DISCRIMINATOR. Identical board, identical elimination, but nobody takes the Blast — so the
#// three live bases stay tied and CR 12.7.3 shares the victory between all of them. If the section
#// above ever passes while this one also reports a single winner, the scoring has stopped reading
#// base damage at all and the pair is no longer measuring anything.

## GIVEN
CommonSetup: grw/ggk/{myBase:SOR_019}
WithSeatOrder: 1234
WithEliminatedSeats: 2
WithP3Base: SOR_019
WithP4Base: SOR_019
WithP1Deck: [SOR_180 SOR_182 SOR_184]
WithP3Deck: [SOR_180 SOR_182 SOR_184]
WithP4Deck: [SOR_180 SOR_182 SOR_184]
WithActivePlayer: 3
WithGamePhase: ActionPhase

## WHEN
- P1>ScorePhaseEnd

## EXPECT
P1BASEDMG:0
P3BASEDMG:0
P4BASEDMG:0
GAMEWINNERS:1,3,4

---

# NoElimination_PhaseEndDoesNotEndTheGame
#// The other half of CR 12.7.1: with NOBODY eliminated, reaching the end of a phase must not end the
#// game, however much base damage the Blast has dealt. Without this, both sections above would pass
#// for a build that scores at every phase boundary.

## GIVEN
CommonSetup: grw/ggk/{myBase:SOR_019}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP3Base: SOR_019
WithP4Base: SOR_019
WithP1Deck: [SOR_180 SOR_182 SOR_184]
WithP3Deck: [SOR_180 SOR_182 SOR_184]
WithP4Deck: [SOR_180 SOR_182 SOR_184]
WithActivePlayer: 3
WithGamePhase: ActionPhase

## WHEN
- P3>TakeCounter:blast
- P1>ScorePhaseEnd

## EXPECT
NOGAMEWINNER
SWUVAR:SWU_TS_GAME_ENDING:
