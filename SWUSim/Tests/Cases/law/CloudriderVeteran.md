# OnAttackDealBase
#// LAW_181 Cloud-Rider Veteran (1/4) — On Attack: deal 2 damage to a base. Attacks the base: 1 (combat)
#// + 2 (ability) = 3.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_181:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:3

---

# OnAttackDealOwnBase
#// LAW_181 Cloud-Rider Veteran — "deal 2 damage to a base" has no "enemy" qualifier, so the attacker MAY
#// choose their OWN base. Attacking the enemy base still deals 1 combat there, but the 2 ability damage is
#// steered to P1's own base.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_181:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:2
P2BASEDMG:1