# SEC_013 Luthen Rael (leader front) — "When a friendly unit is defeated while attacking: You may exhaust
# this leader. If you do, deal 1 damage to a unit or base." P1's SOR_128 (3/1) attacks SOR_063 (2/4
# Sentinel) and dies to the 2 counter-damage. P1 exhausts Luthen and deals 1 to the enemy base.

## GIVEN
P1LeaderBase: SEC_013/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:1
P1GROUNDARENACOUNT:0
P1LEADER:EXHAUSTED
