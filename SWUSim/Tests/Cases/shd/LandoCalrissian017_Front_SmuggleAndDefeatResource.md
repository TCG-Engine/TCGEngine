# SHD_017 Lando Calrissian (Leader, Cunning/Heroism, cost 4)
#   Front Action [Exhaust]: "Play a card using Smuggle. It costs 2 resources less. Defeat a resource you
#   own and control." P1 controls Lando (undeployed) with a SHD_111 (Collections Starhopper, Smuggle
#   [3 Command]) resource on a Command base (Smuggle cost = 3, then -2 = 1). Using Lando Smuggles SHD_111
#   into the SPACE arena for 1 and exhausts the leader.
# NOTE: the resource-defeat cost + its "before When Played" ordering are verified via a LIVE smoke test
#   (TestSchemaStep) — the defeated resource lands in discard (SOR_251) and the Smuggled card's entry fires
#   after. The in-process regression runner drops the resource-defeat MZCHOOSE answer because it follows the
#   auto-resolved Smuggle-target pick within one action (a known runner divergence), so this test asserts
#   only the runner-drivable core.

## GIVEN
CommonSetup: grk/rrk/{myLeader:SHD_017}
P1OnlyActions: true
WithP1Resources: 1:SHD_111:1,4:SOR_251:1
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myResources-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_111
P1LEADER:EXHAUSTED
