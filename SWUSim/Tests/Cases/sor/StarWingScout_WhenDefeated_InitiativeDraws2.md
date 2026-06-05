# SOR_163 Star Wing Scout (4/1, Space) — When Defeated: If you have the initiative, draw 2
# cards. P1 holds the initiative. The Scout attacks the Gladiator Star Destroyer (5/6): it
# deals 4 (Gladiator survives) and takes 5 (1 HP → defeated). Because P1 has the initiative,
# its When Defeated draws 2 (hand 0 → 2, deck −2).

## GIVEN
CommonSetup: ggw/ggw
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithActivePlayer: 1
WithP1SpaceArena: SOR_163:1:0     # Star Wing Scout (ready) — attacker, dies
WithP2SpaceArena: SOR_086:1:0     # Gladiator (5/6) — kills it back, survives
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:2
P1DECKCOUNT:0
