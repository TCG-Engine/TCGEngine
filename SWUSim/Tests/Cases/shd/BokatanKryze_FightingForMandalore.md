# BoKatan_WhenDefeated_BoundaryBelow15
#// SHD_157 Bo-Katan Kryze — Unit, cost 2, 3/3, unique, Ground, [Aggression][Aggression],
#// traits Mandalorian/Trooper. "When Defeated: For each player with 15 or more damage on their base,
#// draw a card."
#// COVERAGE: offer=N/A (no target pick — the count is derived from both bases and the draw is automatic) ·
#//           request boundary=N/A (the whole When-Defeated resolves inside the attack with no decision) ·
#//           control=N/A (a When-Defeated on a non-unique-effect unit that never changes controller; the
#//           count is per-PLAYER-base, not per-controller) ·
#//           boundary pair=BoKatan_WhenDefeated_BoundaryBelow15 (14 does not qualify) +
#//           BoKatan_WhenDefeated_DrawPerDamagedBase (15 does) ·
#//           decline=N/A (mandatory draw, no "you may").
#// SHD_157 Bo-Katan Kryze — the threshold is "15 or more". P1's base has 15 (qualifies); P2's base has
#// 14 (does NOT). So SHD_157's defeat draws exactly 1.

## GIVEN
CommonSetup: rrk/rrk/{myBaseDamage:15;theirBaseDamage:14}
P1OnlyActions: true
WithP1GroundArena: SHD_157:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1HANDCOUNT:1

---

# BoKatan_WhenDefeated_DrawPerDamagedBase
#// SHD_157 Bo-Katan Kryze — "When Defeated: For each player with 15 or more damage on their base, draw a
#// card." Both bases start at 15 damage. SHD_157 (3/3) attacks a Wampa (SOR_164 4/5): deals 3 (survives),
#// counters 4 → SHD_157 dies. Its When Defeated counts both bases (15 ≥ 15) → P1 draws 2.

## GIVEN
CommonSetup: rrk/rrk/{myBaseDamage:15;theirBaseDamage:15}
P1OnlyActions: true
WithP1GroundArena: SHD_157:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1HANDCOUNT:2

---

# BoKatan_WhenDefeated_EmptyDeck_ThreeDamagePerFailedDraw
#// SHD_157 Bo-Katan Kryze — the draws still happen against an EMPTY deck, and each failed draw deals
#// 3 damage to the drawing player's own base. Both bases sit at 15 so both qualify → SHD_157's defeat
#// asks for 2 cards with nothing left to draw: P1's hand stays empty and P1's base goes 15 → 21.

## GIVEN
CommonSetup: rrk/rrk/{myBaseDamage:15;theirBaseDamage:15}
P1OnlyActions: true
WithP1GroundArena: SHD_157:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1HANDCOUNT:0
P1BASEDMG:21

---

# TwinSuns_CountsEVERYSeatsBase
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-21 during the "an opponent"/"a player" sweep. "For each PLAYER
#// with 15 or more damage on their base" is a per-player loop, and it was written `foreach ([1, 2] as $p)`.
#// At four seats that counted seats 1 and 2 only, so a damaged seat-3 or seat-4 base drew nothing — the
#// card silently paid out less than it should in every Twin Suns game.
#// Fixed to GetLiveSeatsArray(), which returns [1,2] in a 2-seat game, so Premier is untouched (and the
#// two existing sections above needed no edit — that is the check that the conversion was safe).
#//
#// Board: P1's base is clean, seats 2, 3 and 4 all sit on 15+. Bo-Katan trades into a 3/3, and her
#// When Defeated must draw THREE — one per qualifying seat, including the two the old loop could not see.
#// ⚠ A 2-player version of this section CANNOT fail: with only seats 1 and 2 the old and new loops are
#//   the same list. The seat count IS the test.
#// ⚠ FIXTURE: seats 3 and 4 have no base at all unless WithP3Base/WithP4Base seeds one — and this
#//   section needs the DAMAGE on it, which only those directives carry (CommonSetup's theirBaseDamage
#//   reaches seat 2 only).

## GIVEN
CommonSetup: rrk/rrk/{theirBaseDamage:15}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SHD_157:1:0
WithP2GroundArena: SOR_095:1:0
WithP3Base: SOR_019:15
WithP4Base: SOR_019:15
WithP1Deck: [SOR_095 SOR_046 SEC_080 SOR_128 SOR_237]

## WHEN
- P1>AttackGroundArena:0:P2G0

## EXPECT
SEATCOUNT:4
P1GROUNDARENACOUNT:0
P1HANDCOUNT:3
