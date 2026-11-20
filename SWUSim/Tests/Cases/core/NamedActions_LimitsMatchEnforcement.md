# SHD_017_UsedUp_EntryGreyed
#// Named Action abilities — every id in $actionAbilityLimits (ActionAbilities.php) has a section here: use the Action
#// its `max` times, and its entry must stay LISTED but greyed with the limit's reason. The limit table is
#// descriptive; this file proves it agrees with the engine's real enforcement (action_abilities_inventory_test.php
#// checks the reverse direction — that every limited id has its section). Lando (deployed) uses a global flag.
## GIVEN
CommonSetup: grk/rrk/{myLeader:SHD_017:1:1}
P1OnlyActions: true
WithP1Resources: 1:SHD_111:1
WithP1Deck: [SOR_095 SOR_046 SEC_080]
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1SPACEARENAUNIT:0:CARDID:SHD_111
P1ACTIONENTRIES:myGroundArena-0:DISABLED:SHD_017.u0
P1ACTIONREASON:myGroundArena-0:SHD_017.u0:Used_this_round

---

# TWI_082_UsedUp_EntryGreyed
#// MagnaGuard Wing Leader — once each round, per copy (NumUses on the unit); usable with no Droid to attack with.
## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1SpaceArena: TWI_082:1:0
## WHEN
- P1>UseUnitAbility:mySpaceArena-0
## EXPECT
P1ACTIONENTRIES:mySpaceArena-0:DISABLED:TWI_082.0
P1ACTIONREASON:mySpaceArena-0:TWI_082.0:Used_this_round

---

# JTL_013_UsedUp_EntryGreyed
#// Poe as a pilot — once each round (the leader's NumUses). After one hop he is on the second Vehicle, greyed.
## GIVEN
CommonSetup: grw/grw/{
  myLeader:JTL_013;
  myBase:SOR_022;
  theirLeader:JTL_013;
  theirBase:SOR_022
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 2
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArenaUpgrade: 0:JTL_013
## WHEN
- P1>UseUnitAbility:mySpaceArena-0
## EXPECT
P1SPACEARENAUNIT:1:UPGRADE:0:CARDID:JTL_013
P1ACTIONENTRIES:mySpaceArena-1:DISABLED:JTL_013.u0
P1ACTIONREASON:mySpaceArena-1:JTL_013.u0:Used_this_round

---

# LOF_022_UsedUp_EntryGreyed
#// Mystic Monastery — no more than 3 times each game.
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

# LAW_094_UsedUp_EntryGreyed
#// Hondo Ohnaka — once each round (NumUses on the unit). The deck still has a playable top card afterwards, so the
#// only thing stopping a second use is the limit.
## GIVEN
CommonSetup: ggw/ggw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: LAW_094:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1ACTIONENTRIES:myGroundArena-0:DISABLED:LAW_094.0
P1ACTIONREASON:myGroundArena-0:LAW_094.0:Used_this_round

---

# ASH_230_UsedUp_EntryGreyed
#// Improvised Identity — once each round, per copy (the subcard's NumUses). Declining the attack still spends it.
## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_063 SOR_063 SOR_063]
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SOR_063
- P1>AnswerDecision:NO
## EXPECT
P1ACTIONENTRIES:myGroundArena-0:DISABLED:ASH_230.0
P1ACTIONREASON:myGroundArena-0:ASH_230.0:Used_this_round

---

# HMW_009_UsedUp_EntryGreyed
#// Chewbacca (deployed) — once each round (the leader unit's NumUses). Driven through the real deploy.
## GIVEN
CommonSetup: ggw/ggw/{myLeader:HMW_009;myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP2GroundArena: SOR_063:1:0
## WHEN
- P1>DeployLeader
- P1>UseUnitAbility:myGroundArena-1
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:HMW_009
P1ACTIONENTRIES:myGroundArena-1:DISABLED:HMW_009.u0
P1ACTIONREASON:myGroundArena-1:HMW_009.u0:Used_this_round

---

# HMW_172_UsedUp_EntryGreyed
#// Heavy Ion Cannon — the base's granted Action, once each phase. A card is still in hand afterwards, so the cost is
#// payable and only the limit stops a second use.
## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
P1OnlyActions: true
SkipPreGame: true
WithP1BaseUpgrade: HMW_172
WithP1Hand: [SOR_095 SEC_080]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1HANDCOUNT:1
P1ACTIONENTRIES:myBase-0:DISABLED:HMW_172.0
P1ACTIONREASON:myBase-0:HMW_172.0:Used_this_phase
