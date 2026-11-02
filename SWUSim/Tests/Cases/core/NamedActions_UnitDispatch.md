# ChewbaccaLeader_UseImprovisedIdentity
#// Named Action abilities — the 2026-11-02 report. Deployed Chewbacca (HMW_009) wearing Improvised Identity: the
#// upgrade's Action is reachable BY ID even though Chewbacca has his own. It discards Cantina Braggart (Raid 2) and
#// attacks P2's base for 3 + 2. Chewbacca's own once-each-round use is untouched — his entry is greyed only for lack
#// of a non-base target, NOT "Used this round".
## GIVEN
CommonSetup: ggw/ggw/{myLeader:HMW_009;myLeaderDeployed:1;myResources:5}
P1OnlyActions: true
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_157 SOR_063 SOR_063]
## WHEN
- P1>UseUnitAbility:myGroundArena-0:ASH_230.0
- P1>AnswerDecision:SOR_157
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_009
P2BASEDMG:5
P1ACTIONREASON:myGroundArena-0:ASH_230.0:Used_this_round
P1ACTIONREASON:myGroundArena-0:HMW_009.u0:No_legal_target

---

# ChewbaccaLeader_BareAbility_IsAmbiguousError
#// Two usable Actions and no id: the bare DSL form is an ERROR, never a silent pick.
## GIVEN
CommonSetup: ggw/ggw/{myLeader:HMW_009;myLeaderDeployed:1;myResources:5}
P1OnlyActions: true
WithP1GroundArenaUpgrade: 0:ASH_230
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
EXPECTERROR:ambiguous

---

# TwoImprovisedIdentities_SpendsOneCopyEach
#// Two copies are ONE entry; each use spends the next unspent copy. Declining the attack keeps the host ready, so
#// the second use is reachable; after it, the entry is greyed "Used this round".
## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
## WHEN
- P1>UseUnitAbility:myGroundArena-0:ASH_230.0
- P1>AnswerDecision:SOR_063
- P1>AnswerDecision:NO
- P1>UseUnitAbility:myGroundArena-0:ASH_230.0
- P1>AnswerDecision:SOR_063
- P1>AnswerDecision:NO
## EXPECT
P1DISCARDCOUNT:2
P1ACTIONENTRIES:myGroundArena-0:DISABLED:ASH_230.0
P1ACTIONREASON:myGroundArena-0:ASH_230.0:Used_this_round

---

# StaleId_UnitLeftPlay_Refused
#// The unit is gone (the menu was opened on a board that has since changed): the id is refused, nothing breaks,
#// nothing is spent.
## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:mySpaceArena-0:SOR_184.0
## EXPECT
P1NODECISION
P1RESAVAILABLE:2

---

# DisabledId_Refused_NothingSpent
#// A greyed entry can't be used: exhausted Crosshair's [Exhaust] Action by id does nothing and spends nothing.
## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SHD_087:0:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-0:SHD_087.1
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1RESAVAILABLE:2
P1NODECISION

---

# SatineGrant_OnUnitWithOwnAction
#// Satine's granted Action on a unit that has its own (Crosshair, 6 HP): reachable by id. It discards half the unit's
#// remaining HP, rounded up — 3 — from the opponent's deck.
## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SHD_087:1:0
WithP2GroundArena: TWI_047:1:0
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>UseUnitAbility:myGroundArena-0:TWI_047.0
## EXPECT
P2DISCARDCOUNT:3
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# PoePilot_ReachableOnFirespray
#// Poe as a pilot on a Vehicle that has its own Action (Fett's Firespray): his hop is reachable by id.
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
WithP1Resources: 3
WithP1SpaceArena: SOR_184:1:0
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArenaUpgrade: 0:JTL_013
## WHEN
- P1>UseUnitAbility:mySpaceArena-0:JTL_013.u0
## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:1:UPGRADE:0:CARDID:JTL_013

---

# Log_GrantedAction_NamesTheAbilityAndTheHost
#// The game log names a granted Action AND the unit that used it — not "P1 used Improvised Identity's Action".
## GIVEN
CommonSetup: ggw/ggw/{myLeader:HMW_009;myLeaderDeployed:1;myResources:5}
P1OnlyActions: true
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_157 SOR_063 SOR_063]
## WHEN
- P1>UseUnitAbility:myGroundArena-0:ASH_230.0
- P1>AnswerDecision:SOR_157
- P1>AnswerDecision:NO
## EXPECT
LOGCONTAINS:P1 used [[ASH_230|Improvised Identity]] on [[HMW_009|Chewbacca]]

---

# Log_OwnAction_TwoOnOneCard_NamesTheCost
#// A unit's own Action: "P1 used <host>'s Action", plus the cost when its card prints two (Crosshair).
## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SHD_087:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-0:SHD_087.1
## EXPECT
LOGCONTAINS:P1 used [[SHD_087|Crosshair]]'s Action [Exhaust]
