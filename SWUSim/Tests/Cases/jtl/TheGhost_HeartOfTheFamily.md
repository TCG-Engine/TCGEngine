# Aura_SharesSentinel
#// JTL_053 The Ghost — Each other friendly Spectre unit gains this unit's keywords; while The Ghost is
#// upgraded it gains Sentinel. With an upgrade attached, The Ghost has Sentinel and shares it to the
#// friendly Spectre unit SOR_146 (Zeb).

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_053:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120
WithP1GroundArena: SOR_146:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_053
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:CARDID:SOR_146
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# NotUpgraded_NoShare
#// JTL_053 The Ghost — while NOT upgraded it has no Sentinel, so it shares nothing: the friendly Spectre
#// SOR_146 does not gain Sentinel.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_053:1:0
WithP1GroundArena: SOR_146:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# DoesNotShareWithNonSpectre
#// JTL_053 The Ghost shares its keywords only with friendly SPECTRE units. Upgraded (so it has Sentinel),
#// it does NOT grant Sentinel to a friendly NON-Spectre unit (SOR_095 Battlefield Marine, Rebel/Trooper).

## GIVEN
CommonSetup: bbw/bbk/{myLeader:JTL_004;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_053:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# DoesNotShareWithEnemySpectre
#// JTL_053 The Ghost shares only with FRIENDLY Spectres. Upgraded (so it has Sentinel), it does NOT grant
#// Sentinel to an ENEMY Spectre unit (P2's SOR_146 Zeb, Rebel/Spectre).

## GIVEN
CommonSetup: bbw/bbk/{myLeader:JTL_004;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_053:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120
WithP2GroundArena: SOR_146:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:CARDID:SOR_146
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
