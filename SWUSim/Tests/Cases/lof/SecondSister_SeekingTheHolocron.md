# MillForceReadyResources
#// LOF_184 Second Sister — On Attack: may discard 2 cards from your deck. For each Force card discarded,
#// ready a resource. The top 2 are both Force units, so P1 readies 2 (previously exhausted) resources.

## GIVEN
CommonSetup: yyk/rrw
P1OnlyActions: true
WithP1Resources: 2:SOR_095:0
WithP1GroundArena: LOF_184:1:0
WithP1Deck: LOF_050
WithP1Deck: LOF_193

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1RESAVAILABLE:2
P1DECKCOUNT:0

---

# SkipAbility
#// LOF_184 Second Sister — the On-Attack ability is a "may". P1 declines: no cards are discarded and no
#// resource is readied. The three exhausted resources stay exhausted and the 2-card deck is untouched.

## GIVEN
CommonSetup: yyk/rrw
P1OnlyActions: true
WithP1Resources: 3:SOR_095:0
WithP1GroundArena: LOF_184:1:0
WithP1Deck: LOF_050
WithP1Deck: LOF_193

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P1RESAVAILABLE:0
P1DECKCOUNT:2

---

# OneForceCardReadiesOne
#// LOF_184 Second Sister — discards 2 from deck; readies a resource only for each FORCE card discarded. Deck is
#// Plo Koon (LOF_050, Force) + Battlefield Marine (SOR_095, non-Force). Both are discarded but only 1 is a
#// Force card, so exactly 1 of the 3 exhausted resources is readied.

## GIVEN
CommonSetup: yyk/rrw
P1OnlyActions: true
WithP1Resources: 3:SOR_095:0
WithP1GroundArena: LOF_184:1:0
WithP1Deck: LOF_050
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1RESAVAILABLE:1
P1DECKCOUNT:0

---

# LastCardDiscardsOnlyOne
#// LOF_184 Second Sister — with only 1 card in the deck, the ability discards just that card (a Force card) and
#// readies 1 resource. Intended: "discard the last card from the deck and ready 1 resource".

## GIVEN
CommonSetup: yyk/rrw
P1OnlyActions: true
WithP1Resources: 3:SOR_095:0
WithP1GroundArena: LOF_184:1:0
WithP1Deck: LOF_050

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1RESAVAILABLE:1
P1DECKCOUNT:0

---

# EmptyDeckReadiesNone
#// LOF_184 Second Sister — with an empty deck, triggering the ability discards nothing and readies no resource.
#// The three exhausted resources stay exhausted. Intended: "should not ready any resource if deck is
#// empty".

## GIVEN
CommonSetup: yyk/rrw
P1OnlyActions: true
WithP1Resources: 3:SOR_095:0
WithP1GroundArena: LOF_184:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1RESAVAILABLE:0
P1DECKCOUNT:0
