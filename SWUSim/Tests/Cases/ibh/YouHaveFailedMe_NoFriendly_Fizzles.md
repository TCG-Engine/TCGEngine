# IBH_095 You Have Failed Me — with no friendly unit to defeat, the event fizzles cleanly (no decision).

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: IBH_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1NODECISION
