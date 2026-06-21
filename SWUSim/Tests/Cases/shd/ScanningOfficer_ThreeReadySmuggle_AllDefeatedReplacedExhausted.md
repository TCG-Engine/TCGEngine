# SHD_114 Scanning Officer — "defeat EACH revealed Smuggle resource" defeats more than one. P2 has 3
# ready Smuggle cards (SHD_129); all 3 are revealed and all 3 are defeated, and for each one P2 puts the
# top of their deck into play as an EXHAUSTED resource. P2 ends with 3 resources again but all exhausted
# (none ready), 3 Smuggle cards in discard, deck emptied.

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 3:SHD_129:1
WithP2Deck: [SEC_080 SOR_095 SOR_100]
WithP1Hand: SHD_114

## WHEN
- P1>PlayHand:0

## EXPECT
P2RESCOUNT:3
P2RESAVAILABLE:0
P2DISCARDCOUNT:3
P2DECKCOUNT:0
