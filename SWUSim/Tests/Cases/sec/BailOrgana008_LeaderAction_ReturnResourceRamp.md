# SEC_008 Bail Organa (leader) — Action [1 resource, Exhaust]: If a friendly unit was defeated this phase,
# return a friendly resource to its owner's hand. If you do, put the top card of your deck into play as a
# resource. P1's SOR_128 (3/1) attacks SOR_063 (2/4 Sentinel) and dies to the 2 counter-damage (friendly
# defeated this phase). P1 then returns a resource (→ hand) and ramps the deck top as a resource.
# Net resource count unchanged (return −1, ramp +1); hand +1; deck −1.

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
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0
- P1>UseLeaderAbility
- P1>AnswerDecision:myResources-0

## EXPECT
P1RESCOUNT:3
P1HANDCOUNT:1
P1DECKCOUNT:1
P2GROUNDARENACOUNT:1
P1LEADER:EXHAUSTED
