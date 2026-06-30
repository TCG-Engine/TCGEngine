# SOR_013 Cassian Andor — the leader Action costs 1 resource. With 0 ready resources it is a full
# no-op: the cost can't be paid, so the action never starts — Cassian stays READY (action not spent),
# nothing is drawn, and no decision is pending. Unaffordable-cost guard.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_128

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1NODECISION
P1HANDCOUNT:0
P1DECKCOUNT:1
