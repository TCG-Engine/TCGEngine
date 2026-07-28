# AttackEndReturnHeal
#// LAW_088 Anakin Skywalker (2/4) — When a friendly unit's attack ends: if no other units have attacked
#// this phase, you may return it to its owner's hand. If you do, heal 2 from your base. Anakin (the only
#// attacker) attacks the base, then returns himself and heals 2.

## GIVEN
CommonSetup: byk/bgw/{myBaseDamage:2}
P1OnlyActions: true
WithP1GroundArena: LAW_088:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1BASEDMG:0

---

# ReturnAnotherFriendlyUnitHeal
#// LAW_088 Anakin Skywalker — the trigger watches ANY friendly unit's attack, not just Anakin's. A
#// second friendly unit (SOR_095) attacks the base (the only attack this phase) and survives; Anakin's
#// ability offers to return SOR_095 to hand and heal 2. Accept -> SOR_095 to hand, base healed 2->0.

## GIVEN
CommonSetup: byk/bgw/{myBaseDamage:2}
P1OnlyActions: true
WithP1GroundArena: [LAW_088:1:0 SOR_095:1:0]

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_088
P1HANDCOUNT:1
P1BASEDMG:0

---

# MayReturnPassLeavesUnitAndNoHeal
#// LAW_088 Anakin Skywalker — the return is a "you may". Declining leaves the attacker in play and heals
#// nothing. SOR_095 attacks the base and survives; pass the ability -> SOR_095 stays, base still 2.

## GIVEN
CommonSetup: byk/bgw/{myBaseDamage:2}
P1OnlyActions: true
WithP1GroundArena: [LAW_088:1:0 SOR_095:1:0]

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:2
P1HANDCOUNT:0
P1BASEDMG:2

---

# AttackerDefeatedNoTrigger
#// LAW_088 Anakin Skywalker — if the attacker does not survive, the ability does not offer a return.
#// SOR_095 (3/3) attacks the 3/7 SOR_046 and dies to the counterattack; no return prompt, base unchanged.

## GIVEN
CommonSetup: byk/bgw/{myBaseDamage:2}
P1OnlyActions: true
WithP1GroundArena: [LAW_088:1:0 SOR_095:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_088
P1DISCARDCOUNT:1
P1BASEDMG:2
P1NODECISION

---

# AnotherUnitAttackedFirstNoTrigger
#// LAW_088 Anakin Skywalker — the return only fires if no OTHER unit has attacked this phase. SOR_095
#// attacks the base first (prompt passed), then SOR_164 attacks the base; because SOR_095 already
#// attacked, no return prompt appears for SOR_164. Nothing returns, base unchanged.

## GIVEN
CommonSetup: byk/bgw/{myBaseDamage:2}
P1OnlyActions: true
WithP1GroundArena: [LAW_088:1:0 SOR_095:1:0 SOR_164:1:0]

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:NO
- P1>AttackGroundArena:2:BASE

## EXPECT
P1GROUNDARENACOUNT:3
P1HANDCOUNT:0
P1BASEDMG:2
P1NODECISION
