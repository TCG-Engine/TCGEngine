# VISUAL CHECK — attack lunge, unit vs BASE
#
# Visual-only schema. The commonest attack in the game, and the case that would have stayed static if
# the lunge had been scoped to unit-vs-unit only.
#
# What to look at:
#   • The Dark Trooper leans toward the OPPONENT'S BASE (top of the board), not toward an empty lane.
#   • The base damage counter updates after the lean.
#   • Exhausting is the attack's COST, so the Trooper is ALREADY TILTED (9°) AND SHADED when the lean
#     starts — it must not lunge bolt upright and full-brightness, then tip over and darken once the
#     motion ends. This is the single-request case (one legal target, no prompt), where the exhaust and
#     the lunge share one animation batch — the path where the pose used to arrive late.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
