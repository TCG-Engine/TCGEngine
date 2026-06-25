# SEC_008 Bail Organa (leader) — the effect is conditional: "If a friendly unit was defeated this phase".
# With no friendly unit defeated, the action still pays its cost and exhausts the leader (like Iden), but
# returns no resource and ramps nothing: resource COUNT unchanged, no decision, hand empty.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:SEC_008;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Deck: [SOR_095 SOR_095]
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:2
P1RESCOUNT:3
P1HANDCOUNT:0
P1DECKCOUNT:2
P1NODECISION
