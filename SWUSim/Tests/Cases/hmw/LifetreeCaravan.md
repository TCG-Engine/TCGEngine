# ThreeUnitsIncludingItself_MayResourceTopOfDeck
#// HMW_136 Lifetree Caravan (2/1, Ewok, cost 3) — "When Played: If you control 3 or more units
#// (including this one), you may resource the top card of your deck."
#// P1 already controls 2 units, so the Caravan is the third and the condition holds. Accepting moves the
#// deck's top card into the resource row: deck 3 -> 2, resources +1.
#// It enters EXHAUSTED — the text has no "and ready it" rider — so RESAVAILABLE is unchanged from the
#// 3 left ready after paying the Caravan's own cost of 3.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 LOF_107:1:0]
WithP1Hand: HMW_136
WithP1Deck: [SOR_046 SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:3
P1DECKCOUNT:2
P1RESCOUNT:7
P1RESAVAILABLE:3

---

# Decline_NothingResourced
#// "You MAY resource" — declining leaves the deck and the resource row untouched.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 LOF_107:1:0]
WithP1Hand: HMW_136
WithP1Deck: [SOR_046 SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:3
P1DECKCOUNT:3
P1RESCOUNT:6

---

# OnlyTwoUnitsIncludingItself_NoOffer
#// The boundary's failing side: with just ONE other unit the Caravan is only the second, so the
#// condition fails and no offer is raised at all.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: HMW_136
WithP1Deck: [SOR_046 SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:2
P1DECKCOUNT:3
P1RESCOUNT:6

---

# CountIncludesTheCaravanItself
#// "(including this one)" is load-bearing: with exactly 2 OTHER units the total is 3 and it fires — the
#// section above proves 1 other (total 2) does not. Together they pin the count as inclusive, not
#// "3 others".

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 LOF_107:1:0]
WithP1Hand: HMW_136
WithP1Deck: [SOR_046 SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Resource_the_top_card_of_your_deck?

---

# UnitsInEITHERArenaCount
#// "units you control" is not arena-restricted — a friendly SPACE unit counts toward the 3.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1Hand: HMW_136
WithP1Deck: [SOR_046 SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1DECKCOUNT:2
P1RESCOUNT:7

---

# EmptyDeck_CleanNoOp
#// Nothing to resource: the condition still holds (3 units), but an empty deck makes the effect a clean
#// no-op rather than a crash or a phantom resource — and NO prompt is raised at all, since an
#// offer the player could only waste is the SEC_186/SEC_210 'skip the pointless offer' rule.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 LOF_107:1:0]
WithP1Hand: HMW_136

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:3
P1DECKCOUNT:0
P1RESCOUNT:6
