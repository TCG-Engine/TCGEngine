# VISUAL CHECK — damage badges on units and bases
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor to eyeball the damage counters.
#
# Sets up damaged units in both arenas (mine + opponent's) and damaged bases on
# both sides, so every damage display fires at once:
#   • Unit damage  → red center badge (GroundArena/SpaceArena Damage counter, Opacity 0.8),
#                    alongside the always-on power (bottom-left) and HP (bottom-right) badges.
#   • Base damage  → red top-right badge (Base Damage counter).
# Damage is kept below each unit's HP so nothing is defeated and the cards stay on the board:
#   SOR_095 Battlefield Marine 3/3 (Ground) → 2 damage (1 remaining)
#   SOR_237 Alliance X-Wing    3/2 (Space)  → 1 damage (1 remaining)
#
# What to look at:
#   • Each damaged unit shows its center damage badge on top of the art, with the
#     new power/HP badges sitting over the printed corner stats.
#   • Both bases show a top-right damage badge.
#   • No WHEN steps — the initial GIVEN state is the whole check.

## GIVEN
CommonSetup: bbk/grw/{myBaseDamage:8;theirBaseDamage:5}
WithP1GroundArena: SOR_095:1:2
WithP1SpaceArena: SOR_237:1:1
WithP2GroundArena: SOR_095:1:2
WithP2SpaceArena: SOR_237:1:1

## WHEN

## EXPECT
P1GROUNDARENACOUNT:1
P1SPACEARENACOUNT:1
P2GROUNDARENACOUNT:1
P2SPACEARENACOUNT:1
