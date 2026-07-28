# FrontExhaustAfterToken
#// LAW_016 The Client (leader front) — "Action [Exhaust]: If you created a token this phase, exhaust an
#// enemy unit." Lady Proxima's action first creates a Credit (a token created this phase); then The
#// Client's action exhausts the enemy SEC_080.

## GIVEN
CommonSetup: yyk/grw/{
  myLeader:LAW_016;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_235:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1CREDITCOUNT:1

---

# FrontNoTokenDoesNothing
#// LAW_016 The Client (front) — "Action [Exhaust]: If you created a token this phase, exhaust an enemy."
#// The "if you created a token" is a conditional EFFECT, so the Action is usable even with NO token created
#// (CR 6.4.587.c): it still pays the [Exhaust] cost (leader exhausted) but the enemy SEC_080 stays ready.

## GIVEN
CommonSetup: yyk/grw/{
  myLeader:LAW_016;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:READY
P1LEADER:EXHAUSTED

---

# FrontTokenUpgradeExhausts
#// LAW_016 The Client (front) — a token UPGRADE counts as "created a token this phase." Bail Organa's
#// Action gives an Experience token to a friendly unit; The Client's Action then exhausts the enemy SEC_080.

## GIVEN
CommonSetup: yyk/grw/{
  myLeader:LAW_016;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_094:1:0 SOR_095:1:0]
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:EXHAUSTED

---

# FrontUnitTokenExhausts
#// LAW_016 The Client (front) — a UNIT token counts too. Battle Droid Escort (TWI_229) creates a Battle
#// Droid token when played; The Client's Action then exhausts the enemy SEC_080.

## GIVEN
CommonSetup: yyk/grw/{
  myLeader:LAW_016;
  myBase:SOR_028;
  myResources:5
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TWI_229
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:EXHAUSTED

---

# DeployedNoTokenDoesNothing
#// LAW_016 The Client (deployed) — the deployed side's On-Attack ability is conditional on having created a
#// token this phase. With no token, The Client attacks the base but does not exhaust the enemy SEC_080.

## GIVEN
CommonSetup: yyk/grw/{
  myLeader:LAW_016:1:1:1;
  myBase:SOR_028;
  myResources:8
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENAUNIT:0:READY

---

# DeployedOnlyEnemyTokenDoesNothing
#// LAW_016 The Client (deployed) — the ability checks a token YOU created; a token the opponent created
#// does not count. Jesse (TWI_145) makes the OPPONENT create 2 Battle Droid tokens; when The Client then
#// attacks the base it does NOT exhaust the enemy SEC_080. (P2's ground count of 3 confirms Jesse resolved.)

## GIVEN
CommonSetup: yyk/grw/{
  myLeader:LAW_016:1:1:1;
  myBase:SOR_028;
  myResources:8
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TWI_145
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENACOUNT:3
P2GROUNDARENAUNIT:0:READY

---

# DeployedCreditTokenExhausts
#// LAW_016 The Client (deployed) — a Credit token counts. Unmarked Credits (LAW_244) creates a Credit token
#// for P1; The Client then attacks the base and exhausts the enemy SEC_080.

## GIVEN
CommonSetup: yyk/grw/{
  myLeader:LAW_016:1:1:1;
  myBase:SOR_028;
  myResources:8
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: LAW_244
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1LEADER:DEPLOYED
P1CREDITCOUNT:1
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# DeployedForceTokenExhausts
#// LAW_016 The Client (deployed) — a FORCE token also counts as "created a token this phase". P1 plays
#// Directed by the Force (LOF_123, "The Force is with you") to create its Force token; The Client then
#// attacks the base and exhausts the enemy SEC_080.

## GIVEN
CommonSetup: yyk/grw/{
  myLeader:LAW_016:1:1:1;
  myBase:SOR_028;
  myResources:8
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: LOF_123
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1LEADER:DEPLOYED
P1HASFORCE
P2GROUNDARENAUNIT:0:EXHAUSTED
