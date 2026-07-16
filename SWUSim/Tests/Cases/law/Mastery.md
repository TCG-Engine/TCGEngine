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
