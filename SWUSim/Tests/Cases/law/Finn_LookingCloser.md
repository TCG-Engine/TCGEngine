# OnAttackShieldNonUnique
#// LAW_095 Finn (6/5, Ambush) — On Attack: you may give a Shield token to a non-unique unit. Attacks the
#// base; shield the non-unique SEC_080.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_095:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# OnAttackShieldEnemyNonUnique
#// LAW_095 Finn — the Shield may be given to a non-unique unit of EITHER side. Attacks the base and shields
#// the enemy non-unique SOR_046.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# OnAttackNoNonUniqueAutoPass
#// LAW_095 Finn — only NON-unique units are valid Shield targets. With every unit unique (Finn attacking,
#// enemy Greedo SOR_204), the ability finds no target and auto-passes: no Shields are given and the attack
#// still lands on the enemy base for 6.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_095:1:0
WithP2GroundArena: SOR_204:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2BASEDMG:6
