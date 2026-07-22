# PerTwoResources_SentinelXWings
#// JTL_130 Timely Reinforcements (event) — Choose an opponent; for every 2 resources they control, create
#// an X-Wing token with Sentinel this phase. P2 controls 6 resources → 3 X-Wings, each with Sentinel.

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_130
WithP1Resources: 5
WithP2Resources: 6

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:3
P1SPACEARENAUNIT:0:CARDID:JTL_T02
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel

---

# OddResources_RoundsDown
#// JTL_130 Timely Reinforcements — "For every 2 resources" rounds DOWN. P2 controls 5 resources →
#// floor(5/2) = 2 X-Wing tokens.

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_130
WithP1Resources: 5
WithP2Resources: 5

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2

---

# SentinelExpiresNextPhase
#// JTL_130 Timely Reinforcements — the Sentinel is granted "for this phase" only. The X-Wings created this
#// phase keep Sentinel now, but after the action phase ends (both players pass → Regroup), the token
#// persists but has LOST Sentinel.

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Hand: JTL_130
WithP1Resources: 5
WithP2Resources: 4

## WHEN
- P1>PlayHand:0
- P1>Pass
- P2>Pass

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:JTL_T02
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel
