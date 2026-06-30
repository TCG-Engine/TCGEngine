# IBH_086 We're In Trouble (reprint of IBH_061) — deal 3 to a unit. Confirms the duplicate is wired.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_086
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
