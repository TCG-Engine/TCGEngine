# SHD_114 Scanning Officer — fizzle: if none of the 3 revealed resources has the Smuggle keyword, nothing
# is defeated and nothing is replaced (no crash, no dangling decision). P2 has 3 ready non-Smuggle
# resources (SOR_095); all 3 are revealed but none is Smuggle, so P2 is untouched.

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 3:SOR_095:1
WithP2Deck: SEC_080
WithP1Hand: SHD_114

## WHEN
- P1>PlayHand:0

## EXPECT
P2RESCOUNT:3
P2RESAVAILABLE:3
P2DISCARDCOUNT:0
P2DECKCOUNT:1
