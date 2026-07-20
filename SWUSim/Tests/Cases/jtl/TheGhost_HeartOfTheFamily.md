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

---

# SharesSaboteur
#// JTL_053 The Ghost — "Each other friendly Spectre unit gains this unit's keywords." SOR_166
#// Infiltrator's Skill (upgrade: "Attached unit gains Saboteur") is attached to The Ghost, giving it
#// Saboteur (the upgrade also makes it "upgraded" → Sentinel). The friendly Spectre SOR_146 Zeb gains
#// Saboteur via the share; the friendly NON-Spectre SOR_095 Battlefield Marine does not.

## GIVEN
CommonSetup: bbw/bbk/{myLeader:JTL_004;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_053:1:0
WithP1SpaceArenaUpgrade: 0:SOR_166
WithP1GroundArena: SOR_146:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Saboteur
P1GROUNDARENAUNIT:0:CARDID:SOR_146
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:NOTKEYWORD:Saboteur

---

# SharesBounty
#// JTL_053 The Ghost shares a Bounty granted by an upgrade. SHD_071 Top Target ("Attached unit gains:
#// 'Bounty — Heal 4 …'") is attached to The Ghost; the friendly Spectre SOR_146 Zeb gains the Bounty
#// keyword via the share, while the non-Spectre SOR_095 Battlefield Marine does not.

## GIVEN
CommonSetup: bbw/bbk/{myLeader:JTL_004;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_053:1:0
WithP1SpaceArenaUpgrade: 0:SHD_071
WithP1GroundArena: SOR_146:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Bounty
P1GROUNDARENAUNIT:0:CARDID:SOR_146
P1GROUNDARENAUNIT:0:HASKEYWORD:Bounty
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:NOTKEYWORD:Bounty

---

# SharesGrit
#// JTL_053 The Ghost shares Grit granted by an attached JTL_050 Phantom II ("Attached unit gets +3/+3
#// and gains Grit"). The friendly Spectre SOR_146 Zeb gains Grit via the share; the non-Spectre SOR_095
#// Battlefield Marine does not.

## GIVEN
CommonSetup: bbw/bbk/{myLeader:JTL_004;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_053:1:0
WithP1SpaceArenaUpgrade: 0:JTL_050
WithP1GroundArena: SOR_146:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:CARDID:SOR_146
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:NOTKEYWORD:Grit

---

# SharesRaid
#// JTL_053 The Ghost shares the Raid keyword (a value keyword — the shared value stacks additively).
#// TWI_169 Clone Cohort ("Attached unit gains Raid 2") is attached to The Ghost, giving it Raid 2. The
#// friendly Spectre SOR_146 Zeb (no innate Raid) gains Raid via the share; the non-Spectre SOR_095
#// Battlefield Marine does not.

## GIVEN
CommonSetup: bbw/bbk/{myLeader:JTL_004;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_053:1:0
WithP1SpaceArenaUpgrade: 0:TWI_169
WithP1GroundArena: SOR_146:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Raid
P1GROUNDARENAUNIT:0:CARDID:SOR_146
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:NOTKEYWORD:Raid

---

# SharesAmbush
#// JTL_053 The Ghost shares Ambush gained from a FIELD effect. SOR_100 Wedge Antilles ("Each friendly
#// VEHICLE unit … gains Ambush") grants Ambush to The Ghost (a Vehicle). SOR_146 Zeb is a Spectre but
#// NOT a Vehicle, so it can only gain Ambush via The Ghost's share — proving the share (not Wedge's own
#// grant) is responsible. The non-Vehicle non-Spectre SOR_095 Battlefield Marine gains nothing.

## GIVEN
CommonSetup: bbw/bbk/{myLeader:JTL_004;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_053:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120
WithP1GroundArena: SOR_100:1:0
WithP1GroundArena: SOR_146:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Ambush
P1GROUNDARENAUNIT:1:CARDID:SOR_146
P1GROUNDARENAUNIT:1:HASKEYWORD:Ambush
P1GROUNDARENAUNIT:2:CARDID:SOR_095
P1GROUNDARENAUNIT:2:NOTKEYWORD:Ambush

---

# SharesCoordinateAndRestore
#// JTL_053 The Ghost shares BOTH keywords granted by TWI_051 For the Republic ("Attached unit gains:
#// 'Coordinate - Restore 2.'"). With The Ghost + Zeb + Marine = 3 friendly units, Coordinate is active,
#// so The Ghost has Coordinate and Restore. The friendly Spectre SOR_146 Zeb gains both via the share;
#// the non-Spectre SOR_095 Battlefield Marine gains neither.

## GIVEN
CommonSetup: bbw/bbk/{myLeader:JTL_004;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_053:1:0
WithP1SpaceArenaUpgrade: 0:TWI_051
WithP1GroundArena: SOR_146:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Coordinate
P1GROUNDARENAUNIT:0:CARDID:SOR_146
P1GROUNDARENAUNIT:0:HASKEYWORD:Coordinate
P1GROUNDARENAUNIT:0:HASKEYWORD:Restore
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:NOTKEYWORD:Coordinate
P1GROUNDARENAUNIT:1:NOTKEYWORD:Restore
