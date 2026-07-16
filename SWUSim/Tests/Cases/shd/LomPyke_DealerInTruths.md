# OnAttack_Decline_NoShields
#// SHD_032 Lom Pyke — declining the "may" gives NO shields at all (the friendly shield is gated on
#// "if you do").

## GIVEN
CommonSetup: bbk/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_032:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# OnAttack_ShieldEnemyThenFriendly
#// SHD_032 Lom Pyke (4/6) — "On Attack: You may give a Shield token to an enemy unit. If you do,
#// give a Shield token to a friendly unit." Base attack; P1 shields the enemy marine, then the sole
#// friendly (Lom Pyke himself, single target → auto) gets one too.

## GIVEN
CommonSetup: bbk/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_032:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
