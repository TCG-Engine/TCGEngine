# SHD_115 — the search finds no unit costing 2 or less (deck top is only a Wampa, cost 4). The search
# resolves with no valid pick (PASS): nothing is discarded from the deck. Only SHD_115 itself sits in
# the discard, and it is not free-playable.

## GIVEN
CommonSetup: ggk/ggk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SHD_115:1:0
WithP1Deck: SOR_164
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_115
P1DECKCOUNT:3
