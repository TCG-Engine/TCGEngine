# AttackEnd_Disclose_AttackWithAnother
#// SEC_096 Ahsoka Tano (Ground, 2/5, Command/Heroism) — When this unit completes an attack (and
#//   survives): you may disclose CommandHeroism → attack with another unit.
#// Ahsoka (idx0) attacks P2 base (2 power, survives — bases don't counter). On attack-end: disclose
#// SEC_094 (Command,Heroism) → attack with another unit; the only other ready unit is SOR_095 (idx1,
#// 3 power) → it attacks the base too. Total base damage 2 + 3 = 5.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_096:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_094

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:myHand-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
P1NODECISION

---

# DeclineDisclose_NoSecondAttack
#// SEC_096 Ahsoka Tano — decline the attack-end disclose → no second attack.
#// Ahsoka attacks the base (2 damage); P1 declines (AnswerDecision:-), so SOR_095 stays READY and
#// the base takes only 2.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_096:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_094

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:READY
P1NODECISION

---

# DieOnAttack_NoDisclose
#// SEC_096 Ahsoka Tano (2/5) — the disclose window only opens if she SURVIVES the attack. She attacks
#//   SOR_232 AT-ST (6/7); the AT-ST deals 6 back and defeats Ahsoka (5 HP), so no disclose is offered
#//   and P1 has no follow-up decision. The AT-ST takes her 2 power.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_096:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION
