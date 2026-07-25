# WhenDefeated_ExpToUnique
#// LOF_095 Lor San Tekka (3/2) — When Defeated: may give an Experience token to a unique unit. He attacks
#// a 4/7 and dies; P1 gives an Experience token to the unique Plo Koon.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_095:1:0
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# WhenDefeated_ExpToEnemyUnique
#// LOF_095 Lor San Tekka — the Experience target is "a unique unit", friendly OR enemy. Lor attacks the
#// enemy 4/7 (LAW_124) and dies; P1 then gives the Experience token to the enemy unique Plo Koon (LOF_050),
#// which sits behind the blocker — Experience can be given to an enemy unique unit.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_095:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: LOF_050:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# WhenDefeated_Declined
#// LOF_095 Lor San Tekka — the Experience grant is a "may": the controller can decline. Lor attacks the
#// enemy 4/7 and dies, then P1 declines; the friendly unique Plo Koon receives no Experience token.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_095:1:0
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
