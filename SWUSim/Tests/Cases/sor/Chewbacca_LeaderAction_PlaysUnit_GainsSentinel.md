# SOR_003 Chewbacca (leader) — Action [exhaust]: Play a unit that costs 3 or less from your hand
# (paying its cost). It gains Sentinel for this phase. P1 uses the leader action: Chewbacca exhausts,
# the only ≤3 hand unit SOR_237 Alliance X-Wing (Heroism, cost 2 — Chewbacca covers Heroism) is
# played for its full 2 (2 ready → 0), enters the space arena, and gains Sentinel via the SOR_003
# turn-effect token.

## GIVEN
P1LeaderBase: SOR_003/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_237

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
P1HANDCOUNT:0
