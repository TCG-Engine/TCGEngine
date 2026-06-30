# SOR_163 Star Wing Scout — the draw is gated on holding the initiative. Here P2 holds it
# (P1OnlyActions), so when the Scout is defeated in combat P1 draws nothing. Absence guard.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: SOR_163:1:0     # attacker, dies
WithP2SpaceArena: SOR_086:1:0     # Gladiator (5/6) — kills it back
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:0
P1DECKCOUNT:2
