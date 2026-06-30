# SOR_245 Medal Ceremony — guard: no eligible target. Only a non-Rebel Imperial Trooper (SOR_128)
# attacked, so the Rebel-attacked target list is empty → the event fizzles with no decision and no
# token. The event still resolves into the discard pile.

## GIVEN
CommonSetup: byw/byw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP1Hand: SOR_245

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3
P1NODECISION
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1
