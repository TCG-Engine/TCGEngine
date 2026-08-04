# VISUAL CHECK — lunge distance clamp
#
# Visual-only schema. This is the acceptance check for the travel clamp added to playLunge
# (Core/CardMotion.js): without it, a long attacker->target vector sweeps the card across the screen.
# A SPACE unit attacking the opponent's base is the longest span a two-player board offers.
#
# What to look at:
#   • The TIE Fighter LEANS toward the enemy base — it must NOT fly most of the way there.
#   • Travel is capped at roughly 1.5x the card's own size, so it reads as a nudge.
#   • Compare against Lunge_UnitVsBase: a short lunge should look IDENTICAL to before the clamp
#     (the clamp is a no-op at ordinary distances).
#
# ⚠ This is the forward-looking mitigation for a 4-seat Twin Suns preview board, where the vector
# spans the whole table. That board is NOT on this branch, so this schema is the closest proxy.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:2
