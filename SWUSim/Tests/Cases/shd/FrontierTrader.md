# WhenPlayed_Decline
#// SHD_214 — the "you may" is optional. Declining the resource return skips the whole chain: no resource
#// returned, no top-card ramp. Resources stay at 4, deck keeps its card, nothing added to hand.

## GIVEN
CommonSetup: yyw/yyw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_214
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1RESCOUNT:4
P1HANDCOUNT:0
P1DECKCOUNT:1

---

# WhenPlayed_ReturnResource_RampTop
#// SHD_214 (3-cost 2/2 Cunning) — "When Played: You may return a resource you control to its owner's
#// hand. If you do, you may put the top card of your deck into play as a resource." P1 takes both: a
#// resource is returned to hand, then the top card (SOR_095) enters as an (exhausted) resource. Net
#// resource count unchanged (4 → 3 → 4); the returned card is now in hand; deck emptied.

## GIVEN
CommonSetup: yyw/yyw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_214
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_214
P1RESCOUNT:4
P1HANDCOUNT:1
P1DECKCOUNT:0
