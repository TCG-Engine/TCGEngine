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

---

# NoTriggerIfHostDies
#// JTL_197 Anakin Skywalker — the return offer requires the piloted host to SURVIVE the attack. JTL_068
#// (3/5) carries Anakin (+2/+3 → 8 HP) but is pre-damaged to 6, leaving 2 HP; it attacks SOR_046 (3 power)
#// and dies to the 3 counter damage. Anakin is defeated with his host — no return decision is offered, and
#// he goes to the discard rather than back to hand.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: JTL_068:1:6
WithP1GroundArenaUpgrade: 0:JTL_197
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0

---

# NoTriggerIfDifferentUnitAttacks
#// JTL_197 Anakin Skywalker — the return trigger is host-specific: it only fires when the unit Anakin
#// pilots completes an attack. Anakin pilots the ground JTL_068, but a DIFFERENT unit (the space JTL_033)
#// makes the attack — no return decision is offered and Anakin stays attached to his host.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: JTL_068:1:0
WithP1GroundArenaUpgrade: 0:JTL_197
WithP1SpaceArena: JTL_033:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:CARDID:JTL_068
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2BASEDMG:2
