# SOR_076 Make an Opening — Give a unit −2/−2 for this phase. Heal 2 damage from your base.
# Single unit in play (enemy AT-AT, 9/9) → auto-target: power 9−2=7, HP 9−2=7.
# P1 base starts at 3 damage → healed by 2 → 1.

## GIVEN
CommonSetup: bbw/bbw/{myBaseDamage:3;myResources:3;handCardIds:SOR_076}
WithP2GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1BASEDMG:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:7
P2GROUNDARENAUNIT:0:HP:7
