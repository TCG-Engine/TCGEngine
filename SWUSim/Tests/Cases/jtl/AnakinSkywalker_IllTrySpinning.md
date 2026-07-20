# CompletesAttack_Decline
#// JTL_197 Anakin Skywalker — the return is optional. Declining (AnswerDecision:NO) leaves Anakin
#// attached to SOR_095.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: JTL_068:1:0
WithP1GroundArenaUpgrade: 0:JTL_197

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1HANDCOUNT:0

---

# CompletesAttack_ReturnToHand
#// JTL_197 Anakin Skywalker — Piloting + "When attached unit completes an attack (and survives): You may
#// return this upgrade to its owner's hand." JTL_068 (3/5 Vehicle) carries Anakin (+2/+3 pilot → 5 power),
#// attacks the P2 base for 5, survives, then P1 returns Anakin to hand.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: JTL_068:1:0
WithP1GroundArenaUpgrade: 0:JTL_197

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1HANDCOUNT:1

---

# AsUnit_NoReturnAbility
#// JTL_197 Anakin Skywalker — the "return this upgrade" trigger belongs to the PILOT upgrade. Played/seated
#// as a UNIT, Anakin (2/3) attacks the base and there is no return offer (no decision), and he stays in play.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: JTL_197:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:CARDID:JTL_197
P2BASEDMG:2
