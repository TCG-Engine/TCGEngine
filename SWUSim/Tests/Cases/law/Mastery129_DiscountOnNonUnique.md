# LAW_129 Mastery (Upgrade, +3/+3, cost 4, Vigilance) — "This upgrade costs 1 resource less to play on
# a non-unique unit." Played onto SEC_080 (non-unique) with only 3 resources → the discount makes it
# affordable, it attaches (+3/+3 → 6/6) and all resources are spent.

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_129

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6
P1RESAVAILABLE:0
