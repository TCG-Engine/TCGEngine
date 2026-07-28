# OnAttackArenaAOE
#// LAW_178 Persecutor (9/7, space) — When Played/On Attack: choose an arena. You may deal 3 damage to
#// each unit in that arena. Attacks the base; choose Ground -> each ground unit takes 3 (SOR_046 3/7
#// survives at DAMAGE:3; SOR_095 3/3 dies).

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_178:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Ground

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# WhenPlayedSpaceAOE
#// LAW_178 Persecutor (9/7, space) — When Played: choose an arena, may deal 3 to each unit in it.
#// Played from hand it enters the space arena, so choosing Space hits Persecutor itself (DAMAGE:3) and
#// each other space unit (JTL_037 4/5 -> DAMAGE:3); the enemy ground unit is untouched.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1Resources: 9
WithP1Hand: LAW_178
WithP2SpaceArena: JTL_037:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_178
P1SPACEARENAUNIT:0:DAMAGE:3
P2SPACEARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# OnAttackSpaceAOE
#// LAW_178 Persecutor — On Attack: choosing Space deals 3 to each space unit (Persecutor itself and the
#// enemy JTL_037 4/5 -> DAMAGE:3 each); the enemy ground unit is untouched. Persecutor still attacks the
#// base for 9.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_178:1:0
WithP2SpaceArena: JTL_037:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Space

## EXPECT
P2BASEDMG:9
P1SPACEARENAUNIT:0:DAMAGE:3
P2SPACEARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# WhenPlayed_Decline_NoDamage
#// LAW_178 Persecutor — "You MAY deal 3 damage to each unit in that arena." The effect can be declined:
#// choosing Pass deals no damage. Persecutor enters the space arena undamaged and the enemy units are
#// untouched.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1Resources: 9
WithP1Hand: LAW_178
WithP2SpaceArena: JTL_037:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_178
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
