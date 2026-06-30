# LOF_237 The Son (6/8) — negative: without the Force, no buff — The Son stays 6 power and the friendly
# SOR_095 stays 3 power.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithP1GroundArena: LOF_237:1:0
WithP1GroundArena: SOR_095:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:1:POWER:3
