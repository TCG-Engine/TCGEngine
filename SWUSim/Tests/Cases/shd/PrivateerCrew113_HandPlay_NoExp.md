# SHD_113 Privateer Crew played normally FROM HAND — the "using Smuggle" gate means NO Experience
# tokens: plain 2/2.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_113

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:2
