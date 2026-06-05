# SOR_143 Fighters for Freedom — a NON-Aggression card does NOT trigger the reaction.
# Absence guard: Confiscate is a neutral event (no Aggression aspect), so FFF stays silent.

## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:SOR_251}
WithP1GroundArena: SOR_143:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:0
P2BASEDMG:0
P1NODECISION
