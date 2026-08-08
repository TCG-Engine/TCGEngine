# WhenDefeated_SplitDamage
#// LOF_213 The Legacy Run (3/3) — When Defeated: deal 6 damage divided as you choose among enemy units. It
#// attacks a 4/7, dies to the counter, and assigns all 6 to the surviving enemy 3/7.

## GIVEN
CommonSetup: yyk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_213:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1:6

## EXPECT
P2GROUNDARENAUNIT:1:DAMAGE:6

---

# WhenDefeated_DistributeMultiple
#// LOF_213 The Legacy Run — When Defeated deals 6 damage divided as you choose among enemy units. It attacks
#// a 3/3 (SEC_080) and dies to the counter; on death P1 splits 6 among the three surviving enemies
#// (SOR_046 3, LAW_124 2, SOR_128 1) — the 1-HP SOR_128 is defeated, leaving two damaged units. (Intended: #// "should distribute damage among targets when defeated".)

## GIVEN
CommonSetup: yyk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_213:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0:3,theirGroundArena-1:2,theirGroundArena-2:1

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:DAMAGE:2

---

# WhenDefeated_SingleTarget_AllDamage
#// LOF_213 The Legacy Run — with a single surviving enemy unit, all 6 of the divided damage lands on it. It
#// attacks the 3/3 (SEC_080) and dies to the counter; the lone survivor SOR_046 (3/7) takes all 6. (Intended: #// "should automatically deal all damage to that target"; SWUSim still surfaces the distribute prompt, so the
#// 6 is assigned to the one legal target.)

## GIVEN
CommonSetup: yyk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_213:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0:6

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:6

---

# WhenDefeated_NoEnemyUnits_Nothing
#// LOF_213 The Legacy Run — the When Defeated does nothing when there are no enemy units to damage. It
#// attacks the only enemy (SEC_080 3/3) and both are defeated in the trade; with zero enemy units left the
#// ability has no legal target and simply fizzles. (Intended: "should do nothing if there are no enemy units".)

## GIVEN
CommonSetup: yyk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_213:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
