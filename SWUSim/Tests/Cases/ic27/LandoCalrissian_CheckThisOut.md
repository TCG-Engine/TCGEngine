# DeclineTheResourcing_CardsStayInHand
#// TAKE/DECLINE: "up to 3" includes ZERO. The return still happened (mandatory), so resources drop to
#// 3 and the six returned/held cards all stay in hand. This is also the "exactly 3, not all 6" proof.
#// (A "fewer than 3 resources" case is unreachable: Lando costs 3, and paying EXHAUSTS resources
#// rather than removing them, so at least 3 always remain in the zone when his ability resolves.)

## GIVEN
CommonSetup: yyw/yyw/{myResources:6;myhandCardIds:IC27_167}
P1OnlyActions: true
WithP1Hand: [SOR_095 SOR_046 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0&myResources-1&myResources-2
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1RESCOUNT:3
P1HANDCOUNT:6

---

# ResourceFewerThanThree
#// QUANTITY: "up to 3" is a cap, not a requirement — resourcing exactly one is legal.

## GIVEN
CommonSetup: yyw/yyw/{myResources:6;myhandCardIds:IC27_167}
P1OnlyActions: true
WithP1Hand: [SOR_095 SOR_046 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0&myResources-1&myResources-2
- P1>AnswerDecision:myHand-0

## EXPECT
P1RESCOUNT:4
P1HANDCOUNT:5

---

# FullPath_ReturnsThreeThenResourcesThree_EnteringExhausted
#// THE FULL PATH, with a discriminating tell. Returning 3 and resourcing 3 leaves the resource COUNT
#// unchanged (6 -> 3 -> 6), so a count-only assertion would pass even unimplemented. RESAVAILABLE is
#// the real evidence: a card put into the resource zone by an effect enters EXHAUSTED (readied at the next ready step) —
#// the same rule as the regroup resource step. After paying 3 of 6 and returning the 3 still-ready
#// ones, every remaining resource is exhausted, so the three newly resourced cards must leave
#// RESAVAILABLE at 0.

## GIVEN
CommonSetup: yyw/yyw/{myResources:6;myhandCardIds:IC27_167}
P1OnlyActions: true
WithP1Hand: [SOR_095 SOR_046 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-3&myResources-4&myResources-5
- P1>AnswerDecision:myHand-0&myHand-1&myHand-2

## EXPECT
P1RESCOUNT:6
P1RESAVAILABLE:0

---

# EnemyOwnedResourceReturnsToItsOwnersHand
#// OWNERSHIP: "to their OWNER's hands" — a resource this player controls but the opponent OWNS goes
#// back to the OPPONENT's hand, not the controller's.

## GIVEN
CommonSetup: yyw/yyw/{myResources:3;myhandCardIds:IC27_167}
P1OnlyActions: true
WithP1ResourceControlled: SOR_095:2
WithP1Hand: [SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-3&myResources-0&myResources-1
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1RESCOUNT:1
P2HANDCOUNT:1
