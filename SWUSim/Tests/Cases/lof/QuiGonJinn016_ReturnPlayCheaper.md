# LOF_016 Qui-Gon Jinn — Action [Exhaust, use the Force]: Return a friendly non-leader unit to hand, then
# play a non-Villainy unit that costs less from your hand for free. P1 returns SOR_046 (cost 4) and plays
# SOR_059 (cost 1, Vigilance) for free.

## GIVEN
P1LeaderBase: LOF_016/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: SOR_059
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_059
P1HANDCOUNT:1
P1NOFORCE
