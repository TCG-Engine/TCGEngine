# SHD_123 Bounty Hunter's Quarry — collecting the granted Bounty. The enemy Battlefield Marine wears
# SHD_123; P1's Industrious Team (LAW_124, 4/7) attacks and defeats it. P1 collects the Bounty: "Search
# the top 5 of your deck (the marine isn't unique) for a unit costing 3 or less and play it for free."
# P1's deck top is Imperial Dark Trooper (SEC_080, cost 2 — the only matching unit; AT-AT Suppressor
# SOR_039 is too expensive). P1 has 0 resources, so the played unit proves it was free. SOR_039 is put
# on the bottom (deck still has 1 card).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_123
WithP1Deck: SEC_080
WithP1Deck: SOR_039

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:SEC_080

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1DECKCOUNT:1
