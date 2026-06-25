# JTL_144 No Disintegrations (event) — the amount uses REMAINING HP, not max. SOR_046 (3/7) already has
# 2 damage → 5 remaining HP → takes 5−1=4 more → total 6 damage (left at 1). Distinguishes
# remaining-HP from max-HP.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_011;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_144
WithP1Resources: 3
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P2GROUNDARENACOUNT:1
