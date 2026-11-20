# TrenchLeaderAbility_ById
#// Named Action abilities on a LEADER: Admiral Trench's front Action by id. "Action [Exhaust]: Discard a card that
#// costs 3 or more from your hand. If you do, draw a card." — discards the 3-cost card, draws one, exhausts Trench.
## GIVEN
CommonSetup: ggw/ggw/{myLeader:JTL_014;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_046]
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P1>UseLeaderAbility:0:JTL_014.0
- P1>AnswerDecision:myHand-0
## EXPECT
P1DISCARDCOUNT:1
P1HANDCOUNT:1
P1LEADER:EXHAUSTED
LOGCONTAINS:P1 used [[JTL_014|Admiral Trench]]'s Action

---

# TrenchDeployId_IsNotALeaderAbility_Refused
#// Trench's second front Action is his DEPLOY — it stays the "Deploy Leader" input and is not a leader-ability entry.
#// Naming it as a leader ability does nothing (no discard prompt, no deploy, Trench stays ready).
## GIVEN
CommonSetup: ggw/ggw/{myLeader:JTL_014;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_046]
## WHEN
- P1>UseLeaderAbility:0:JTL_014.1
## EXPECT
P1NODECISION
P1LEADER:READY
P1HANDCOUNT:1

---

# TrenchDeploy_StillDeployLeaderInput
#// The deploy is unchanged: DeployLeader deploys Trench (his repeatable "Action [3 resources, Exhaust]" deploy).
## GIVEN
CommonSetup: ggw/ggw/{myLeader:JTL_014;myResources:6}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>DeployLeader:0
## EXPECT
P1LEADER:DEPLOYED

---

# ExhaustedLeader_IdRefused
## GIVEN
CommonSetup: ggw/ggw/{myLeader:JTL_014:0;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_046]
## WHEN
- P1>UseLeaderAbility:0:JTL_014.0
## EXPECT
P1NODECISION
P1HANDCOUNT:1
