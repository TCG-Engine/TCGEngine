# Deployed_OnAttack_DealTwoToBase
#// IBH_053 Darth Vader (deployed) — On Attack: deal 2 damage to a base. Vader deploys (6 resources),
#//   attacks the enemy base: combat 3 + On Attack 2 = 5.
#// "a base" names no controller, so the 2 is a CHOICE between both bases (same as his leader-side
#//   Action below, which always offered it). P1 picks the enemy base here, so the totals are unchanged.

## GIVEN
CommonSetup: rrk/bbw/{
  myLeader:IBH_053
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:5

---

# LeaderAction_DealOneToBase
#// IBH_053 Darth Vader — Leader Action [1 resource, Exhaust]: deal 1 damage to a base. P1 chooses the
#//   enemy base for 1 damage; Vader exhausts and 1 resource is spent.

## GIVEN
CommonSetup: rrk/bbw/{
  myLeader:IBH_053
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# LeaderAction_Unaffordable_NoOp
#// IBH_053 Darth Vader — with no ready resource, the Action can't pay: full no-op (Vader stays ready,
#//   no base damage).

## GIVEN
CommonSetup: rrk/bbw/{
  myLeader:IBH_053
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:0
P1LEADER:READY

---

# Deployed_OnAttack_CanChooseYourOwnBase
#// IBH_053 Darth Vader (deployed) — "Deal 2 damage to a base" is a choice, so the own-base branch must
#// work on the DEPLOYED side exactly as it already did on the leader-side Action. Combat (power 3) hits
#// the enemy base, the ability's 2 goes to P1's own base: own 2, enemy 3.

## GIVEN
CommonSetup: rrk/bbw/{
  myLeader:IBH_053
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:2
P2BASEDMG:3
