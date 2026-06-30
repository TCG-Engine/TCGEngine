# JTL_098 Snap Wexley — "When played as a unit/On Attack: The next Resistance card you play this phase
# costs 1 resource less." Played as a unit (no friendly Vehicle → no Pilot option), then P1 plays the
# Resistance unit JTL_099 (cost 3) which costs 2 thanks to the discount. Resource check: 10 − 3 (Snap)
# − 2 (discounted JTL_099) = 5 ready left (would be 4 without the discount).

## GIVEN
CommonSetup: ggw/rrk/{myResources:10;handCardIds:JTL_098}
P1OnlyActions: true
WithP1Hand: JTL_099

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:5
