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

---

# CreatedTokenThisPhase_SurvivesTheRequestBoundary
#// LAW_016 The Client — request-boundary guard on the "you created a token this phase" state, which must
#// outlive the action that created the token. LAW_235 Lady Proxima's Action creates a Credit; SOR_142 Sabine
#// then attacks SEC_080, leaving a REAL pending choose for her On Attack damage (MZMAYCHOOSE over
#// theirGroundArena-0 & theirBase-0 & myBase-0); a serialize round-trip is inserted before that answer. Only
#// then does The Client use its Action: the phase state survived, so the enemy SEC_080 is still exhausted.
#// (FrontNoTokenDoesNothing is the negative: with no token created, the same Action exhausts nothing.)

## GIVEN
CommonSetup: yyk/grw/{
  myLeader:LAW_016;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [LAW_235:1:0 SOR_142:1:0]
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AttackGroundArena:1:theirGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirBase-0
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1CREDITCOUNT:1

---

# FrontOffer_EnemyUnitsAnyArenaIncludingDeployedLeader
#// LAW_016 The Client (leader front) — OFFER assertion for "exhaust an ENEMY unit." Lady Proxima creates
#// the Credit that satisfies the "if you created a token this phase" gate, then the Action's pool is read
#// while pending. Discriminating board: both of P1's own units (LAW_235, SOR_095) are OUT on controller
#// scope; P2's ready SEC_080, P2's ALREADY-EXHAUSTED SOR_046 (still a legal pick — the text says "an
#// enemy unit", not "a ready enemy unit"), P2's DEPLOYED leader at ground idx 2 (a deployed leader IS a
#// unit, so it is correctly IN) and P2's space TIE Fighter (no arena restriction) are all IN.
#// COVERAGE: offer=this section (controller scope + both arenas + exhausted-still-eligible + deployed
#//           leader included) · reqboundary=CreatedTokenThisPhase_SurvivesTheRequestBoundary ·
#//           control=N/A (the "you created a token" state is per-seat and already covered by
#//           DeployedOnlyEnemyTokenDoesNothing; no control-change path) · boundary
#//           pair=FrontExhaustAfterToken (token created) vs FrontNoTokenDoesNothing (none — Action still
#//           usable, CR 6.4.587.c) · decline=N/A (no "you may"; the exhaust is mandatory once gated)

## GIVEN
CommonSetup: yyk/grw/{
  myLeader:LAW_016;
  myBase:SOR_028;
  theirLeaderDeployed:true
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [LAW_235:1:0 SOR_095:1:0]
WithP2GroundArena: [SEC_080:1:0 SOR_046:0:0]
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2&theirSpaceArena-0
P2GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:2:ISLEADERUNIT
