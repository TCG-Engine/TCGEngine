# WhenDefeatedDiscount
#// LAW_058 Honor-Bound Partisan — When Defeated: the next unit you play this phase costs 1 less. Partisan
#// attacks SOR_046 (3/7) and dies; then SEC_080 (cost 2) plays for 1 (1 ready -> 0).
#// COVERAGE: offer=both branches of the When-Played base pool are exercised (WhenPlayedDealBase enemy
#//           pick, WhenPlayed_ChooseOwnBase own pick); no pending SELECTABLE section ·
#//           reqboundary=WhenDefeatedDiscount + NGORDefeat_DiscountGoesToTheNewController (the discount
#//           registers at defeat and is consumed on a LATER action, surviving intervening requests) ·
#//           control=NGORDefeat_DiscountGoesToTheNewController ·
#//           boundary=WhenDefeatedNoDiscountForNonUnit (event NOT discounted) vs WhenDefeatedDiscount
#//           (unit discounted) · decline=N/A (no "may" on either ability; the base pick is a mandatory
#//           2-option choice)

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

---

# NGORDefeat_DiscountGoesToTheNewController
#// LAW_058 Honor-Bound Partisan — "the next unit YOU play this phase costs 1 less" belongs to whoever
#// controls the Partisan when it is defeated. P2 plays No Glory, Only Results (JTL_043, cost 5) to take
#// control of P1's Partisan and defeat it, so the discount registers for P2, not for P1 the owner:
#// P1 then plays SEC_080 (cost 2) at FULL price (2 -> 0), while P2's SEC_080 costs 1 (8 - 5 - 1 -> 2).

## GIVEN
CommonSetup: grk/bgk/{myResources:2}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 8
WithP2Hand: [JTL_043 SEC_080]
WithP1Hand: SEC_080
WithP1GroundArena: LAW_058:1:0

## WHEN
- P2>PlayHand:0
- P1>PlayHand:0
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P1RESAVAILABLE:0
P2RESAVAILABLE:2

---

# WhenDefeatedDiscount_SurvivesTheRequestBoundary
#// LAW_058 Honor-Bound Partisan — "the next unit you play this phase costs 1 less" is registered when the
#// Partisan dies and consumed by a LATER action, with unrelated requests in between; in production each of
#// those is a fresh process, so the charge must live in the serialized gamestate.
#// Extends WhenDefeatedDiscount with an intervening interactive decision so the boundary lands on a REAL
#// pending choose: the Partisan attacks SOR_046 (3/7) and dies (discount registered), P1 then plays Daring
#// Raid (SHD_178, cost 1) whose "deal 2 to a unit or base" pick is a genuine 3-candidate MZCHOOSE
#// (theirGroundArena-0 & myBase-0 & theirBase-0). A request boundary is inserted before that answer.
#// Intended: the event is NOT discounted (per WhenDefeatedNoDiscountForNonUnit) and does NOT consume the
#// charge, so SEC_080 (cost 2) still plays for 1 afterwards: 3 - 1 (Raid) - 1 (discounted SEC_080) = 1.

## GIVEN
CommonSetup: grk/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: LAW_058:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: [SHD_178 SEC_080]

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirBase-0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1RESAVAILABLE:1
P2BASEDMG:2
