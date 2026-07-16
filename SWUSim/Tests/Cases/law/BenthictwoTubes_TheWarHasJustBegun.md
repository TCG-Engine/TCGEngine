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
