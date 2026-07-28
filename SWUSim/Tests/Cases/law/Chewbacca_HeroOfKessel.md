# FrontDefeatResourceDeal2Credit
#// LAW_013 Chewbacca (leader front) — "Action [1 resource, Exhaust, defeat a friendly resource]: Deal 2
#// damage to a unit and create a Credit token." Pay 1 + defeat a resource → 1 Credit created and 2 damage
#// to P2's SOR_128 (3/1), defeating it.

## GIVEN
CommonSetup: yrw/grw/{
  myLeader:LAW_013;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myResources-0

## EXPECT
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:1


---

# Front_CannotActivate_EmptyResources
#// LAW_013 Chewbacca (undeployed) — the Action costs "1 resource, Exhaust, defeat a friendly resource".
#// With ZERO resources there is nothing to defeat (and a Credit token can't substitute the resource that
#// must be defeated), so the action is unavailable: using it is a full no-op — leader stays ready, the
#// Credit is kept, no enemy unit is damaged, and no decision is raised.

## GIVEN
CommonSetup: yrw/grw/{
  myLeader:LAW_013;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1Credits: 1
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1CREDITCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# Deployed_OnAttack_DefeatResource_DealTwoCreateCredit
#// LAW_013 Chewbacca (deployed) — On Attack: "You may defeat a friendly resource. If you do, deal 2 damage
#// to a unit and create a Credit token." Deployed Chewbacca (5/6) attacks P2's base for 5; the on-attack
#// trigger defeats one of the 4 resources, then deals 2 to the enemy SEC_080 (3/3, survives) and creates a
#// Credit. Resource pool drops from 4 to 3.

## GIVEN
CommonSetup: yrw/grw/{
  myLeader:LAW_013:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:5
P2GROUNDARENAUNIT:0:DAMAGE:2
P1CREDITCOUNT:1
P1RESCOUNT:3
P1RESAVAILABLE:3

---

# Deployed_OnAttack_CanBeSkipped
#// LAW_013 Chewbacca (deployed) — the On Attack ability is optional ("You may"). Chewbacca attacks P2's
#// base for 5 and the player declines to defeat a resource, so no damage is dealt to any unit, no Credit
#// is created, and the resource pool is untouched.

## GIVEN
CommonSetup: yrw/grw/{
  myLeader:LAW_013:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:PASS

## EXPECT
P2BASEDMG:5
P2GROUNDARENAUNIT:0:DAMAGE:0
P1CREDITCOUNT:0
P1RESCOUNT:4
P1RESAVAILABLE:4

---

# Deployed_OnAttack_EmptyResources_AutoSkips
#// LAW_013 Chewbacca (deployed) — with ZERO resources there is no friendly resource to defeat, so the
#// On Attack ability auto-passes with no prompt: Chewbacca still attacks the base for 5, but deals no
#// unit damage and creates no Credit.

## GIVEN
CommonSetup: yrw/grw/{
  myLeader:LAW_013:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P2GROUNDARENAUNIT:0:DAMAGE:0
P1CREDITCOUNT:0
