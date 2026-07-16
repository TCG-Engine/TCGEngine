# AttackBase_Deal3
#// SOR_133 Seventh Sister (3/6) — "When this unit deals combat damage to an opponent's base: You
#// may deal 3 damage to a ground unit that opponent controls." She attacks the base (3 damage),
#// then deals 3 to the opponent's 3/7 ground unit.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_133:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# AttackBase_NoEnemyUnit_Fizzle
#// SOR_133 Seventh Sister — base-damage rider with NO enemy ground unit to target. The "may deal 3
#// to a ground unit" has zero legal targets → SWUQueueMayChooseTarget no-ops (no dangling decision,
#// no crash). Base still takes her 3 combat damage; P1 keeps a clean turn (no pending decision).

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_133:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1NODECISION
P1GROUNDARENACOUNT:1

---

# Saboteur_AttacksBasePastSentinel
#// SOR_133 Seventh Sister — Saboteur lets her ignore Sentinel and attack the BASE even though P2
#// controls a Sentinel (SOR_063, 2/4). The base takes her 3 combat damage, which then fires the
#// rider: deal 3 to a ground unit P2 controls → the Sentinel takes 3 (survives at 4 HP). She takes
#// no counter (bases don't fight back). Proves Saboteur + the base-damage trigger compose.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_133:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:0
