# SOR_047 Kanan Jarrus — "On Attack: You may discard 1 card from the defending player's deck for
# each friendly SPECTRE unit. Heal 1 damage from your base for each different aspect among the
# discarded cards." 2 friendly Spectre (Kanan + Chopper) → mill 2 from P2's deck (Aggression +
# Aggression/Villainy = 2 DISTINCT aspects) → heal 2 from P1's base (3 → 1). Kanan's 4 combat damage
# still hits P2's base.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: SOR_047:1:0
WithP1GroundArena: SOR_188:1:0
WithP2Deck: SOR_172
WithP2Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1BASEDMG:1
P2BASEDMG:4
P2DECKCOUNT:0
P2DISCARDCOUNT:2
