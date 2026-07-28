# DiscountOnUnique
#// LAW_129 Mastery (Upgrade, +3/+3, cost 4, Vigilance) — "This upgrade costs 1 resource less to play on
#// a UNIQUE unit." Played onto SOR_181 (unique) with only 3 resources → the discount (cost 4 → 3) makes
#// it affordable, it attaches, and all 3 resources are spent.

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_181:1:0
WithP1Hand: LAW_129

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:LAW_129
P1RESAVAILABLE:0

---

# NoDiscountOnNonUnique
#// LAW_129 Mastery — guard: NO discount on a NON-unique host. With only 3 resources and a non-unique
#// host (SEC_080), Mastery costs its full 4 → unaffordable, so the play is rejected and it stays in
#// hand (proves the discount is host-conditional on uniqueness, not always-on).

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_129

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# StacksWithOtherCostReduction
#// LAW_129 Mastery — the unique discount stacks with another cost-reduction effect. Fenn Rau (SHD_067)
#// "When Played: you may play an upgrade from your hand; it costs 2 less" is used to play Mastery onto the
#// friendly unique SOR_236 (R2-D2): 4 - 2 (Fenn Rau) - 1 (unique host) = 1. With 7 resources, Fenn Rau (6)
#// + Mastery (1) spends all 7.

## GIVEN
CommonSetup: bbw/rrk/{myResources:7}
P1OnlyActions: true
WithP1GroundArena: SOR_236:1:0
WithP1Hand: SHD_067
WithP1Hand: LAW_129

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_236
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:LAW_129
P1RESAVAILABLE:0

---

# WorksOnEnemyUniqueUnit
#// LAW_129 Mastery — has no printed "friendly" attach restriction, so it may attach to an ENEMY unique unit
#// (and still gets its own -1 unique discount). P1 plays Mastery onto the enemy SOR_236 (R2-D2): cost 4-1=3.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP2GroundArena: SOR_236:1:0
WithP1Hand: LAW_129

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_236
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1RESAVAILABLE:1
