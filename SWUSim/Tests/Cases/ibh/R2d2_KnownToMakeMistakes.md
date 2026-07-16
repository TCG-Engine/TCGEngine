# OnAttack_CommandControlled_Exhausts
#// IBH_011 R2-D2 (Ground, 1/4, Cunning/Heroism) — On Attack: if you control a Command unit, exhaust an
#//   enemy ground unit that costs 4 or less. P1 controls a Command unit (SOR_095). R2-D2 attacks the base;
#//   the cost-2 enemy is exhausted, while a cost-8 enemy is NOT an eligible target (stays ready).

## GIVEN
CommonSetup: yyw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: IBH_011:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY
P2BASEDMG:1
P1NODECISION

---

# OnAttack_NoCommand_NoExhaust
#// IBH_011 R2-D2 — On Attack with NO Command unit controlled: the exhaust does not happen (R2-D2 itself
#//   is Cunning/Heroism, not Command). The cost-2 enemy stays ready and no decision is presented.

## GIVEN
CommonSetup: yyw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: IBH_011:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:READY
P2BASEDMG:1
P1NODECISION
