# JTL_008 Wedge Antilles (leader) — Action [Exhaust]: Play a card from your hand using Piloting. It
# costs 1 resource less. P1 plays JTL_108 (pure Pilot, Piloting cost 2, Command — covered by Wedge) as
# an upgrade onto the Munificent Frigate for 2 − 1 = 1 resource, leaving 0. With only 1 ready resource,
# this play is ONLY possible because of the −1 discount (full cost 2 would be unaffordable).

## GIVEN
P1LeaderBase: JTL_008/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP1Hand: JTL_108
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_069
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_108
P1RESAVAILABLE:0
P1HANDCOUNT:0
P1LEADER:EXHAUSTED
