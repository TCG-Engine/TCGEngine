# OnAttack_DefeatShielded
#// LOF_037 Darth Vader — On Attack: defeat an enemy unit with a Shield token on it. Vader (5 power)
#// attacks the base; on attack he defeats the shielded enemy 3/7, then deals 5 to the base.

## GIVEN
CommonSetup: bbk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_037:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:5

---

# WhenPlayed_ShieldBoth
#// LOF_037 Darth Vader — When Played: give a Shield token to a friendly unit and to an enemy unit. P1
#// shields its SOR_095 and the enemy 3/7.

## GIVEN
CommonSetup: bbk/ggw/{myResources:6;handCardIds:LOF_037}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# WhenPlayed_NoEnemy_OnlyFriendlyShield
#// LOF_037 Darth Vader — When Played gives a Shield to a friendly unit AND to an enemy unit. With no enemy
#// units on the board, only the friendly half resolves; the sole friendly is Vader himself, so he shields
#// himself. Ref: "when played should only give a shield to a friendly unit as there is no enemy units".

## GIVEN
CommonSetup: bbk/ggw/{myResources:6;handCardIds:LOF_037}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENACOUNT:1

---

# OnAttack_NoShieldedEnemy_NoTrigger
#// LOF_037 Darth Vader — On Attack: defeat an enemy unit WITH a Shield token. With no shielded enemy units the
#// reaction has no legal target and does nothing (no decision). Vader (5) attacks an unshielded 3/7 Consular
#// Security Force (SOR_046): it takes 5 combat damage and survives; nothing else happens. Ref: "should not be
#// able to defeat any unit as there are no enemy shielded units".

## GIVEN
CommonSetup: bbk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_037:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENACOUNT:1
P1NODECISION
