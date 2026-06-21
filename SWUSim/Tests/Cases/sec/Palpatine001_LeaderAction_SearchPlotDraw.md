# SEC_001 Chancellor Palpatine (leader) — Action [1 resource, Exhaust]: Search the top 5 cards of your
# deck for a card with Plot, reveal it, and draw it (rest to the bottom in a random order).
# Deck top-5 = SEC_034 (Plot) + 4 vanilla SOR_095; only SEC_034 matches the Plot filter → drawn.
# Costs 1 resource (2 ready → 1) and exhausts the leader.

## GIVEN
P1LeaderBase: SEC_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Deck: [SEC_034 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:SEC_034

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:5
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED
P1NODECISION
