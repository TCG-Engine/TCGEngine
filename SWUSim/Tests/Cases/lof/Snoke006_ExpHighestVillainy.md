# LOF_006 Supreme Leader Snoke — Action [1 resource, Exhaust]: Give an Experience token to the unit with the
# most power among friendly Villainy units. SOR_038 (Villainy, power 5) is the only Villainy unit → +1/+1.

## GIVEN
P1LeaderBase: LOF_006/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SOR_038:1:0
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:POWER:6
P1RESAVAILABLE:0
