# WhenPlayed_TwoSpy
#// SEC_191 Trade Federation Delegates (Ground, 3/?, Cunning/Villainy, cost 5) — When Played: create 2 Spy tokens.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SEC_191

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1NODECISION
