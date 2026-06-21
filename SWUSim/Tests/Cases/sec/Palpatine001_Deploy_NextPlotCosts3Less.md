# SEC_001 Chancellor Palpatine (deployed) — When Deployed: The next card you play using Plot this
# phase costs 3 resources less. P1 controls SEC_034 Cad Bane (Plot, cost 5, Vigilance/Villainy) as
# myResources-0 + 6 vanilla (7 ready — meets SEC_001's deploy threshold of 7). SEC_001's V/V leader
# aspects cover SEC_034's V/V pips → no penalty. Deploy arms the −3, then the Plot window plays
# SEC_034 for 5 − 3 = 2 (7 ready → 5). No enemy units → Cad Bane's When Played fizzles cleanly.

## GIVEN
P1LeaderBase: SEC_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SEC_034:1,6:SOR_095:1
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_034
P1RESCOUNT:7
P1RESAVAILABLE:5
P1DECKCOUNT:1
P1NODECISION
