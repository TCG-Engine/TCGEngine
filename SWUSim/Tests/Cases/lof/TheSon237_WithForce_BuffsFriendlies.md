# LOF_237 The Son (6/8) — "While the Force is with you, each friendly unit gets +2/+0." With the Force,
# The Son (6 → 8) and a friendly SOR_095 (3 → 5) both gain +2 power; HP is unchanged.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithP1Force: true
WithP1GroundArena: LOF_237:1:0
WithP1GroundArena: SOR_095:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:8
P1GROUNDARENAUNIT:0:HP:8
P1GROUNDARENAUNIT:1:POWER:5
