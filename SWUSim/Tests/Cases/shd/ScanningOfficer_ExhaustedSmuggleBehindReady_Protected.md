# SHD_114 Scanning Officer — the Ready-first reveal protects a Smuggle resource kept EXHAUSTED. P2 has
# 3 ready non-Smuggle resources (SOR_095) + 1 exhausted Smuggle card (SHD_129). Scanning Officer reveals
# the 3 ready ones; the exhausted Smuggle is NOT among the revealed 3, so it is never defeated. P2 keeps
# all 4 resources, nothing goes to discard and nothing is replaced — this is the incentive to keep your
# Smuggle cards exhausted vs Scanning Officer.

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 3:SOR_095:1,1:SHD_129:0
WithP2Deck: SEC_080
WithP1Hand: SHD_114

## WHEN
- P1>PlayHand:0

## EXPECT
P2RESCOUNT:4
P2RESAVAILABLE:3
P2DISCARDCOUNT:0
P2DECKCOUNT:1
