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
#// LAW_058 Honor-Bound Partisan (2/2) — When Played: deal 1 damage to a base (choose either). Choosing the
#// enemy base deals 1 to P2's base.

## GIVEN
CommonSetup: grk/bgw/{myResources:2}
WithP1Hand: LAW_058

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:1
P1BASEDMG:0

---

# WhenPlayed_ChooseOwnBase
#// LAW_058 Honor-Bound Partisan — "a base" has no "enemy" qualifier, so the player MAY choose their OWN
#// base. Choosing P1's base deals 1 to it (P2's base untouched).

## GIVEN
CommonSetup: grk/bgw/{myResources:2}
WithP1Hand: LAW_058

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:1
P2BASEDMG:0

---

# WhenDefeatedNoDiscountForNonUnit
#// LAW_058 Honor-Bound Partisan — When Defeated: only the next UNIT played is discounted; a non-unit (event)
#// is NOT discounted. Partisan attacks SOR_046 (3/7) and dies; then Daring Raid (SHD_178, cost 1) is played
#// at full cost (myResources:1 -> 0, not free), dealing 2 to P2's base.

## GIVEN
CommonSetup: grk/bgw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: LAW_058:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SHD_178

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1RESAVAILABLE:0
P2BASEDMG:2
