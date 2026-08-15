# XWingAndShield
#// JTL_076 Covering the Wing (event) — Create an X-Wing token. You may give a Shield to another unit. P1
#// creates an X-Wing (JTL_T02) and shields SOR_095 (not the X-Wing).

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_076
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_T02
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# MoffJerjerrodDoublesXWings
#// JTL_076 Covering the Wing + ASH_094 Moff Jerjerrod — Moff doubles token creation (defeating himself):
#// Covering the Wing makes 2 X-Wing tokens instead of 1, then still lets P1 shield ANOTHER unit (not the
#// freshly-created X-Wings). P1 accepts the double, then shields SOR_095. Moff goes to the discard.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_076
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: ASH_094:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:JTL_T02
P1SPACEARENAUNIT:1:CARDID:JTL_T02
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# Offer_ShieldPoolIsAnyUnitExceptTheNewXWing
#// JTL_076 Covering the Wing — "Create an X-Wing token. You may give a Shield token to ANOTHER unit."
#// "Another" is relative to the freshly created X-Wing, and the shield is NOT restricted to friendly
#// units. The pool must therefore be the friendly SOR_095 AND the enemy SOR_046, while excluding the
#// just-created X-Wing at mySpaceArena-0. The decision is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_076
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_T02
