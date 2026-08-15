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

---

# ExhaustedResourcesStillCounted
#// JTL_130 Timely Reinforcements — "resources they control" counts EXHAUSTED resources too, not just ready
#// ones. P2 controls 5 resources, all exhausted (status 0) → floor(5/2) = 2 X-Wing tokens, each with
#// Sentinel. (Intended: exhausted resources still count.)

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
WithP2Resources: 5:SOR_095:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:JTL_T02
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel

---

# ZeroResources_NoTokens
#// JTL_130 Timely Reinforcements — edge case: opponent controls 0 resources → floor(0/2) = 0 X-Wing tokens,
#// no crash. (Intended: 0 resources does not crash.)

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
WithP2Resources: 0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0

---

# SentinelOnlyToNewBatch
#// JTL_130 Timely Reinforcements — Sentinel is granted only to the NEWLY-created batch, not to any
#// pre-existing X-Wing tokens already in play. P1 already controls 1 X-Wing token (no keywords); playing
#// Timely Reinforcements with P2 at 6 resources creates 3 more (each with Sentinel). The pre-existing token
#// at index 0 keeps NO Sentinel. (Intended: existing X-Wing tokens are not given Sentinel.)

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_T02:1:0
WithP1Hand: JTL_130
WithP1Resources: 5
WithP2Resources: 6

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:4
P1SPACEARENAUNIT:0:CARDID:JTL_T02
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel
P1SPACEARENAUNIT:1:HASKEYWORD:Sentinel
P1SPACEARENAUNIT:2:HASKEYWORD:Sentinel
P1SPACEARENAUNIT:3:HASKEYWORD:Sentinel

---

# SimulateRequestBoundary_SentinelGrantSurvives
#// JTL_130 Timely Reinforcements — the card raises no target decision in a 2-player game, but production
#// still ends the request at the PLAY action, so the phase-scoped Sentinel grant on the newly-created batch
#// is written in one process and read in a fresh one. Mirrors SentinelOnlyToNewBatch with a boundary
#// inserted immediately after the play: the pre-existing token must still lack Sentinel and each of the 3
#// new X-Wings must still carry it after the gamestate round-trip.

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_T02:1:0
WithP1Hand: JTL_130
WithP1Resources: 5
WithP2Resources: 6

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary

## EXPECT
P1SPACEARENACOUNT:4
P1SPACEARENAUNIT:0:CARDID:JTL_T02
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel
P1SPACEARENAUNIT:1:HASKEYWORD:Sentinel
P1SPACEARENAUNIT:2:HASKEYWORD:Sentinel
P1SPACEARENAUNIT:3:HASKEYWORD:Sentinel
