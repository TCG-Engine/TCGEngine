# Deployed_OnAttack_HealTwoFriendlies
#// IBH_001 Leia Organa (deployed) — On Attack: heal 1 from a friendly unit and 1 from another friendly
#//   unit. Leia deploys (5 resources), attacks the base; two damaged space units (2 dmg each) each heal 1.

## GIVEN
CommonSetup: ggw/brk/{
  myLeader:IBH_001
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:2
WithP1SpaceArena: SOR_237:1:2

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:mySpaceArena-1

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:1
P1SPACEARENAUNIT:1:DAMAGE:1
P2BASEDMG:3

---

# LeaderAction_HealFriendly
#// IBH_001 Leia Organa — Leader Action [1 resource, Exhaust]: heal 1 damage from a friendly unit. A
#//   damaged 3/3 (2 damage) heals to 1; Leia exhausts and 1 resource is spent.

## GIVEN
CommonSetup: ggw/brk/{
  myLeader:IBH_001
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SOR_095:1:2

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# LeaderAction_Unaffordable_NoOp
#// IBH_001 Leia Organa — with no ready resource, the Action can't pay its 1-resource cost: full no-op
#//   (Leia stays ready, the unit is not healed).

## GIVEN
CommonSetup: ggw/brk/{
  myLeader:IBH_001
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:2

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:READY
