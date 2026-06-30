# SOR_062 Regional Governor — the "can't play the named card" block also covers cards-played-by-
# effects (not just from hand). P1 plays Governor and names "Battlefield Marine". On P2's turn, P2
# plays U-Wing Reinforcement (SOR_104), which searches the top 10 and plays up to 3 units for free.
# P2's deck top has two Battlefield Marines (SOR_095) — both are BLOCKED, so neither enters play;
# they go back to the deck. (The U-Wing event still resolves and goes to P2's discard.)

## GIVEN
CommonSetup: bbw/ggw/{myResources:2;theirResources:7}
WithP1Hand: SOR_062
WithP2Hand: SOR_104
WithP2Deck: SOR_095
WithP2Deck: SOR_095
WithP2Deck: SOR_171
WithP2Deck: SOR_171
WithP2Deck: SOR_171
WithP2Deck: SOR_171
WithP2Deck: SOR_171
WithP2Deck: SOR_171
WithP2Deck: SOR_171
WithP2Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P2>PlayHand:0
- P2>AnswerDecision:SOR_095,SOR_095

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P2DECKCOUNT:10
P2DISCARDCOUNT:1
