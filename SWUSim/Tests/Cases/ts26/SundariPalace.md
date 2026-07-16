# DefeatsResourcesAtRegroup
#// TS26_012 Sundari Palace — the "resource a card and ready it" clause is paid for at the start of the next
#// regroup phase: defeat that many friendly resources. After resourcing SEC_080 (2 → 3) and passing to
#// regroup, 1 resource is defeated (3 → 2).
## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_012;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SEC_080
## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>Pass
## EXPECT
P1RESCOUNT:2

---

# EpicResourceReady
#// TS26_012 Sundari Palace (Base, Cunning) — Epic Action: for each friendly leader unit, you may resource
#// a card from your hand and ready it. With one deployed leader unit, resource SEC_080 (2 resources → 3),
#// emptying the hand.
## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_012;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SEC_080
## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
## EXPECT
P1RESCOUNT:3
P1HANDCOUNT:0
P1BASE:EPICUSED
