# DefeatsResourcesAtRegroup
#// TS26_12 Sundari Palace — the "resource a card and ready it" clause is paid for at the start of the next
#// regroup phase: defeat that many friendly resources. After resourcing SEC_080 (2 → 3) and passing to
#// regroup, 1 resource is defeated (3 → 2).
## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12;myLeaderDeployed:true}
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
#// TS26_12 Sundari Palace (Base, Cunning) — Epic Action: for each friendly leader unit, you may resource
#// a card from your hand and ready it. With one deployed leader unit, resource SEC_080 (2 resources → 3),
#// emptying the hand.
## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12;myLeaderDeployed:true}
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

---

# NoFriendlyLeaderUnits_NothingHappens
#// TS26_12 Sundari Palace — "FOR EACH friendly LEADER UNIT". With the leader undeployed there are none,
#// so the Epic Action offers nothing: the hand keeps its card, the resource count is unchanged, and no
#// decision is left pending (and so nothing is queued to be defeated at regroup either).

## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SEC_080

## WHEN
- P1>UseBaseAbility

## EXPECT
P1RESCOUNT:2
P1HANDCOUNT:1
P1NODECISION

---

# NoCardsInHand_NothingHappens
#// TS26_12 Sundari Palace — the other empty input: a deployed leader unit is present but there is no card
#// to resource, so the resource count stays put and no decision opens.

## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2

## WHEN
- P1>UseBaseAbility

## EXPECT
P1RESCOUNT:2
P1NODECISION

---

# ChoosingNoCards_NothingIsResourcedAndNothingIsDefeatedAtRegroup
#// TS26_12 Sundari Palace — "you MAY resource a card". Declining leaves the card in hand and the resource
#// count at 2; passing on into the regroup phase then defeats nothing, since the delayed cost is owed only
#// for cards actually resourced.

## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SEC_080

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:-
- P1>Pass

## EXPECT
P1RESCOUNT:2
P1HANDCOUNT:1
