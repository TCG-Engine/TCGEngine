# WhenPlayed_LeastHP_SelfExp
#// SEC_244 Darth Nihilus (Ground, 6/6, Villainy, cost 7) — When Played/On Attack: deal 3 to the OTHER
#//   unit with the least remaining HP; if it's a non-Vehicle unit, give an Experience token to this unit.
#//   Lowest is SOR_128 (1 HP, non-Vehicle) → defeated; Nihilus gets +1 Experience → 7 power.

## GIVEN
CommonSetup: rrk/grw/{myResources:7}
P1OnlyActions: true
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_244

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_244
P1GROUNDARENAUNIT:0:POWER:7
P2GROUNDARENACOUNT:1
P1NODECISION
