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
---

# Offer_BothBasesAreSelectable
#// LAW_181 Cloud-Rider Veteran — "Deal 2 damage to A BASE" names no controller, so both bases are offered
#// and the player chooses. The two existing sections each answer one side, which an implementation
#// hardcoded to either base would also satisfy; this one asserts the POOL itself by leaving the pick
#// pending.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_181:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myBase-0&theirBase-0

---

# OnAttackFiresWhenAttackingAUNITToo
#// LAW_181 Cloud-Rider Veteran — the trigger is "On Attack", not "when this unit attacks a base", so it
#// fires on a unit attack as well. The 1/4 Veteran attacks a 1/1 Battle Droid token (defeating it and
#// surviving the 1 counter damage) and the ability still deals its 2 to the chosen base. Both existing
#// sections attack a base, so neither could catch a trigger wrongly gated on the attack's target.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_181:1:0
WithP2GroundArena: TWI_T01:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:2
P1GROUNDARENAUNIT:0:CARDID:LAW_181
P1GROUNDARENAUNIT:0:DAMAGE:1
