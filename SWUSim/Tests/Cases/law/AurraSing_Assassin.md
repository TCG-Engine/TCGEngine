# DeployedDefeatLowHp
#// LAW_004 Aurra Sing (deployed) — "When Deployed: You may defeat a non-leader unit with 5 or less
#// remaining HP." Deploy Aurra (7+ resources); the only eligible enemy is SOR_128 (3/1, 1 HP) — SOR_046
#// (3/7) is NOT eligible. P1 defeats SOR_128, leaving SOR_046.

## GIVEN
CommonSetup: ybk/grw/{
  myLeader:LAW_004;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046

---

# FrontDefeatLowHp
#// LAW_004 Aurra Sing (leader front) — "Action [Exhaust]: Defeat a non-leader unit with 1 or less
#// remaining HP." P2's SOR_128 (3/1) has 1 remaining HP → it is the only legal target and is defeated.

## GIVEN
CommonSetup: ybk/grw/{
  myLeader:LAW_004;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENACOUNT:0

---

# FrontNoTarget_UsableAnyway
#// LAW_004 Aurra Sing (leader front) — "Action [Exhaust]: Defeat a non-leader unit with 1 or less
#// remaining HP." With NO unit at 1-or-less HP (only the enemy 3/7 SOR_046 at full HP), the Action is
#// still usable (CR 6.4.587.c "Use it anyway"): the Exhaust cost is paid but nothing is defeated.

## GIVEN
CommonSetup: ybk/grw/{
  myLeader:LAW_004;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P2GROUNDARENACOUNT:1

---

# FrontDefeatFriendly1Hp
#// LAW_004 Aurra Sing (front) — the defeat is not restricted to enemies: with a friendly 1-HP unit
#// (SOR_128) and a damaged-to-1-HP enemy (SOR_046 with 6 damage) both eligible, P1 chooses to defeat the
#// FRIENDLY SOR_128; the enemy survives.

## GIVEN
CommonSetup: ybk/grw/{
  myLeader:LAW_004;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:6

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1

---

# DeployedNoTarget_NoOp
#// LAW_004 Aurra Sing (deployed) — "When Deployed: You may defeat a non-leader unit with 5 or less
#// remaining HP." With the only enemy unit at 7 HP (SOR_046, above the 5-HP threshold) there is no legal
#// target: Aurra deploys and nothing is defeated.

## GIVEN
CommonSetup: ybk/grw/{
  myLeader:LAW_004;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENACOUNT:1

---

# DeployedMayDecline
#// LAW_004 Aurra Sing (deployed) — the When Deployed defeat is optional ("you may"): with an eligible
#// 1-HP enemy (SOR_128) present, P1 declines and the enemy survives.

## GIVEN
CommonSetup: ybk/grw/{
  myLeader:LAW_004;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:PASS

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENACOUNT:1

---

# DeployedDefeatFriendly
#// LAW_004 Aurra Sing (deployed) — the When Deployed defeat may target a FRIENDLY non-leader unit: the
#// only eligible unit is the friendly 1-HP SOR_128, which P1 defeats. Deployed Aurra remains in the
#// ground arena.

## GIVEN
CommonSetup: ybk/grw/{
  myLeader:LAW_004;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1GroundArena: SOR_128:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:1
