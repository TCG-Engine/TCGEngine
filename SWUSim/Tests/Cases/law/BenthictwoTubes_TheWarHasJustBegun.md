# OnAttackDealGround
#// LAW_057 Benthic "Two Tubes" (3/2) — On Attack: deal 1 damage to an enemy ground unit. Attacks the
#// base; deal 1 to the enemy SOR_046.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_057:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# WhenDefeatedDealBase
#// LAW_057 Benthic "Two Tubes" (3/2) — When Defeated: deal 1 damage to a base. Benthic attacks SOR_046
#// (3/7) and dies to the counter (decline the OnAttack deal); its When Defeated deals 1 to P2's base.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_057:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-
- P1>AnswerDecision:theirBase-0

## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:1

---

# OnAttackNoTargetsNoOp
#// LAW_057 Benthic "Two Tubes" — On Attack: with NO enemy ground unit, the "deal 1 to an enemy ground unit"
#// ability does nothing (it never redirects to friendly units). Benthic attacks the base for 3; friendly
#// units (SOR_095 ground, SOR_178 space) stay undamaged.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: [LAW_057:1:0 SOR_095:1:0]
WithP1SpaceArena: SOR_178:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:1:DAMAGE:0
P1SPACEARENAUNIT:0:DAMAGE:0

---

# WhenDefeatedDealFriendlyBase
#// LAW_057 Benthic "Two Tubes" — When Defeated: deal 1 damage to a base; you may choose YOUR OWN base.
#// Benthic attacks SOR_046 (3/7), declines the On Attack deal, and dies to the counter; its When Defeated
#// deals 1 to P1's own base.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_057:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-
- P1>AnswerDecision:myBase-0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:1
P2BASEDMG:0
