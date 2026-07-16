# Control_FrontActionFiresWithoutIt
#// TWI_255 Brain Invaders — control case for the front-action suppression: with NO Brain Invaders in
#// play, Luke Skywalker's (SOR_005) front Action resolves normally and exhausts the leader. Confirms the
#// suppression in the sibling test is caused by Brain Invaders.
## GIVEN
CommonSetup: bbw/rrk/{myResources:1}
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED

---

# Control_KeywordPresentWithoutIt
#// TWI_255 Brain Invaders — control case: the leader-ability loss is conditional on a Brain Invaders being
#// in play. With NO Brain Invaders on the board, a deployed SOR_003 keeps its Sentinel keyword. (Confirms
#// the suppression in the sibling test is caused by Brain Invaders, not by the deploy itself.)
## GIVEN
CommonSetup: bbw/rrk/{myLeader:SOR_003;myLeaderDeployed:true}
P1OnlyActions: true
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_003
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# DeployedLeaderLosesKeyword
#// TWI_255 Brain Invaders (Unit, 4/2, Ground) — "Each leader loses all abilities except for epic actions
#// and can't gain abilities." A deployed SOR_003 (leader unit) normally has Sentinel; while an enemy Brain
#// Invaders is in play, that keyword (an ability) is suppressed — the deployed leader has no Sentinel.
## GIVEN
CommonSetup: bbw/rrk/{myLeader:SOR_003;myLeaderDeployed:true}
P1OnlyActions: true
WithP2GroundArena: TWI_255:1:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_003
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# EpicDeployStillWorks
#// TWI_255 Brain Invaders — the ability loss is "except for epic actions." A leader's Epic deploy is an
#// epic action, so it still works while a Brain Invaders is in play: P1 deploys Luke Skywalker (SOR_005,
#// cost 6) normally. (After deploying, the leader UNIT would have its abilities suppressed — covered by
#// the deployed-keyword test — but the deploy itself is unaffected.)
## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
P1OnlyActions: true
WithP2GroundArena: TWI_255:1:0
## WHEN
- P1>DeployLeader
## EXPECT
P1LEADER:DEPLOYED

---

# FrontLeaderActionSuppressed
#// TWI_255 Brain Invaders — a front (undeployed) leader's activated Action is an ability, so it is lost
#// while a Brain Invaders is in play. P1's Luke Skywalker (SOR_005) tries to use his "Action [1 resource]"
#// but it does nothing: the leader stays ready and the resource is not spent.
## GIVEN
CommonSetup: bbw/rrk/{myResources:1}
P1OnlyActions: true
WithP2GroundArena: TWI_255:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:READY
P1RESAVAILABLE:1
