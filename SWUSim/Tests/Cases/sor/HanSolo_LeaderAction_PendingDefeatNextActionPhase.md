# SOR_017 Han Solo — Leader Action delayed downside:
# "...At the start of the next action phase, defeat a resource you control."
# Han ramps (hand card → ready resource, 3 → 4). Both players pass → regroup phase runs
# (draw, resource, ready). At the start of the NEXT action phase Han's pending trigger fires:
# the player must defeat one resource they control (mandatory, player chooses which).
# Resources 4 → 3, defeated resource goes to discard.
#
# NOTE (phase-crossing): ending the action phase pauses auto-advance at the Resource step
# (each player has a "resource up to 1 card" MZMAYCHOOSE that does not auto-resolve), so both
# players must answer with ResourcePass before the cycle reaches Ready → next Action phase.

## GIVEN
CommonSetup: gyw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Resources: 3
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>UseLeaderAbility
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:myResources-0

## EXPECT
P1RESCOUNT:3
P1RESAVAILABLE:3
P1DISCARDCOUNT:1
