# PlaySecondCopy_ChoiceBeforeAmbushAndWhenPlayed
#// The uniqueness rule resolves BEFORE the entering copy's triggers (owner 2026-10-20: "whenever we control two of the same
#// unique units, the uniqueness rule must be satisfied before anything else … it definitely comes up when i play Blue Leader
#// since it has both ambush and a when played"). CR 2.x.5: game-state situations "take priority over other waiting triggered
#// abilities"; b = two copies of a unique card. CR 8.30.3: the defeat "occurs immediately and is not a triggered ability. The
#// player still must resolve any abilities that trigger upon either copy being played or defeated."
#// JTL_096 Blue Leader is unique: Ambush + "When Played: You may pay 2 resources. If you do, move this unit to the ground arena
#// and give 2 Experience tokens to it." P1 controls one and plays a second, with an enemy TIE to Ambush. The FIRST thing P1 is
#// asked is which copy to defeat — the engine used to raise that only when the action ended, after Ambush and When Played.
## GIVEN
CommonSetup: ggw/rrk
WithP1Resources: 6
WithP1Hand: JTL_096
WithP1SpaceArena: JTL_096:1:0
WithP2SpaceArena: SOR_225:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1DECISIONTOOLTIP:Uniqueness_rule_choose_a_copy_to_defeat
P1SPACEARENACOUNT:2

---

# PlaySecondCopy_DefeatOldCopy_NewCopyTriggersFollow
#// Same board. P1 defeats the copy already in play (index 0); the new copy's Ambush and When Played come after — the choice
#// is answered and P1 is now on the new copy's triggers, with one Blue Leader left.
## GIVEN
CommonSetup: ggw/rrk
WithP1Resources: 6
WithP1Hand: JTL_096
WithP1SpaceArena: JTL_096:1:0
WithP2SpaceArena: SOR_225:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1SPACEARENACOUNT:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:JTL_096

---

# RescueMidAbility_ChoiceBeforeTheNextClause
#// The same rule when a RESCUE makes the duplicate in the middle of an ability (CR 8.34.4: a guard that leaves play frees its
#// captives; a rescued unit enters play as a new copy). P2's SHD_164 Rhokai Gunship guards a captured P1 HMW_254 Captain
#// Tarpals; P1 controls another Tarpals. P1 plays LAW_208 Collateral Damage — "Deal 2 damage to a unit. Then, deal 2 damage to
#// a base or another unit in the same arena." The first clause kills the Gunship and Tarpals is rescued: P1 controls two, so
#// the choice comes BEFORE the "Then" pick. The engine used to raise it only when the action ended, after the second clause.
#// (An ATTACK that frees the captive does not show it: the attack's action ends before anything else of P1's resolves.)
## GIVEN
CommonSetup: rrk/ggw
WithP1Resources: 5
WithP1Hand: LAW_208
WithP1GroundArena: HMW_254:1:0
WithP2SpaceArena: SHD_164:1:0
WithP2SpaceArenaCaptive: 0:HMW_254
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P1DECISIONTOOLTIP:Uniqueness_rule_choose_a_copy_to_defeat
P1GROUNDARENACOUNT:2

---

# ControlReturnAtRegroup_ChoiceBeforeRegroupStartTriggers
#// HMW_200 Rish Loo: "Take control of an enemy non-leader unit with a Weakness token on it. At the start of the next regroup
#// phase, its owner takes control of it." P1's Rish Loo steals P2's weakened LAW_071 The Max Rebo Band; P2 plays a second Band
#// (legal — the first is P1's now). At the regroup the Band comes home and P2 controls two. The choice comes before the
#// "When the regroup phase starts: Create a Credit token" abilities, so only the surviving Band makes one: ONE Credit. The step
#// used to keep running past the queued choice, and both Bands' triggers resolved first: two Credits.
## GIVEN
CommonSetup: yyk/ggw
WithP1Resources: 4
WithP2Resources: 6
WithP1Hand: HMW_200
WithP2Hand: LAW_071
WithP2GroundArena: LAW_071:1:0
WithP2GroundArenaUpgrade: 0:HMW_T02
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>Pass
- P2>Pass
## EXPECT
P2DECISIONTOOLTIP:Uniqueness_rule_choose_a_copy_to_defeat
P2CREDITCOUNT:0
P2HANDCOUNT:0

---

# ControlReturnAtRegroup_OneCreditAfterTheChoice
#// Same board, answered: P2 defeats the returned Band (index 1). The surviving Band's regroup trigger makes ONE Credit, then
#// the draw.
## GIVEN
CommonSetup: yyk/ggw
WithP1Resources: 4
WithP2Resources: 6
WithP1Hand: HMW_200
WithP2Hand: LAW_071
WithP2GroundArena: LAW_071:1:0
WithP2GroundArenaUpgrade: 0:HMW_T02
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>Pass
- P2>Pass
- P2>ChooseMyGroundUnit:1
## EXPECT
P2GROUNDARENACOUNT:1
P2CREDITCOUNT:1
P2HANDCOUNT:2

---

# PlaySecondCopy_DefeatNewCopy_ItsTriggersStillResolve_ClosesOnce
#// CR 8.30.3: "The player still must resolve any abilities that trigger upon either copy being played". P1 defeats the NEW
#// Blue Leader (index 1): its Ambush and When Played still come up to be resolved (with the unit gone they do nothing), and
#// the action then closes exactly ONCE — the choice is raised inside the play, so the play's close must wait for it and must
#// not also be taken by the answer. No P1OnlyActions, so TURNPLAYER can see a double turn swap.
## GIVEN
CommonSetup: ggw/rrk
WithP1Resources: 6
WithP1Hand: JTL_096
WithP1SpaceArena: JTL_096:1:0
WithP2SpaceArena: SOR_225:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-1
- P1>AnswerDecision:EffectStack-0
## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:READY
P1DISCARDCOUNT:1
P1NODECISION
TURNPLAYER:2
NOEXTRAACTION

---

# PlaySecondCopy_NoTriggers_ClosesOnce
#// A second copy with nothing to trigger (LOF_093 Gungi has no text): the choice is raised inside the play, and ActivateCard's
#// immediate close finds it pending — it must wait behind the answer and then close once. The turn passes to P2.
## GIVEN
CommonSetup: ggw/rrk
WithP1Resources: 5
WithP1Hand: LOF_093
WithP1GroundArena: LOF_093:1:0
## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:1
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:READY
P1DISCARDCOUNT:1
P1NODECISION
TURNPLAYER:2
NOEXTRAACTION

---

# MaulPlaysSecondCopy_DefeatTheOldCopy_BothAreLost
#// HMW_016 Maul: "Play a unit from your hand. It costs 1 resource less. Then, defeat it." Playing a second copy of a unique
#// raises the choice the moment it enters — before Maul's "then, defeat it" — so picking the OLD copy (index 0) loses BOTH:
#// the rule takes the old one, Maul's step the new one (found by its marker). The same shape as JTL_043 No Glory, Only
#// Results (core/UniquenessRule_ImmediateOnControlChange). LOF_093 Gungi has no text, so nothing else can fire.
## GIVEN
CommonSetup: gyk/rrk/{myResources:5;myLeader:HMW_016}
P1OnlyActions: true
WithP1GroundArena: LOF_093:1:0
WithP1Hand: LOF_093
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>ChooseMyGroundUnit:0
## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
P1NODECISION
