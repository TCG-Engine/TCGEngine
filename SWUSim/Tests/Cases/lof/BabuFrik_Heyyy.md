# Action_HPAsDamage
#// LOF_206 — Action [Exhaust]: Attack with a friendly Droid; for this attack it deals damage equal to
#// its remaining HP instead of its power. The Droid SOR_188 (1/3) deals 3 (its HP) to the enemy base
#// instead of 1 (its power). LOF_206 exhausts to pay the action; SOR_188 exhausts from attacking.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_206:1:0
WithP1GroundArena: SOR_188:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
