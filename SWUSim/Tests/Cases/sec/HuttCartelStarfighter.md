# WhenPlayed_SelfDamage
#// SEC_240 Hutt Cartel Starfighter (Ground, 3/5, Villainy, cost 3) — When Played: deal 2 to this unit.

## GIVEN
CommonSetup: rrk/grw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SEC_240

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SEC_240
P1SPACEARENAUNIT:0:DAMAGE:2
P1NODECISION
