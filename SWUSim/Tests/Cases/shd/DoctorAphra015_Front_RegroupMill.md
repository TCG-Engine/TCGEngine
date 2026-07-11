# SHD_015 Doctor Aphra (leader FRONT side, undeployed) — "When the regroup phase starts: Discard a card
#   from your deck." Both players pass to reach regroup; at RegroupPhaseStart Aphra mills 1 (deck→discard,
#   From:DECK) before the draw step. Deck 6 → -1 mill -2 regroup-draw = 3 left; discard holds the 1 milled.

## GIVEN
CommonSetup: yyk/rrk/{myLeader:SHD_015}
WithActivePlayer: 1
WithP1Deck: [SOR_095 SEC_080 SOR_128 SOR_046 LAW_180 SOR_063]

## WHEN
- P1>Pass
- P2>Pass

## EXPECT
P1DISCARDCOUNT:1
P1DECKCOUNT:3
