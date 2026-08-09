# AttackEndHealIfBase
#// LAW_046 Chirrut Îmwe (8/6, Saboteur) — When Attack Ends: if this unit dealt combat damage to a base,
#// you may heal 4 from another unit. Attacks the base; heal 4 from the damaged friendly SOR_046 (4 -> 0).

## GIVEN
CommonSetup: brw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_046:1:0
WithP1GroundArena: SOR_046:1:4

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:0

---

# HealFromEnemyUnit
#// LAW_046 heals 4 from "another unit" — this can be an ENEMY unit. Chirrut attacks the base, then heals
#// 4 from the damaged enemy SOR_046 (4 -> 0).

## GIVEN
CommonSetup: brw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_046:1:0
WithP2GroundArena: SOR_046:1:4

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# NoHealWhenAttackingUnitNoBaseDamage
#// LAW_046 does not trigger if Chirrut deals no combat damage to a base. He attacks an enemy unit (no
#// Overwhelm), so the damaged friendly SOR_046 is not healed.

## GIVEN
CommonSetup: brw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_046:1:0
WithP1GroundArena: SOR_046:1:4
WithP2GroundArena: IBH_063:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:4

---

# NoHealWhenAnotherUnitDamagesBase
#// LAW_046 only cares about ITS OWN attack. Another friendly unit (SEC_080) attacks the base; Chirrut's
#// ability does not fire, so the damaged friendly SOR_046 is not healed.

## GIVEN
CommonSetup: brw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_046:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_046:1:4

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:2:CARDID:SOR_046
P1GROUNDARENAUNIT:2:DAMAGE:4
