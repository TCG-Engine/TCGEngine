# SOR_150 Heroic Sacrifice — with no unit able to attack (only an EXHAUSTED unit present), the draw
# still happens but there is no attack and no self-defeat. The exhausted unit survives.

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1Deck: SOR_237
WithP1Hand: SOR_150

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECKCOUNT:0
P1HANDCOUNT:1
P1GROUNDARENACOUNT:1
P2BASEDMG:0
P1NODECISION
