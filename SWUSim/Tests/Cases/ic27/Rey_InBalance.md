#// IC27_002 Rey — In Balance (Leader, Vigilance/Heroism, Force/Resistance; deployed 3/6 Ground). PREVIEW card — no official
#// rulings exist; reasoned from the CR and the released analogue SHD_004 Rey (More Than a Scavenger), whose two sides have the
#// same shape with a different target condition.
#//   FRONT:    Action [1 resource, Exhaust]: Give an Experience token to a damaged unit.
#//             Epic Action: If you control 5 or more resources, deploy this leader.
#//   DEPLOYED: On Attack: You may give an Experience token to a damaged unit.
#// "A damaged unit" names no side and no "another": any unit in play with damage on it, on either side of the table, in either
#// arena — Rey herself included on the deployed side.
#// FRONT with no damaged unit: still usable. CR 1.? (592.d / 637.3): "As long as an action ability has a cost that changes the
#// game state, a player can pay that cost and resolve the ability even if the ability's effect would not change the game state."
#// Paying 1 resource + exhausting Rey changes it, so the Action resolves with no effect (same as SHD_004).
#//
#// COVERAGE: FRONT clause give-Experience — pos=Front_GivesExperienceToTheChosenDamagedUnit · offer=Front_OfferPool_DamagedOnly_AnySide_AnyArena
#//   · neg(no damaged unit)=Front_NoDamagedUnit_CostPaid_NoEffect · cannot-pay=Front_NoReadyResource_ActionUnavailable
#//   · reqboundary=Front_RequestBoundary_BeforeTheChoice · modes=Twin Suns (Front_TwinSuns_FarSeatDamagedUnit) — "a damaged unit"
#//   is unqualified, so every seat's units are legal; Team Suns N/A (no "friendly"/"enemy" in the text)
#//   · decline=N/A — the effect is mandatory once the Action is paid (no "may")
#// DEPLOYED clause may-give-Experience — pos=Deployed_OnAttack_Take_GivesExperience · decline=Deployed_OnAttack_Decline_NoExperience
#//   · neg(no damaged unit)=Deployed_OnAttack_NoDamagedUnit_NoOffer · offer(self + undamaged defender)=Deployed_OnAttack_OfferPool_IncludesDamagedRey_ExcludesUndamagedDefender
#//   · reqboundary=Deployed_OnAttack_RequestBoundary_BeforeTheChoice · modes=Twin Suns (Deployed_OnAttack_TwinSuns_FarSeatDamagedUnit)
#// EPIC deploy threshold — boundary pair=Epic_FourResources_CannotDeploy / Epic_FiveResources_Deploys

# Front_GivesExperienceToTheChosenDamagedUnit
#// Two damaged units (one each side, so the choice is real and does not auto-resolve). P1 pays 1 resource, exhausts Rey and
#// gives the Experience to P2's damaged marine; P1's own damaged marine gets nothing.

## GIVEN
CommonSetup: bbw/bbk/{myResources:3;myLeader:IC27_002}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:1
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:2
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_T01
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Front_OfferPool_DamagedOnly_AnySide_AnyArena
#// The offer, left pending: P1's damaged ground marine and P2's damaged space unit are offered; P1's and P2's UNDAMAGED ground
#// marines are not. Proves both sides, both arenas, and that "damaged" is load-bearing.

## GIVEN
CommonSetup: bbw/bbk/{myResources:3;myLeader:IC27_002}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:1 SOR_095:1:0]
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1DECISIONTOOLTIP:Give_an_Experience_token_to_a_damaged_unit
P1SELECTABLEEXACT:myGroundArena-0&theirSpaceArena-0

---

# Front_NoDamagedUnit_CostPaid_NoEffect
#// No unit is damaged. CR 637.3: the cost changes the game state, so the Action is still usable — Rey exhausts and the
#// resource is spent — but no Experience is given and no decision is raised.

## GIVEN
CommonSetup: bbw/bbk/{myResources:3;myLeader:IC27_002}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:2
P1NODECISION
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Front_NoReadyResource_ActionUnavailable
#// The [1 resource] is a cost REQUIREMENT, checked before Rey exhausts: with no ready resource the Action is unavailable —
#// Rey stays ready and the damaged marine gets nothing.

## GIVEN
CommonSetup: bbw/bbk/{myLeader:IC27_002}
P1OnlyActions: true
WithP1Resources: 1:SOR_046:0
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1NODECISION
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Front_RequestBoundary_BeforeTheChoice
#// Front_GivesExperienceToTheChosenDamagedUnit with a request boundary between the offer and the answer.

## GIVEN
CommonSetup: bbw/bbk/{myResources:3;myLeader:IC27_002}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:1
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:2
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Front_TwinSuns_FarSeatDamagedUnit
#// Three seats: seat 2 and seat 3 each have a damaged marine. "A damaged unit" is unqualified, so the FAR seat's unit is legal
#// — this cannot pass at two seats. P1 gives the Experience to seat 3's marine.

## GIVEN
CommonSetup3P: bbw/bbk/bbk/{myLeader:IC27_002; myResources:3}
SkipPreGame: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP2GroundArena: SOR_095:1:1
WithP3GroundArena: SOR_095:1:2

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P3GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Deployed_OnAttack_Take_GivesExperience
#// Rey deploys (5 resources) and attacks P2's base. The On Attack is one optional pick (choose a unit, or decline); the
#// Experience goes to P2's damaged marine (two damaged units offered, so the choice is real).

## GIVEN
CommonSetup: bbw/bbk/{myResources:5;myLeader:IC27_002}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:1
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2BASEDMG:3

---

# Deployed_OnAttack_Decline_NoExperience
#// "You may": declining the pick ('-') gives nothing to anyone, and the attack still resolves.

## GIVEN
CommonSetup: bbw/bbk/{myResources:5;myLeader:IC27_002}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:1
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:-

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2BASEDMG:3
P1NODECISION

---

# Deployed_OnAttack_NoDamagedUnit_NoOffer
#// No unit is damaged when the On Attack resolves (combat damage comes after it): nothing is offered and the attack resolves.

## GIVEN
CommonSetup: bbw/bbk/{myResources:5;myLeader:IC27_002}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE

## EXPECT
P1NODECISION
P2BASEDMG:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Deployed_OnAttack_OfferPool_IncludesDamagedRey_ExcludesUndamagedDefender
#// Rey is already damaged and attacks P2's UNDAMAGED marine (the defender takes its combat damage only after the On Attack).
#// The offer, left pending: Rey herself (no "another") and P2's other, damaged marine — not the undamaged defender.

## GIVEN
CommonSetup: bbw/bbk/{myLeader:IC27_002:1:1:0:2}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: [SOR_095:1:0 SOR_095:1:1]

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-1

---

# Deployed_OnAttack_RequestBoundary_BeforeTheChoice
#// Deployed_OnAttack_Take_GivesExperience with a request boundary between the offer and the pick.

## GIVEN
CommonSetup: bbw/bbk/{myResources:5;myLeader:IC27_002}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:1
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Deployed_OnAttack_TwinSuns_FarSeatDamagedUnit
#// Three seats. Deployed Rey attacks seat 2's undamaged marine; seat 3 has two damaged marines. The far seat's units are legal
#// — cannot pass at two seats. P1 gives the Experience to seat 3's second marine.

## GIVEN
CommonSetup3P: bbw/bbk/bbk/{myLeader:IC27_002:1:1:0}
SkipPreGame: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: [SOR_095:1:1 SOR_095:1:2]

## WHEN
- P1>AttackGroundArena:0:p2GroundArena-0
- P1>AnswerDecision:p3GroundArena-1

## EXPECT
P3GROUNDARENAUNIT:1:UPGRADECOUNT:1
P3GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Epic_FourResources_CannotDeploy
#// "If you control 5 or more resources": at 4, the Epic Action is refused and Rey stays on her leader side.

## GIVEN
CommonSetup: bbw/bbk/{myResources:4;myLeader:IC27_002}
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENACOUNT:0

---

# Epic_FiveResources_Deploys
#// At exactly 5, Rey deploys as a 3/6 Ground unit.

## GIVEN
CommonSetup: bbw/bbk/{myResources:5;myLeader:IC27_002}
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:CARDID:IC27_002
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:6
