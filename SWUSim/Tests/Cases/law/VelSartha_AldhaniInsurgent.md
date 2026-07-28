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
