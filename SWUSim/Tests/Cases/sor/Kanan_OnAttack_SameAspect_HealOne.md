# SOR_047 Kanan Jarrus — heal is per DISTINCT aspect, not per card. 2 friendly Spectre (Kanan +
# Chopper) mill 2 cards that share the SAME single aspect (Aggression + Aggression) → only 1
# distinct aspect → heal 1 (NOT 2). Guards the distinct-vs-count logic.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: SOR_047:1:0
WithP1GroundArena: SOR_188:1:0
WithP2Deck: SOR_172
WithP2Deck: SOR_172

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1BASEDMG:2
P2BASEDMG:4
P2DECKCOUNT:0
P2DISCARDCOUNT:2
