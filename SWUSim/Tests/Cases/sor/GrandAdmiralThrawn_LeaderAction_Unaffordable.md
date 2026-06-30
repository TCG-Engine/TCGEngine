# SOR_016 Grand Admiral Thrawn — Leader Action costs [1 resource, exhaust]. With 0 ready
# resources the cost cannot be paid, so the action is a no-op: the leader stays ready,
# nothing is queued, and the player keeps their action.

## GIVEN
CommonSetup: gyk/grw
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_128

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1RESCOUNT:0
P1NODECISION
