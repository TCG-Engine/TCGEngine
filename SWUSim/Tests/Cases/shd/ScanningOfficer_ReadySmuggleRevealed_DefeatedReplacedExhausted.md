# SHD_114 Scanning Officer — When Played: reveal 3 enemy resources and defeat each REVEALED Smuggle one,
# its controller replacing it from deck AS A RESOURCE (exhausted, NOT readied — the key difference from
# SEC_242 Elia Kane). P2 has 2 ready non-Smuggle resources (SOR_095) + 1 ready Smuggle card (SHD_129).
# All 3 are revealed (only 3 exist); the Smuggle one is defeated and P2 replaces it from deck with an
# EXHAUSTED resource — so P2 keeps 3 resources but only 2 are ready, the Smuggle card now in discard,
# deck −1.

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 2:SOR_095:1,1:SHD_129:1
WithP2Deck: SEC_080
WithP1Hand: SHD_114

## WHEN
- P1>PlayHand:0

## EXPECT
P2RESCOUNT:3
P2RESAVAILABLE:2
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SHD_129
P2DECKCOUNT:0
