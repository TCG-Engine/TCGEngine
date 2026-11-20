# MysticMonasteryPlusCarboniteChamber_BothListed
#// Named Action abilities — a base lists its own Action AND each Fortify upgrade's: Mystic Monastery (LOF_022) with
#// Carbonite Chamber (HMW_095) attached.
## GIVEN
CommonSetup: gbk/bbk/{myBase:LOF_022;theirBase:SOR_021}
SkipPreGame: true
WithP1BaseUpgrade: HMW_095
WithP2GroundArena: SOR_095:1:0
## WHEN
## EXPECT
P1ACTIONENTRIES:myBase-0:EXACT:LOF_022.0&HMW_095.0
P1ACTIONENTRIES:myBase-0:ENABLED:LOF_022.0&HMW_095.0

---

# MysticMonastery_ThreeUsesSpent_Greyed
#// "Use this ability no more than 3 times each game": after the third use it stays listed, greyed "Used this game".
## GIVEN
CommonSetup: gbk/bbk/{myBase:LOF_022;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseBaseAbility
- P1>UseBaseAbility
- P1>UseBaseAbility
## EXPECT
P1ACTIONENTRIES:myBase-0:DISABLED:LOF_022.0
P1ACTIONREASON:myBase-0:LOF_022.0:Used_this_game

---

# EpicBase_EpicSpent_Greyed
#// A spent Epic Action is greyed "Used this game", not hidden.
## GIVEN
CommonSetup: gbk/bbk/{myBase:SOR_019;theirBase:SOR_021;myBaseEpicUsed:1}
SkipPreGame: true
WithP1GroundArena: SOR_095:1:0
## WHEN
## EXPECT
P1ACTIONENTRIES:myBase-0:EXACT:SOR_019.0
P1ACTIONENTRIES:myBase-0:DISABLED:SOR_019.0
P1ACTIONREASON:myBase-0:SOR_019.0:Used_this_game

---

# HeavyIonCannon_EmptyHand_Greyed
#// HMW_172 grants the base "Action [discard a card from your hand]": with an empty hand the cost can't be paid.
## GIVEN
CommonSetup: gbk/bbk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithP1BaseUpgrade: HMW_172
WithP2GroundArena: SOR_095:1:0
## WHEN
## EXPECT
P1ACTIONENTRIES:myBase-0:EXACT:HMW_172.0
P1ACTIONENTRIES:myBase-0:DISABLED:HMW_172.0
P1ACTIONREASON:myBase-0:HMW_172.0:Can't_be_used_now

---

# TrenchLeader_OneAbilityEntry
#// Admiral Trench prints two front Actions; the second is his deploy, which stays the "Deploy Leader" button and is
#// NOT a leader-ability entry.
## GIVEN
CommonSetup: ggw/ggw/{myLeader:JTL_014;myResources:3}
SkipPreGame: true
## WHEN
## EXPECT
P1ACTIONENTRIES:myLeader-0:EXACT:JTL_014.0

---

# ExhaustedLeader_Greyed
## GIVEN
CommonSetup: ggw/ggw/{myLeader:JTL_014:0;myResources:3}
SkipPreGame: true
## WHEN
## EXPECT
P1ACTIONENTRIES:myLeader-0:DISABLED:JTL_014.0
P1ACTIONREASON:myLeader-0:JTL_014.0:Exhausted

---

# DeployedLeader_NoFrontEntry
#// Once deployed, the leader's Actions are the UNIT's (`.u0` on the unit surface); the leader slot lists none.
## GIVEN
CommonSetup: ggw/ggw/{myLeader:HMW_009;myLeaderDeployed:1;myResources:5}
SkipPreGame: true
## WHEN
## EXPECT
P1ACTIONENTRIES:myLeader-0:EXACT:-

---

# KeywordPrefixedLeaderAction_IsAnEntry
#// "Coordinate - Action [1 resource, Exhaust]: …" (TWI_008 Padmé Amidala) is an Action too — the keyword prefix must not
#// hide it (the clause parser once required lines to START with "Action", and refused her ability outright).
## GIVEN
CommonSetup: ggw/rrk/{myResources:1;myLeader:TWI_008}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_095:1:0 SOR_095:1:0]
## WHEN
## EXPECT
P1ACTIONENTRIES:myLeader-0:EXACT:TWI_008.0

---

# LeaderAction_LabelFallsBackToLeaderAbility
#// A leader-slot Action is labelled "Leader Ability" (owner, 2026-11-03), not the card title; a hand-written name in
#// $actionAbilityLabelOverrides replaces it (action_ability_labels_test.php).
## GIVEN
CommonSetup: ggw/ggw/{myLeader:JTL_014;myResources:3}
SkipPreGame: true
## WHEN
## EXPECT
P1ACTIONLABEL:myLeader-0:JTL_014.0:Leader_Ability

---

# FlippedFlipatine_LabelIsLeaderAbility
#// TWI_017's flipped face is still a LEADER (not a unit), so its `.u0` Action is a leader-slot entry and takes the
#// same fallback.
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017;myLeaderDeployed:1;myResources:3}
SkipPreGame: true
## WHEN
## EXPECT
P1ACTIONENTRIES:myLeader-0:EXACT:TWI_017.u0
P1ACTIONLABEL:myLeader-0:TWI_017.u0:Leader_Ability
