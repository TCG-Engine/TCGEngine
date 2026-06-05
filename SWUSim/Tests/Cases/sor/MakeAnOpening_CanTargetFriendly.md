# SOR_076 Make an Opening — "a unit" means ANY unit, friendly included (unlike Disarm).
# Only a friendly unit in play (AT-AT, 9/9) → auto-target it: power 9−2=7, HP 9−2=7.
# Base heal still applies (3 → 1).

## GIVEN
CommonSetup: bbw/bbw/{myBaseDamage:3;myResources:3;handCardIds:SOR_076}
WithP1GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:7
