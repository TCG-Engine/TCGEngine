# ExpPerAspect
#// LAW_147 Jaunty Light Freighter (1/1, space) — When Played: give an Experience token to this unit for
#// each different aspect among units you control. SOR_095 (Command,Heroism) + SOR_225 (Villainy) + the
#// Freighter (Command,Heroism) = 3 distinct aspects -> 3 Experience (1/1 -> 4/4).

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_225:1:0
WithP1Hand: LAW_147

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:1:CARDID:LAW_147
P1SPACEARENAUNIT:1:UPGRADECOUNT:3
P1SPACEARENAUNIT:1:POWER:4
P1SPACEARENAUNIT:1:HP:4
