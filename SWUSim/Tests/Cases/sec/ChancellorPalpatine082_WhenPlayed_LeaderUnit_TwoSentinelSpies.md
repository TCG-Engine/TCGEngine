# SEC_082 Chancellor Palpatine (Ground, 2/2, Command/Villainy) — When Played: if you control a leader
#   unit, create 2 Spy tokens and give those tokens Sentinel for this phase. (Plot keyword is dormant
#   when played from hand.) P1's leader is deployed (flag set → SWUControlsLeaderUnit true).
# Off-aspect (Vigilance/Heroism leader) so SEC_082 costs 3 + 4 = 7.

## GIVEN
P1LeaderBase: SOR_005:1:1:1/SOR_028
P2LeaderBase: SOR_005:0:0:0/SOR_028
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1Hand: SEC_082

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:1:CARDID:SEC_T01
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:2:HASKEYWORD:Sentinel
P1NODECISION
