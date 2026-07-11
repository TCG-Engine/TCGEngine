# SHD_122 Arquitens Assault Cruiser (Unit, Space, cost 8, Command, 7/8, Ambush)
#   "When this unit attacks and defeats a non-leader unit: Put the defeated unit into play as a resource
#    under your control."
# SHD_122 (7 power) attacks P2's TIE Fighter (SOR_225, 2/1) and defeats it. Instead of the TIE going to
# P2's discard, it enters P1's resource zone (exhausted, controlled by P1) — so P2's discard stays empty
# and P1's resource count rises by 1. SHD_122 survives the 2 counter-damage (DAMAGE:2).

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1SpaceArena: SHD_122:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:0
P1RESCOUNT:3
P1SPACEARENAUNIT:0:DAMAGE:2
