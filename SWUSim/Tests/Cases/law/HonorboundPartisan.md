# WhenDefeatedDiscount
#// LAW_058 Honor-Bound Partisan — When Defeated: the next unit you play this phase costs 1 less. Partisan
#// attacks SOR_046 (3/7) and dies; then SEC_080 (cost 2) plays for 1 (1 ready -> 0).

## GIVEN
CommonSetup: grk/bgw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: LAW_058:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_080

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1RESAVAILABLE:0

---

# WhenPlayedDealBase
#// LAW_058 Honor-Bound Partisan (2/2) — When Played: deal 1 damage to a base. Deals 1 to P2's base.

## GIVEN
CommonSetup: grk/bgw/{myResources:2}
WithP1Hand: LAW_058

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:1
