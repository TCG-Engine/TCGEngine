# VISUAL CHECK — attack lunge, unit vs BASE
#
# Visual-only schema. The commonest attack in the game, and the case that would have stayed static if
# the lunge had been scoped to unit-vs-unit only.
#
# What to look at:
#   • The Dark Trooper leans toward the OPPONENT'S BASE (top of the board), not toward an empty lane.
#   • The base damage counter updates after the lean.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
