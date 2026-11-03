# ChewbaccaLeaderPlusImprovisedIdentity_BothListed
#// Named Action abilities — a unit lists EVERY Action it has, not one. Deployed Chewbacca (HMW_009, his Action is on
#// the deployed side → `.u0`) wearing Improvised Identity: both entries, both usable (an enemy unit gives his attack a
#// legal non-base target). Before named abilities only Chewbacca's own was reachable (reported 2026-11-02).
## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
WithP1GroundArena: HMW_009:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP2GroundArena: SOR_095:1:0
## WHEN
## EXPECT
P1ACTIONENTRIES:myGroundArena-0:EXACT:HMW_009.u0&ASH_230.0
P1ACTIONENTRIES:myGroundArena-0:ENABLED:HMW_009.u0&ASH_230.0

---

# CrosshairTwoOwnActions
#// SHD_087 prints two Actions: `.0` [2 resources] and `.1` [Exhaust]. Ready, 2 resources: both listed and usable.
## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
WithP1GroundArena: SHD_087:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
## EXPECT
P1ACTIONENTRIES:myGroundArena-0:EXACT:SHD_087.0&SHD_087.1
P1ACTIONENTRIES:myGroundArena-0:ENABLED:SHD_087.0&SHD_087.1

---

# CrosshairExhausted_ExhaustEntryGreyed
#// Exhausted Crosshair: the [Exhaust] Action is greyed "Exhausted"; the [2 resources] one is still usable. Greyed
#// entries stay listed (owner decision) — EXACT still names both.
## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
WithP1GroundArena: SHD_087:0:0
WithP2GroundArena: SOR_095:1:0
## WHEN
## EXPECT
P1ACTIONENTRIES:myGroundArena-0:EXACT:SHD_087.0&SHD_087.1
P1ACTIONENTRIES:myGroundArena-0:ENABLED:SHD_087.0
P1ACTIONENTRIES:myGroundArena-0:DISABLED:SHD_087.1
P1ACTIONREASON:myGroundArena-0:SHD_087.1:Exhausted

---

# TwoHeroicResolves_OneEntry
#// Two copies of one granting upgrade fold into ONE entry (owner decision).
## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SHD_155
WithP1GroundArenaUpgrade: 0:SHD_155
## WHEN
## EXPECT
P1ACTIONENTRIES:myGroundArena-0:EXACT:SHD_155.0

---

# HeroicResolve_NoResources_Greyed
#// Heroic Resolve costs 2 resources; with 1 it is listed but greyed "Not enough resources".
## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SHD_155
## WHEN
## EXPECT
P1ACTIONENTRIES:myGroundArena-0:DISABLED:SHD_155.0
P1ACTIONREASON:myGroundArena-0:SHD_155.0:Not_enough_resources

---

# SatinePlusOwnAction_UnitExhausted
#// Satine (TWI_047) on the OPPONENT's side still grants every unit her Action. On an exhausted Fett's Firespray: her
#// [Exhaust] grant is greyed "Exhausted", while the Firespray's own [2 resources] Action is still usable.
## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
WithP1SpaceArena: SOR_184:0:0
WithP2GroundArena: TWI_047:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
## EXPECT
P1ACTIONENTRIES:mySpaceArena-0:EXACT:SOR_184.0&TWI_047.0
P1ACTIONENTRIES:mySpaceArena-0:ENABLED:SOR_184.0
P1ACTIONENTRIES:mySpaceArena-0:DISABLED:TWI_047.0
P1ACTIONREASON:mySpaceArena-0:TWI_047.0:Exhausted

---

# PoePilotOnFirespray_BothListed
#// Poe (JTL_013) attached as a pilot: his Action is on his deployed side (`.u0`). The Vehicle's own Action and Poe's
#// are both listed.
## GIVEN
CommonSetup: grw/grw/{
  myLeader:JTL_013;
  myBase:SOR_022;
  theirLeader:JTL_013;
  theirBase:SOR_022
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 3
WithP1SpaceArena: SOR_184:1:0
WithP1SpaceArenaUpgrade: 0:JTL_013
## WHEN
## EXPECT
P1ACTIONENTRIES:mySpaceArena-0:EXACT:SOR_184.0&JTL_013.u0

---

# LeiaInSpace_SelfGrantListed
#// LOF_098 Leia gains her Action only while she is in the space arena.
## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
WithP1SpaceArena: LOF_098:1:0
## WHEN
## EXPECT
P1ACTIONENTRIES:mySpaceArena-0:EXACT:LOF_098.0

---

# LeiaOnGround_NoEntry
#// On the ground she doesn't have it at all — absent, not greyed.
## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
WithP1GroundArena: LOF_098:1:0
## WHEN
## EXPECT
P1ACTIONENTRIES:myGroundArena-0:EXACT:-

---

# EnemyGunship_ClickerSeesAnyPlayerOnly
#// An opponent's Mercenary Gunship ("Any player may use this ability"), seen by P1: its any-player Action is listed.
## GIVEN
CommonSetup: ggw/ggw/{myResources:4}
WithP2SpaceArena: SHD_256:1:0
## WHEN
## EXPECT
P1ACTIONENTRIES:theirSpaceArena-0:EXACT:SHD_256.0

---

# EnemyUnitOwnAction_NotListedForTheClicker
#// Control for the section above: an opponent's unit whose Action is NOT any-player (Crosshair) lists nothing for P1.
## GIVEN
CommonSetup: ggw/ggw/{myResources:4}
WithP2GroundArena: SHD_087:1:0
## WHEN
## EXPECT
P1ACTIONENTRIES:theirGroundArena-0:EXACT:-

---

# TwinSuns_FarSeatHunterForHire
#// Above two seats an enemy unit is `p{n}…`. P3's Hunter For Hire, seen by P1, lists its any-player Action.
## GIVEN
CommonSetup3P: yyk/bbw/bbw/{myResources:2}
SkipPreGame: true
WithActivePlayer: 1
WithP3GroundArena: LAW_156:1:0
## WHEN
## EXPECT
SEATCOUNT:3
P1ACTIONENTRIES:p3GroundArena-0:EXACT:LAW_156.0

---

# PlainUnit_NoEntries
## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
WithP1GroundArena: SOR_095:1:0
## WHEN
## EXPECT
P1ACTIONENTRIES:myGroundArena-0:EXACT:-

---

# PilotLeaderVehicle_KeepsItsOwnAction
#// A Pilot leader (JTL_001) makes Mercenary Gunship a LEADER UNIT (CR 3.4.7) — but the Gunship's own Action is still
#// printed on its front. Reading the "deployed side" of every leader unit dropped it.
## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myLeaderDeployedPilot:1;
  myBase:SOR_021;
  theirBase:SOR_021;
  theirResources:5
}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: SHD_256:1:0
## WHEN
## EXPECT
P2ACTIONENTRIES:theirSpaceArena-0:EXACT:SHD_256.0

---

# UnitActionLabels_KeepTheCardTitle
#// The "Leader Ability" fallback is for the leader slot only: a unit's Actions keep the card title, plus the cost when
#// the card prints two.
## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
WithP1GroundArena: SHD_087:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
## EXPECT
P1ACTIONLABEL:myGroundArena-0:SHD_087.1:Crosshair_[Exhaust]
