# JTL_003 Lando Calrissian (leader) — the Shield rider requires controlling BOTH a ground and a space
# unit after the play. Here P1 controls no units, then plays a space unit (SOR_237); it controls a
# space unit but no ground unit, so no Shield is granted. Proves the conjunctive condition.

## GIVEN
P1LeaderBase: JTL_003/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_237
WithP1Resources: 3

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:SHIELDCOUNT:0
P1RESAVAILABLE:0
P1LEADER:EXHAUSTED
P1NODECISION
