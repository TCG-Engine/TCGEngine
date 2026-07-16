# PowerPerPilot
#// JTL_093 Nien Nunb — This unit gets +1/+0 for each other friendly Pilot unit and upgrade. With two other
#// Pilot units (JTL_034, JTL_035) in play, Nien Nunb (base 1) has power 3.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_093:1:0
WithP1GroundArena: JTL_034:1:0
WithP1GroundArena: JTL_035:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
