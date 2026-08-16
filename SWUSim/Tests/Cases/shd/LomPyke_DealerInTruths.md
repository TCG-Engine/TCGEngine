# OnAttack_Decline_NoShields
#// SHD_032 Lom Pyke — declining the "may" gives NO shields at all (the friendly shield is gated on
#// "if you do").
#// COVERAGE: offer=N/A (the enemy pick is answered directly out of the enemy ground arena; the friendly
#//           half auto-resolves onto the single friendly unit) · decline=OnAttack_Decline_NoShields ·
#//           control=N/A (Shield tokens are handed to units in place; no controller change) ·
#//           boundary=OnAttack_ShieldEnemyThenFriendly (an enemy unit exists → both halves land) vs
#//           OnAttack_NoEnemyUnit_AutoPasses (no enemy unit → the ability self-resolves with no
#//           decision and neither half lands) · reqboundary=OnAttack_ShieldEnemyThenFriendly (the
#//           friendly half is only reachable after the enemy pick is answered — "if you do")

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

---

# OnAttack_NoEnemyUnit_AutoPasses
#// SHD_032 Lom Pyke — the whole ability is gated on "give a Shield token to an enemy unit", so with no
#// enemy unit in play there is nothing to offer: the On Attack self-resolves with NO decision and the
#// friendly half never happens either (neither Lom Pyke nor the other friendly gets a Shield). The base
#// still takes Lom Pyke's 4.

## GIVEN
CommonSetup: bbk/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_032:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1NODECISION
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
