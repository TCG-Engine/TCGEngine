# ExhaustedSmuggleBehindReady_Protected
#// SHD_114 Scanning Officer — the Ready-first reveal protects a Smuggle resource kept EXHAUSTED. P2 has
#// 3 ready non-Smuggle resources (SOR_095) + 1 exhausted Smuggle card (SHD_129). Scanning Officer reveals
#// the 3 ready ones; the exhausted Smuggle is NOT among the revealed 3, so it is never defeated. P2 keeps
#// all 4 resources, nothing goes to discard and nothing is replaced — this is the incentive to keep your
#// Smuggle cards exhausted vs Scanning Officer.

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

---

# NoSmuggleRevealed_NoOp
#// SHD_114 Scanning Officer — fizzle: if none of the 3 revealed resources has the Smuggle keyword, nothing
#// is defeated and nothing is replaced (no crash, no dangling decision). P2 has 3 ready non-Smuggle
#// resources (SOR_095); all 3 are revealed but none is Smuggle, so P2 is untouched.

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

---

# ReadySmuggleRevealed_DefeatedReplacedExhausted
#// SHD_114 Scanning Officer — When Played: reveal 3 enemy resources and defeat each REVEALED Smuggle one,
#// its controller replacing it from deck AS A RESOURCE (exhausted, NOT readied — the key difference from
#// SEC_242 Elia Kane). P2 has 2 ready non-Smuggle resources (SOR_095) + 1 ready Smuggle card (SHD_129).
#// All 3 are revealed (only 3 exist); the Smuggle one is defeated and P2 replaces it from deck with an
#// EXHAUSTED resource — so P2 keeps 3 resources but only 2 are ready, the Smuggle card now in discard,
#// deck −1.

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

---

# ThreeReadySmuggle_AllDefeatedReplacedExhausted
#// SHD_114 Scanning Officer — "defeat EACH revealed Smuggle resource" defeats more than one. P2 has 3
#// ready Smuggle cards (SHD_129); all 3 are revealed and all 3 are defeated, and for each one P2 puts the
#// top of their deck into play as an EXHAUSTED resource. P2 ends with 3 resources again but all exhausted
#// (none ready), 3 Smuggle cards in discard, deck emptied.

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
