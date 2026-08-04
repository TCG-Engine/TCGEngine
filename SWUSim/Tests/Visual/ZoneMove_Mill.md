# VISUAL CHECK — zone slide, deck -> discard (mill)
#
# Visual-only schema. IC27_187 Jar Jar Binks: "On Attack: Discard a card from your deck."
#
# What to look at:
#   • Jar Jar LEANS at the base (lunge) and the top deck card flies to the discard pile.
#   • Both animations belong to the same action — watch that they read as one beat, not a queue.

## GIVEN
CommonSetup: ggw/ggw/{}
P1OnlyActions: true
WithP1GroundArena: IC27_187:1:0
WithP1Deck: [SOR_049 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1DISCARDCOUNT:1
