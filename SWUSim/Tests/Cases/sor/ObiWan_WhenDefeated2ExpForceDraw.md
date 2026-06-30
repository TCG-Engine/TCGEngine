# SOR_049 Obi-Wan Kenobi (4/6) — When Defeated: give 2 Experience tokens to another
# friendly unit; if it's a Force unit, draw a card. Obi-Wan (pre-damaged to 1 remaining
# HP) attacks P2's Battlefield Marine and dies in the exchange. His only other friendly
# is Count Dooku (SOR_038, a Force unit) → auto-gets +2/+2 (5/4 → 7/6) and P1 draws.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1GroundArena: SOR_038:1:0    # Force recipient (5/4) — index 0 (stays put)
WithP1GroundArena: SOR_049:1:5    # Obi-Wan, 5 damage → 1 remaining HP — index 1
WithP2GroundArena: SOR_095:1:0    # defender (3/3)

## WHEN
- P1>AttackGroundArena:1:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:6
P1HANDCOUNT:1
