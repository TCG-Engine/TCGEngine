# SOR_178 Cartel Spacer (2/3, Space) — When Played: If you control another [Cunning] unit,
# exhaust an enemy unit that costs 4 or less. P1 already controls Outer Rim Headhunter
# (SOR_208, Cunning), so the condition holds; the enemy Battlefield Marine (cost 2) is the
# only ≤4-cost enemy unit and is exhausted. Automatic (not optional).

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_178
WithP1SpaceArena: SOR_208:1:0     # another Cunning unit (condition) — idx 0
WithP2GroundArena: SEC_080:1:0    # enemy unit, cost 2 (≤4) — exhaust target

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
