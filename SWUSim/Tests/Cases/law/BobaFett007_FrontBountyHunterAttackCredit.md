# LAW_007 Boba Fett (leader front) — "When a friendly Bounty Hunter unit's attack ends: If the defending
# unit was defeated, you may exhaust this leader. If you do, create a Credit token." LAW_124 (4/7 Bounty
# Hunter) attacks and defeats SOR_128 (3/1); P1 exhausts Boba to create a Credit.

## GIVEN
P1LeaderBase: LAW_007/SOR_028
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:1
P1LEADER:EXHAUSTED
