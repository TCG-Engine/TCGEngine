# SOR_080 General Tagge (2/2) — When Played with no Trooper units in play: the
# ability fizzles (no targets), so no decision is queued and Tagge simply enters
# play. P1's only other unit is a non-Trooper (Restored ARC-170, Vehicle).

## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:SOR_080}
P1OnlyActions: true
WithP1SpaceArena: SOR_044:1:0    # Restored ARC-170 (Vehicle — not a Trooper)

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1SPACEARENAUNIT:0:POWER:2
