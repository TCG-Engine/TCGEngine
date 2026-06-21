# SEC_112 Orn Free Taa — "This unit gets +1/+0 for each Law card in your discard pile." Two Law cards
#   (SEC_126 ×2) plus a non-Law card (SOR_095) sit in the discard → +2 (not +3) → power 2.

## GIVEN
CommonSetup: ggk/rrk/{discardCardIds:SEC_126,SEC_126,SOR_095}
WithActivePlayer: 1
WithP1GroundArena: SEC_112:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
