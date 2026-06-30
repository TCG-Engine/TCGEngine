# SOR_150 Heroic Sacrifice (Aggression/Heroism event, cost 1, Tactic) — "Draw a card, then attack with
# a unit. For this attack, it gets +2/+0 and gains: 'When this unit deals combat damage: Defeat it.'"
# P1 draws (deck 1 → 0, hand → 1), the attacker (SOR_095, 3/3) gets +2/+0 → deals 5 to the enemy base,
# then is defeated by its granted self-defeat trigger (even though the base dealt no counter-damage).

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_237
WithP1Hand: SOR_150

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECKCOUNT:0
P1HANDCOUNT:1
P2BASEDMG:5
P1GROUNDARENACOUNT:0
