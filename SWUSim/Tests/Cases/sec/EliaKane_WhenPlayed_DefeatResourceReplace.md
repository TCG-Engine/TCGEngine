# SEC_242 Elia Kane (Ground, 3/6, Villainy) — Raid 1 + When Played: look at 3 enemy resources, may
#   defeat 1; if you do, its controller puts the top of their deck into play as a ready resource.
#   Defeat one of P2's 3 resources → P2 replaces from deck (net resource count unchanged, deck −1).

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 3:SOR_095:1
WithP2Deck: [SOR_095]
WithP1Hand: SEC_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P2RESCOUNT:3
P2DECKCOUNT:0
P2DISCARDCOUNT:1
P1NODECISION
