# SEC_010 Dedra Meero (leader) — Action [1 resource, Exhaust]: Choose an enemy unit. Its controller may
# deal 2 damage to it. If they don't, draw a card. Here the opponent DECLINES (NO) → P1 draws a card and
# the enemy unit is undamaged.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:SEC_010;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 2
WithP1Deck: [SOR_095]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P2>AnswerDecision:NO

## EXPECT
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED
