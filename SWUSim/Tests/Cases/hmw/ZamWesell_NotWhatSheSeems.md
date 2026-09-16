# DeathStarDeployed_ZamIsAnImperialVehicleCapitalShip
#// COVERAGE: offer=PilotFromHand_HostPool_IncludesZam + GaderffiiStick_ZamIsNotANonVehicleHost
#//           decline=N/A (constant ability) · boundary=N/A · quantity=TwinSuns_GainsBothLeadersTraits
#//           negative=TarkinUndeployed_NotAVehicle, EnemyLeader_NotGained, ForceIsNeverGained,
#//           BlankedZam_GainsNothing, FirstLegionNamesVehicle_StripsTheGainedTrait
#//           control=StolenZam_UsesItsControllersLeaders · reqboundary=N/A (STRUCTURAL: live read)
#//           duration/transition=TarkinDeploysMidGame_ZamBecomesAVehicle + PilotStaysAttached_WhenTheDeathStarLeaves
#//           out-of-play=OutOfPlay_DeckSearchForAVehicle_FindsZam + OutOfPlay_TarkinUndeployed_ZamIsNotFound
#//           awkward=PilotFromHand_AttachesToZam, PilotLeaderDeploysOntoZam_TwinSuns, Wedge_BuffsZamAsAVehicle
#//           modes=2P,TwinSuns,TeamSuns ("friendly leader") — TwinSuns_* (two leaders on one seat) and
#//           TeamSuns_TeammatesLeaderCounts_OpponentsDoesNot
#//
#// HMW_134 Zam Wesell — Unit (Ground) 2/4, cost 2, [Command], Underworld/Bounty Hunter.
#// "Hidden. This unit gains each friendly leader's traits except Force, even while she's not in play."
#// HMW_004 Tarkin deployed = The Death Star (Imperial, Vehicle, Capital Ship).

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true;myResources:9}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_134:1:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Hidden
P1GROUNDARENAUNIT:0:HASTRAIT:Underworld
P1GROUNDARENAUNIT:0:HASTRAIT:Bounty Hunter
P1GROUNDARENAUNIT:0:HASTRAIT:Imperial
P1GROUNDARENAUNIT:0:HASTRAIT:Vehicle
P1GROUNDARENAUNIT:0:HASTRAIT:Capital Ship
P1GROUNDARENAUNIT:0:NOTTRAIT:Official

---

# TarkinUndeployed_NotAVehicle

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004:0;myResources:9}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_134:1:0

## EXPECT
P1GROUNDARENAUNIT:0:HASTRAIT:Imperial
P1GROUNDARENAUNIT:0:HASTRAIT:Official
P1GROUNDARENAUNIT:0:NOTTRAIT:Vehicle
P1GROUNDARENAUNIT:0:NOTTRAIT:Capital Ship

---

# ForceIsNeverGained

## GIVEN
CommonSetup: grk/grw/{myLeader:SOR_010}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_134:1:0

## EXPECT
P1GROUNDARENAUNIT:0:HASTRAIT:Sith
P1GROUNDARENAUNIT:0:HASTRAIT:Imperial
P1GROUNDARENAUNIT:0:NOTTRAIT:Force

---

# EnemyLeader_NotGained

## GIVEN
CommonSetup: grw/grk/{myLeader:HMW_004:0;theirLeader:SOR_010}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_134:1:0
WithP2GroundArena: HMW_134:1:0

## EXPECT
P1GROUNDARENAUNIT:0:HASTRAIT:Official
P1GROUNDARENAUNIT:0:NOTTRAIT:Sith
P2GROUNDARENAUNIT:0:HASTRAIT:Sith
P2GROUNDARENAUNIT:0:NOTTRAIT:Official

---

# StolenZam_UsesItsControllersLeaders

## GIVEN
CommonSetup: grw/grk/{myLeader:HMW_004;myLeaderDeployed:true;theirLeader:SOR_010}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArenaControlled: HMW_134:2

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_134
P1GROUNDARENAUNIT:0:HASTRAIT:Vehicle
P1GROUNDARENAUNIT:0:NOTTRAIT:Sith

---

# BlankedZam_GainsNothing

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_134:1:0
WithP1GroundArenaUpgrade: 0:SHD_072

## EXPECT
P1GROUNDARENAUNIT:0:HASTRAIT:Underworld
P1GROUNDARENAUNIT:0:NOTTRAIT:Vehicle
P1GROUNDARENAUNIT:0:NOTTRAIT:Imperial

---

# FirstLegionNamesVehicle_StripsTheGainedTrait
#// HMW_108 (P2 named Vehicle this phase): enemy cards lose it — a gained trait included.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP2GlobalEffect: SWU_HMW108|VEHICLE
WithP1GroundArena: HMW_134:1:0

## EXPECT
P1GROUNDARENAUNIT:0:NOTTRAIT:Vehicle
P1GROUNDARENAUNIT:0:HASTRAIT:Capital Ship

---

# TarkinDeploysMidGame_ZamBecomesAVehicle

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004:0;myResources:9}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_134:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:HASTRAIT:Vehicle
P1GROUNDARENAUNIT:0:NOTTRAIT:Official

---

# PilotFromHand_HostPool_IncludesZam
#// JTL_066 Trace Martez (Piloting [1 Vigilance]); with 1 resource only the Pilot play is affordable, so the
#// host pick comes straight up. Friendly Vehicles without a Pilot: The Death Star AND Zam.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1Hand: JTL_066
WithP1GroundArena: HMW_134:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# PilotFromHand_AttachesToZam

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1Hand: JTL_066
WithP1GroundArena: HMW_134:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:6

---

# PilotFromHand_TarkinUndeployed_PlaysAsAUnit
#// No friendly Vehicle (Zam is Imperial/Official only) → Trace Martez can only be played as a unit.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004:0}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: JTL_066
WithP1GroundArena: HMW_134:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# PilotStaysAttached_WhenTheDeathStarLeaves
#// CR 3.4.a: eligibility is checked only on attach. The Death Star (11 damage) attacks a TIE and is defeated;
#// Zam is no longer a Vehicle, but the Pilot on her stays.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004:1:1:0:11}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_134:1:0
WithP1GroundArenaUpgrade: 0:JTL_066
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:NOTTRAIT:Vehicle
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# TwinSuns_GainsBothLeadersTraits

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004:0;myLeader2:JTL_018}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_134:1:0

## EXPECT
P1GROUNDARENAUNIT:0:HASTRAIT:Official
P1GROUNDARENAUNIT:0:HASTRAIT:Resistance
P1GROUNDARENAUNIT:0:HASTRAIT:Pilot

---

# PilotLeaderDeploysOntoZam_TwinSuns
#// Leader 1 The Death Star (deployed) makes Zam a Vehicle; leader 2 JTL_018 Kazuda deploys as a Pilot onto
#// her (+3/+3). He stays a friendly leader, so she keeps his traits.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true;myLeader2:JTL_018}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 9
WithP1GroundArena: HMW_134:1:0

## WHEN
- P1>DeployLeader:1
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HASTRAIT:Pilot
P1GROUNDARENAUNIT:0:HASTRAIT:Vehicle

---

# TeamSuns_TeammatesLeaderCounts_OpponentsDoesNot

## GIVEN
CommonSetup: grw/grk/{myLeader:HMW_004:0;theirLeader:SOR_010}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Leader: JTL_018:1:0
WithP1GroundArena: HMW_134:1:0

## EXPECT
P1GROUNDARENAUNIT:0:HASTRAIT:Official
P1GROUNDARENAUNIT:0:HASTRAIT:Resistance
P1GROUNDARENAUNIT:0:NOTTRAIT:Sith

---

# GaderffiiStick_ZamIsNotANonVehicleHost
#// HMW_235 "Attach to a non-Vehicle unit with 3 or less power" — Zam (a Vehicle via The Death Star) is excluded.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: HMW_235
WithP1GroundArena: HMW_134:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# Wedge_BuffsZamAsAVehicle
#// SOR_100 Wedge Antilles: "Each friendly VEHICLE unit gets +1/+1 and gains Ambush."

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_134:1:0
WithP1GroundArena: SOR_100:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush

---

# OutOfPlay_DeckSearchForAVehicle_FindsZam
#// JTL_128 Prepare for Takeoff searches for Vehicle UNITS; Zam in the deck is one while The Death Star is out.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_128
WithP1Resources: 2
WithP1Deck: HMW_134
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:HMW_134

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:1

---

# OutOfPlay_TarkinUndeployed_ZamIsNotFound

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004:0}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_128
WithP1Resources: 2
WithP1Deck: HMW_134
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:HMW_134

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:2

---

# OutOfPlay_OwnerAwareSearch_RebelLeader_FindsZam
#// SOR_096 Mon Mothma searches for a Rebel card through _SWUCardHasTrait (the owner-aware out-of-play check).
#// P1's leader SOR_009 Leia is a Rebel, so Zam in the deck is one.
#// STRUCTURAL: on a board like this the owner-aware hook and the bare-HasTrait fallback agree (the searcher IS
#// the owner), so removing either one alone stays green here; the owner path exists so a caller that tests an
#// opponent-owned card resolves that card's owner rather than the acting seat.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_096
WithP1Deck: HMW_134
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:HMW_134

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:1

---

# OutOfPlay_OwnerAwareSearch_NoRebelLeader_ZamNotFound

## GIVEN
CommonSetup: grw/ggw/{myLeader:HMW_004:0;myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_096
WithP1Deck: HMW_134
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:HMW_134

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:2

---

# FirstLegionNamesOfficial_UndeployedLeaderTraitNotGained
#// HMW_108 (P2 named Official) with Tarkin UNDEPLOYED: the leader's printed Official is read straight off the
#// card, so only the suppression-before-grant order keeps Zam from gaining it. (The Death Star section above
#// can't tell the order apart: its Vehicle is already stripped from the leader unit itself.)

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_004:0}
SkipPreGame: true
P1OnlyActions: true
WithP2GlobalEffect: SWU_HMW108|OFFICIAL
WithP1GroundArena: HMW_134:1:0

## EXPECT
P1GROUNDARENAUNIT:0:NOTTRAIT:Official
P1GROUNDARENAUNIT:0:HASTRAIT:Imperial

---

# GalenNamesZam_OutOfPlayCopyGainsNothing
#// SEC_046 Galen Erso (P2) names Zam Wesell: each non-leader card with that name an opponent owns, including
#// out of play, loses all abilities — Zam in P1's deck is no longer a Vehicle for Prepare for Takeoff.

## GIVEN
CommonSetup: grw/bbw/{myLeader:HMW_004;myLeaderDeployed:true}
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 4
WithP2Hand: SEC_046
WithP1Resources: 2
WithP1Hand: JTL_128
WithP1Deck: HMW_134
WithP1Deck: SOR_095

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Zam Wesell
- P1>PlayHand:0
- P1>AnswerDecision:HMW_134

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:2
