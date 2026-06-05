# SOR_115 Agent Kallus — uniqueness gate: defeating a NON-unique unit does NOT trigger the draw.
# Kallus (4/4) attacks a non-unique SOR_128 (3/1) and defeats it → no reactive, no draw, no decision.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_115:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1DECKCOUNT:1
P1HANDCOUNT:0
P1NODECISION
