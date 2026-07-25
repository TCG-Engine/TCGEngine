# NoForce_Draw1
#// LOF_175 Do or Do Not — without the Force you "do not" use it → draw 1 (no decision, automatic).

## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:LOF_175}
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1NODECISION

---

# UseForce_Draw2
#// LOF_175 Do or Do Not — "You may use the Force. If you do, draw 2. If you do not, draw 1." With the
#// Force, P1 uses it and draws 2.

## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:LOF_175}
P1OnlyActions: true
WithP1Force: true
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P1HANDCOUNT:2

---

# HasForce_Decline_Draw1
#// LOF_175 Do or Do Not — "You may use the Force. If you do, draw 2. If you do not, draw 1." P1 HAS the
#// Force but declines the offer → draws only 1 and the Force token is retained. Ref: "should draw 1 card if
#// the Force is not used".

## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:LOF_175}
P1OnlyActions: true
WithP1Force: true
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1HASFORCE
P1HANDCOUNT:1

---

# EmptyDeck_NoForce_BaseDamage3
#// LOF_175 Do or Do Not — with no Force the effect auto-draws 1 card; against an EMPTY deck CR 6.1 turns that
#// draw into 3 damage to P1's own base. Ref: "should damage the base if used with an empty deck without the
#// Force". P1's deck is left unseeded (empty).

## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:LOF_175}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1BASEDMG:3

---

# EmptyDeck_Force_BaseDamage6
#// LOF_175 Do or Do Not — using the Force draws 2; against an EMPTY deck both draws become 3 damage each →
#// 6 to P1's own base, and the Force token is spent. Ref: "should damage the base if used with an empty deck
#// with the Force".

## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:LOF_175}
P1OnlyActions: true
WithP1Force: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P1HANDCOUNT:0
P1BASEDMG:6
