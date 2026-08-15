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

---

# OnAttack_TakeUpgradeWithFriendlyAttachCondition
#// JTL_056 Hondo Ohnaka — the moved upgrade can itself have a "friendly unit" attach condition. The enemy
#// SOR_207 Crafty Smuggler carries LOF_091 Craving Power (+2/+2, "Attach to a friendly unit") and SOR_072
#// Entrenched. Hondo attacks the base and takes Craving Power (temp idx 0); it re-attaches to the friendly
#// (P1) Hondo, leaving Entrenched behind on the Crafty Smuggler. Moving the upgrade does NOT re-fire its
#// When Played, but its +2/+2 rides along: the base takes 5 (Hondo 3 power + 2). The other enemy unit
#// (LOF_061 Secretive Sage) is present so the destination is a real pick.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: JTL_056:1:0
WithP2GroundArena: LOF_061:1:0
WithP2GroundArena: SOR_207:1:0
WithP2GroundArenaUpgrade: 1:LOF_091
WithP2GroundArenaUpgrade: 1:SOR_072

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_056
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:LOF_091
P2GROUNDARENAUNIT:1:CARDID:SOR_207
P2GROUNDARENAUNIT:1:UPGRADECOUNT:1
P2GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SOR_072
P2BASEDMG:5

---

# OnAttack_MoveTokenUpgradeGainControl
#// JTL_056 Hondo Ohnaka — the move works on a TOKEN upgrade, and taking it grants control (and, for a
#// token, ownership) to Hondo's controller. The enemy LOF_061 Secretive Sage holds an Experience token
#// (SOR_T01). Hondo attacks the base, takes the token, and — Hondo being the only other unit — auto-attaches
#// it to himself. The Experience now sits on the friendly (P1) Hondo; the Secretive Sage has no upgrade.
#// The +1/+1 rides along before combat damage resolves, so the base takes 4 (Hondo 3 power + 1).

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: JTL_056:1:0
WithP2GroundArena: LOF_061:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_056
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_T01
P2GROUNDARENAUNIT:0:CARDID:LOF_061
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2BASEDMG:4

---

# OnAttack_CannotMoveToIneligibleUnit
#// JTL_056 Hondo Ohnaka — "attach it to a DIFFERENT ELIGIBLE unit" is gated on the moved upgrade's OWN
#// printed attach restriction. The enemy LOF_061 Secretive Sage carries LOF_261 Constructed Lightsaber
#// ("Attach to a Force unit"). The only other unit in play is Hondo, who is NOT a Force unit. P1 takes the
#// lightsaber, but it has no eligible destination — the non-Force Hondo is rejected — so the move fizzles
#// and the lightsaber stays on the Secretive Sage (it is NOT illegally attached to Hondo). Nothing is left
#// pending afterward.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: JTL_056:1:0
WithP2GroundArena: LOF_061:1:0
WithP2GroundArenaUpgrade: 0:LOF_261

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myTempZone-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:LOF_061
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:LOF_261
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# Offer_UpgradePoolIsEveryNonPilotUpgradeEitherSide
#// JTL_056 Hondo Ohnaka — "take control of a NON-PILOT upgrade ON A UNIT". Two axes are enforced at once:
#// the upgrade must not be a Pilot, and the unit carrying it may belong to EITHER player (the card says
#// "a unit", not "an enemy unit"). Board: the friendly SOR_095 carries SOR_120 Academy Training, and the
#// enemy SOR_046 carries both SOR_072 Entrenched (non-Pilot) and the Pilot JTL_046 Paige Tico. The offer
#// must be exactly the two non-Pilot upgrades — the friendly one included, the Pilot excluded.
#// The decision is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: JTL_056:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_120
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaPilot: 0:JTL_046
WithP2GroundArenaUpgrade: 0:SOR_072

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Take_control_of_a_non-Pilot_upgrade_to_move_it
P1SELECTABLEEXACT:myTempZone-0&myTempZone-1
