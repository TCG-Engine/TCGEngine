# SEC_010 Dedra Meero (leader) — the opponent ACCEPTS (YES) → its controller deals 2 damage to its own
# unit, and P1 does NOT draw.

## GIVEN
P1LeaderBase: SEC_010/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 2
WithP1Deck: [SOR_095]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1HANDCOUNT:0
P1LEADER:EXHAUSTED
