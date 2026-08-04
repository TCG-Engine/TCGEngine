# VISUAL CHECK — zone slide, arena -> discard (defeat in COMBAT)
#
# Visual-only schema. The 3/1 Stormtrooper deals 3 to the 3/7 wall and dies to its 3-power counter.
# This exercises the COMBAT defeat path
# (_SWUCombatFinishAction), which is separate from the ability-defeat path in SWUDefeatUnit.
#
# What to look at:
#   • The defeated Stormtrooper flies from the ground arena into P1's discard pile.
#   • The surviving defender stays put and just takes damage.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
