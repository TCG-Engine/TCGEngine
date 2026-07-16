# DiscardCaptive
#// SHD_243 Altering the Deal (1-cost event, Villainy) — "Discard a captured card guarded by a friendly unit."
#// P1's Discerning Veteran (SHD_120) first captures SOR_128; Altering the Deal then discards that captive to
#// its owner P2's discard pile.

## GIVEN
CommonSetup: ggk/ggk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_120
WithP1Hand: SHD_243
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_120
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_128
