# JTL_144 No Disintegrations (event) — Deal damage to a non-leader unit equal to 1 less than its
# remaining HP. SOR_046 (3/7, undamaged) has 7 remaining HP, so it takes 6 (left at 1 HP). Auto-resolves
# (only one non-leader unit in play).

## GIVEN
P1LeaderBase: JTL_011/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_144
WithP1Resources: 3
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:6
P2GROUNDARENACOUNT:1
