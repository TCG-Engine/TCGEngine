# StarhawkHalvingAppliesToDroidPayment
#// SHARED PAYMENT MECHANIC — JTL_105 The Starhawk × SEC_122 Vuutun Palaa.
#//
#// CR step 3 DETERMINES the modified cost; step 4 PAYS it. Starhawk modifies the PAYMENT ("While paying
#// costs, you pay half as many resources, rounded up"), and Vuutun's Droids are exhausted "as if it were
#// a resource" — so a Droid is one of the resources you pay, and the halving applies to the total being
#// paid, not to whatever is left after some of it has already been paid.
#//
#// ⚠ REGRESSION GUARD (bug found 2026-09-02, user-ruled). SWUPayCost subtracted the alt-payment FIRST
#// and halved the REMAINDER:  resCost = halve(cost - prepaid).  That spends Droids and Credits at full
#// face value while only real resources get the discount — which made spending one strictly WORSE:
#// a cost-4 card under Starhawk is 2 resources if you pay normally, but 1 Droid + 2 resources = 3 if you
#// spend a Droid first. The correct order is halve(cost) - prepaid.
#// The same wrong order also sized the OFFER: the picker was capped at the unhalved cost.
#//
#// ASH_083 (cost 12, [Vigilance], covered by the Kashyyyk base) is played as the third unit of the round
#// with three Shyyyos out: 12 - 9 = 3 determined, halved to 2 paid. With ZERO ready resources the two
#// Droids must cover it exactly. Under the old order (3 - 2 = 1, halved to 1) one resource was still
#// owed, the play was refused, and the Droids were spent anyway.

## GIVEN
CommonSetup: bgw/bgw/{myResources:0;myBase:HMW_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IBH_008
WithP1Hand: IBH_008
WithP1Hand: ASH_083
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0
WithP1SpaceArena: JTL_105:1:0
WithP1SpaceArena: SEC_122:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1SPACEARENACOUNT:3
P1SPACEARENAUNIT:2:CARDID:ASH_083
P1HANDCOUNT:0
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:2:READY
P1GROUNDARENAUNIT:3:READY
P1GROUNDARENAUNIT:4:READY

---

# UnderpayingWithDroidsSpendsNothing
#// SHARED PAYMENT MECHANIC — CR step 4.a: "If any costs (including resource costs and additional costs)
#// cannot be paid, cease this process without paying any costs. Return the game state to the way it was
#// before the first step."
#//
#// ⚠ REGRESSION GUARD (bug found 2026-09-02). DROID_PAY exhausted the chosen Droids and THEN dispatched
#// the play; when the remainder could not be covered the play was refused but the Droids stayed spent.
#// A player could lose Droids for nothing, repeatedly. Measured with AND without Starhawk, so it is the
#// general alt-payment path — and Credit tokens ride the same funnel, which is a far commoner board.
#//
#// No Starhawk here on purpose: this is about the rollback, not the halving. ASH_083 is the third unit
#// of the round with three Shyyyos out, so it costs 12 - 9 = 3, and the player has ZERO resources.
#// Answering with only TWO Droids cannot cover 3, so the play must be refused AND all five Droids must
#// still be READY. The old behaviour left two exhausted.

## GIVEN
CommonSetup: bgw/bgw/{myResources:0;myBase:HMW_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IBH_008
WithP1Hand: IBH_008
WithP1Hand: ASH_083
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0
WithP1GroundArena: HMW_145:1:0
WithP1SpaceArena: SEC_122:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1HANDCOUNT:1
P1SPACEARENACOUNT:1
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:2:READY
P1GROUNDARENAUNIT:3:READY
P1GROUNDARENAUNIT:4:READY
