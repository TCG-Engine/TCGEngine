# SOR_013 Cassian Andor — the threshold is 3 (not "any"). P1's Alliance X-Wing (SOR_237, 2 power)
# deals only 2 to P2's base, below the bar. The leader action is still used — Cassian exhausts and
# pays 1 resource (1 → 0) — but the condition fails, so NO card is drawn (deck stays 1, hand stays 0).
# Distinguishes "3 or more" from a buggy ">0" / ">=1".

## GIVEN
P1LeaderBase: SOR_013/SOR_024
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1SpaceArena: SOR_237:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:2
P1HANDCOUNT:0
P1DECKCOUNT:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
