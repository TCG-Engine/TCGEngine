# PowerPerPilot
#// JTL_093 Nien Nunb — This unit gets +1/+0 for each other friendly Pilot unit and upgrade. With two other
#// Pilot units (JTL_034, JTL_035) in play, Nien Nunb (base 1) has power 3.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_093:1:0
WithP1GroundArena: JTL_034:1:0
WithP1GroundArena: JTL_035:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3

---

# PilotGrant_HostPerPilot
#// JTL_093 Nien Nunb (pilot) — "Attached unit gets +1/+0 for each OTHER friendly Pilot unit and upgrade."
#// Host SOR_237 (2) + Nien's +1 pilot power = 3, plus +1 for the one OTHER friendly Pilot (JTL_034) = 4.
#// (Nien's own upgrade on the host is excluded from the per-pilot count.)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_093
WithP1GroundArena: JTL_034:1:0

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:POWER:4
