# VISUAL CHECK — zone slide, hand -> resources
#
# Visual-only schema. Uses the regroup resource step, the commonest way a card becomes a resource.
#
# What to look at:
#   • The chosen card flies from the hand down into the resource row.
#   • It lands EXHAUSTED (sideways) — resources put into play by an effect enter exhausted.

## GIVEN
CommonSetup: bbk/bbk/{myhandCardIds:SEC_080}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_046 SOR_128 SOR_237]
WithP2Deck: [SOR_095 SOR_046 SOR_128 SOR_237]

## WHEN
- P1>Pass
- P1>ResourceHand:0
- P2>ResourcePass

## EXPECT
P1RESCOUNT:1
