# Front_AttackWithAUnit_ItGainsRestore2_HealsYourBase
#// IC27_004 Mace Windu - Here With Backup (Leader, Ground 4/7, cost 6, [Command,Heroism])
#// FRONT: "Action [1 resource, Exhaust]: Attack with a unit. It gains Restore 2 for this attack."
#// DEPLOYED: "Restore 2. When Deployed: Resource the top card of your deck." · Epic: deploy at 6+ resources.
#//
#// COVERAGE (FRONT): offer=Front_Offer_UnitsThatCanAttackNow · decline=N/A ("Attack with a unit" carries no "may";
#//   the no-attacker case is Front_NoUnitCanAttack_CostPaid_ActionResolvesToNothing) · boundary=the exact heal (base 5 → 3,
#//   not 4 or 1) + Front_TheRestoreIsForThisAttackOnly (duration) · control=N/A (a leader cannot be taken; "your base"
#//   is the leader controller's) · reqboundary=Front_RequestBoundary_TwoAttackers_TurnPassesOnce
#// COVERAGE (DEPLOYED): offer=N/A (nothing is chosen) · decline=N/A (not optional) · boundary=Deployed_Epic_BlockedAtFive
#//   (6 vs 5) + Deployed_EmptyDeck_NothingToResource · control=N/A (a leader unit cannot be taken) · reqboundary=N/A (no
#//   decision) · modes=2P only (no player reference beyond "your", no friendly/enemy wording)
#//
#// The positive: P1's ready Battlefield Marine attacks P2's empty board, so the base target auto-resolves. Restore 2 heals
#// P1's base 5 → 3 when the attack starts; the Marine deals 3. The action paid 1 resource and exhausted the leader.
## GIVEN
CommonSetup: ggw/rrk/{myLeader:IC27_004;myResources:1;myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1BASEDMG:3
P2BASEDMG:3
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Front_TheRestoreIsForThisAttackOnly
#// "for this attack": the granted Restore 2 must be gone once the attack ends — still in the SAME phase, so a phase-long
#// grant (the obvious wrong duration) would still read 2 here. The Marine has no Restore of its own.
## GIVEN
CommonSetup: ggw/rrk/{myLeader:IC27_004;myResources:1;myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1BASEDMG:3
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Restore:0

---

# Front_StacksWithPrintedRestore_HealsFour
#// "It gains Restore 2" on a unit that already has Restore 2 stacks (CR 7.9.b — the numerals are added together): Chandrilan
#// Sponsor (SEC_043, 2/3, printed Restore 2) attacks with Restore 4, so P1's base heals 5 → 1 — not 5 → 3 (the
#// highest-instance reading). P2's empty board auto-resolves the base target; the Sponsor deals 2.
## GIVEN
CommonSetup: ggw/rrk/{myLeader:IC27_004;myResources:1;myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: SEC_043:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1BASEDMG:1
P2BASEDMG:2
P1LEADER:EXHAUSTED

---

# Front_Offer_UnitsThatCanAttackNow
#// The attacker pick is left PENDING. Offered: the ready Battlefield Marine and the ready X-Wing (space). Excluded: the
#// exhausted Imperial Dark Trooper, the ready Loth-Wolf (LOF_044 can never attack), and P2's unit (not yours).
## GIVEN
CommonSetup: ggw/rrk/{myLeader:IC27_004;myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_080:0:0
WithP1GroundArena: LOF_044:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# Front_AttacksAUnit_StillHeals
#// Restore fires "when it attacks" — on a UNIT attack as well as a base attack. The Marine attacks P2's 3/7 Consular
#// Security Force: P1's base heals 5 → 3 and the defender takes 3; P2's base is untouched.
## GIVEN
CommonSetup: ggw/rrk/{myLeader:IC27_004;myResources:1;myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:0

---

# Front_NoUnitCanAttack_CostPaid_ActionResolvesToNothing
#// The only unit is exhausted, so nothing can attack. The cost changes game state, so the action is still usable (CR
#// 6.4.587.c): the resource is spent, the leader exhausts, nothing is offered, and the turn passes exactly once.
#// ⚠ No P1OnlyActions — it would make TURNPLAYER blind.
## GIVEN
CommonSetup: ggw/rrk/{myLeader:IC27_004;myResources:1;myBaseDamage:5}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_095:0:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
P1BASEDMG:5
P1NODECISION
TURNPLAYER:2
NOEXTRAACTION

---

# Front_CannotPay_NoResource_LeaderStaysReady
#// The [1 resource] is a cost: with nothing to pay it, the action is unavailable — the leader stays ready and nothing
#// attacks.
## GIVEN
CommonSetup: ggw/rrk/{myLeader:IC27_004;myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:READY
P2BASEDMG:0
P1GROUNDARENAUNIT:0:READY
P1NODECISION

---

# Front_ACreditPaysTheResource
#// A Credit token pays an ability's resource cost (CR 3.13): 0 ready resources + 1 Credit, and the action resolves.
## GIVEN
CommonSetup: ggw/rrk/{myLeader:IC27_004;myResources:0;myBaseDamage:5}
P1OnlyActions: true
WithP1Credits: 1
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myTempZone-0
## EXPECT
P1CREDITCOUNT:0
P1BASEDMG:3
P2BASEDMG:3
P1LEADER:EXHAUSTED

---

# Front_RequestBoundary_TwoAttackers_TurnPassesOnce
#// Two legal attackers, so the pick is a real prompt; the answer arrives in a later request. The second unit attacks,
#// gains Restore 2 (base 5 → 3), the first stays ready, and the turn passes exactly once — the attack owns the close.
## GIVEN
CommonSetup: ggw/rrk/{myLeader:IC27_004;myResources:1;myBaseDamage:5}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_080:1:0
## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1BASEDMG:3
P2BASEDMG:3
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:EXHAUSTED
TURNPLAYER:2
NOEXTRAACTION

---

# Deployed_WhenDeployed_ResourcesTheTopCard_Exhausted
#// "When Deployed: Resource the top card of your deck." The TOP card (SOR_095) becomes a 7th resource, EXHAUSTED; the next
#// card (SEC_080) is now on top. Deploying is free, so the 6 ready resources stay ready.
## GIVEN
CommonSetup: ggw/rrk/{myLeader:IC27_004;myResources:6}
P1OnlyActions: true
WithP1Deck: [SOR_095 SEC_080]
## WHEN
- P1>DeployLeader
## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:CARDID:IC27_004
P1RESCOUNT:7
P1RESAVAILABLE:6
P1DECKCOUNT:1
P1DECKTOPCARD:SEC_080

---

# Deployed_EmptyDeck_NothingToResource
#// With no deck there is nothing to resource: the deploy still happens and the ability is a clean no-op.
## GIVEN
CommonSetup: ggw/rrk/{myLeader:IC27_004;myResources:6}
P1OnlyActions: true
## WHEN
- P1>DeployLeader
## EXPECT
P1DECKCOUNT:0
P1LEADER:DEPLOYED
P1RESCOUNT:6
P1NODECISION

---

# Deployed_Restore2_HealsOnItsOwnAttack
#// The deployed leader unit has printed Restore 2: deployed (ready), it attacks P2's empty board — P1's base heals 5 → 3
#// and P2's base takes its 4.
## GIVEN
CommonSetup: ggw/rrk/{myLeader:IC27_004;myResources:6;myBaseDamage:5}
P1OnlyActions: true
## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:3
P2BASEDMG:4

---

# Deployed_Epic_BlockedAtFiveResources
#// The boundary of "If you control 6 or more resources": at 5 the leader does not deploy and nothing is resourced.
## GIVEN
CommonSetup: ggw/rrk/{myLeader:IC27_004;myResources:5}
P1OnlyActions: true
WithP1Deck: [SOR_095 SEC_080]
## WHEN
- P1>DeployLeader
## EXPECT
P1LEADER:NOTDEPLOYED
P1RESCOUNT:5
P1DECKCOUNT:2
