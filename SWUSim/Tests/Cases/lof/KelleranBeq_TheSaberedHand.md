# SearchPlayDiscounted
#// LOF_100 Kelleran Beq — When Played: search the top 7 for a unit, reveal it, and play it costing 3 less.
#// The deck is all SOR_095 (cost 3 → 0 after −3), so P1 plays one for free; Kelleran + the searched unit
#// are both in play, and 6 cards remain in the deck.

## GIVEN
CommonSetup: ggw/rrk/{myResources:7;handCardIds:LOF_100}
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1DECKCOUNT:6

---

# SearchPool_ExcludesUnaffordable
#// LOF_100 Kelleran Beq — "search the top 7 for a unit, reveal it, and play it. It costs 3 resources
#// less." BUG (live game): the search offered units the player couldn't afford even after the −3 discount,
#// so picking one just fizzled (the resolve handler silently returns it to the deck). The offered/playable
#// pool must exclude units the player can't pay for.
#//
#// P1 has 7 resources; Kelleran costs 7 (Command/Heroism, fully covered by Leia + green base → no penalty),
#// so after playing him 0 ready resources remain. The top of the deck holds:
#//   - SOR_095 Battlefield Marine — cost 2 → max(0, 2−3) = 0 net → affordable (0 ≤ 0), MUST be offered.
#//   - SOR_119 Reinforcement Walker — cost 8 (Command, covered) → max(0, 8−3) = 5 net → UNaffordable
#//     (5 > 0), must NOT be offered.
#//
#// The TOPDECKSEARCH decision is left pending so its playable set (matchIDs) can be asserted directly —
#// the harness's answer path doesn't enforce that set, so only inspecting the offer catches the bug.

## GIVEN
CommonSetup: ggw/rrk/{myResources:7;handCardIds:LOF_100}
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1Deck: SOR_119

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SEARCHPLAYABLEHAS:SOR_095
P1SEARCHPLAYABLENOT:SOR_119
