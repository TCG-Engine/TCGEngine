# FrontExpOppCredit
#// LAW_006 Vel Sartha (leader front) — "Action [Exhaust]: Give an Experience token to a unit. An
#// opponent creates a Credit token." SEC_080 (the only unit) auto-gets the Experience token (→ 4/4) and
#// P2 (the opponent) creates 1 Credit.

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_006;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P2CREDITCOUNT:1

---

# DeployedOnAttackExpSelfCredit
#// LAW_006 Vel Sartha (deployed) — On Attack: give an Experience token to a unit, then an opponent creates
#// a Credit token. Deployed Vel attacks P2's base and gives the Experience token to HERSELF (leader can be
#// the target); P2 creates 1 Credit.

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_006:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P2CREDITCOUNT:1

---

# DeployedOnAttackPassNoCredit
#// LAW_006 Vel Sartha (deployed) — the On Attack ability is optional ("you may"). Declining (Pass) means
#// no Experience token is given, so the "if you do" Credit clause does NOT fire: P2 has 0 Credits.

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_006:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:PASS

## EXPECT
P2CREDITCOUNT:0

---

# FrontNoUnits_StillOppCredit
#// LAW_006 Vel Sartha (leader front) — "An opponent creates a Credit token" is a SEPARATE, unconditional
#// sentence: with NO unit in play to receive the Experience, the leader still exhausts and P2 still creates
#// 1 Credit. (Contrast the deployed On-Attack side, which is "if you do".)

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_006;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2CREDITCOUNT:1
P1LEADER:EXHAUSTED

---

# EpicDeployCountsAControlledEnemyOwnedResource
#// LAW_006 Vel Sartha — "Epic Action: If you control 6 or more resources, deploy this leader." The gate
#// counts resources you CONTROL, not resources you own. P1 has only 5 of their own cards resourced; the
#// sixth slot in P1's resource zone is a P2-OWNED card (the end state after an effect resources an enemy
#// card, e.g. SHD_122 Arquitens). Controlling 6 clears the gate and the leader deploys. Paired with
#// EpicDeployBlockedAtFiveOwnResources below, which holds the same board minus that one controlled slot
#// and does NOT deploy — so it is provably the P2-owned resource that crosses the threshold, not a loose
#// or absent check.

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_006;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1ResourceControlled: SOR_095:2

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED

---

# EpicDeployBlockedAtFiveOwnResources
#// LAW_006 Vel Sartha — the negative partner that makes the section above load-bearing: the identical
#// board with only the five P1-owned resources (no P2-owned slot) is one short of the "6 or more
#// resources" gate, so the Epic Action does nothing and the leader stays undeployed.

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_006;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED

---

# FrontExpOnAControlledEnemyOwnedUnitAndOpponentCredit
#// LAW_006 Vel Sartha (leader front) — "Give an Experience token to a unit. An opponent creates a Credit
#// token." Neither clause names an owner: "a unit" is unqualified and so spans both sides, and "an
#// opponent" is the opponent of the ability's CONTROLLER. P1's only friendly is a P2-OWNED SEC_080 that P1
#// controls, and P2 fields SOR_046, so the pool holds one unit per seat and cannot auto-resolve. P1 names
#// the P2-owned unit it controls: the Experience token attaches there (3/3 -> 4/4) even though P1 does not
#// own the host, and the Credit is created by P2 — P1 ends with none.
#//
#// COVERAGE: control=this section + EpicDeployCountsAControlledEnemyOwnedResource /
#//           EpicDeployBlockedAtFiveOwnResources (the Epic gate counts CONTROLLED resources, including a
#//           P2-owned one; the Experience lands on a P1-controlled / P2-owned host and "an opponent" is
#//           the CONTROLLER's opponent) · offer=this section reaches a P2-owned unit but the exact pool is
#//           not pinned with SELECTABLEEXACT · decline=DeployedOnAttackPassNoCredit (PASS on the deployed
#//           side's "you may") · boundary pair=DeployedOnAttackExpSelfCredit (token given -> Credit) vs
#//           DeployedOnAttackPassNoCredit (declined -> no Credit), and the two Epic sections above ·
#//           reqboundary=not encoded

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_006;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArenaControlled: SEC_080:2
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P2CREDITCOUNT:1
P1CREDITCOUNT:0
