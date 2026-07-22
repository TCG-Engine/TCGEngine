# OnAttack_MoveUpgrade
#// JTL_056 Hondo Ohnaka — Shielded + "On Attack: You may take control of a non-Pilot upgrade on a unit
#// and attach it to a different eligible unit." Hondo attacks the base; on attack he takes SOR_120
#// (Academy Training, +2/+2) off the enemy SOR_046 and reattaches it to the friendly SOR_095.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: JTL_056:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SOR_120
P2BASEDMG:3

---

# Shielded_EntersWithShield
#// JTL_056 Hondo Ohnaka has Shielded — when he enters play he gains a Shield token. Played from hand (cost
#// 4, mono-Vigilance), Hondo enters P1's ground arena with SHIELDCOUNT 1.

## GIVEN
CommonSetup: bbk/rrk/{myResources:5;handCardIds:JTL_056}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_056
P1GROUNDARENAUNIT:0:HASKEYWORD:Shielded
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# OnAttack_DeclineMove
#// JTL_056 Hondo Ohnaka — the On-Attack upgrade move is a MAY. Hondo attacks the base and P1 declines
#// (Pass): the enemy SOR_046 keeps SOR_120 and no upgrade is relocated.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: JTL_056:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:PASS

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_120
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P2BASEDMG:3

---

# OnAttack_NonPilotUpgradeOnly
#// JTL_056 Hondo Ohnaka — the move applies only to a NON-Pilot upgrade. The only upgrade in play is a Pilot
#// (JTL_046 on the enemy SOR_046), so Hondo's On-Attack finds no eligible upgrade and offers no move; the
#// pilot stays put.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: JTL_056:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaPilot: 0:JTL_046

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2BASEDMG:3
