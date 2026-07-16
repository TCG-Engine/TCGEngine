# Deployed_CapitalShipCostsTwoLess
#// JTL_005 Admiral Piett (deployed leader unit) — passive: Each Capital Ship unit you play costs 2
#// resources less. P1 deploys Piett (free epic, 5-resource threshold met) then plays JTL_069 Munificent
#// Frigate (cost 5, Vigilance covered by the Vigilance base) for 5 − 2 = 3, leaving 2 of 5 resources.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_069
WithP1Resources: 5

## WHEN
- P1>DeployLeader
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_069
P1RESAVAILABLE:2
P1LEADER:DEPLOYED
P1LEADER:EPICUSED

---

# LeaderAction_NoCapitalShip_Fizzle
#// JTL_005 Admiral Piett (leader) — the action only plays a CAPITAL SHIP unit. With only a non-Capital
#// Ship unit in hand (SOR_225 TIE Fighter), there is no eligible card, so the action fizzles: the leader
#// exhausts, the hand is unchanged, and no card is played. Proves the Capital Ship restriction.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_225
WithP1Resources: 4

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
P1LEADER:EXHAUSTED
P1NODECISION

---

# LeaderAction_PlaysCapitalShip_CostsOneLess
#// JTL_005 Admiral Piett (leader) — Action [Exhaust]: Play a Capital Ship unit from your hand. It costs
#// 1 resource less. P1 plays JTL_069 Munificent Frigate (cost 5, Vigilance — covered by the Vigilance
#// base) for 5 − 1 = 4, leaving 0 resources.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_069
WithP1Resources: 4

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_069
P1RESAVAILABLE:0
P1HANDCOUNT:0
P1LEADER:EXHAUSTED
