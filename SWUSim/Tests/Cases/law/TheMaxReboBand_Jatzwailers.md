# RegroupCredit
#// LAW_071 The Max Rebo Band (1/5) — When the regroup phase starts: create a Credit token. Pass to
#// regroup -> 1 Credit.

## GIVEN
CommonSetup: gyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_071:1:0

## WHEN
- P1>Pass

## EXPECT
P1CREDITCOUNT:1
