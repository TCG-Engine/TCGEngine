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
