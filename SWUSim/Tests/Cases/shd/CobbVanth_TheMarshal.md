# WhenDefeated_NoCheapUnit_NoDiscard
#// SHD_115 — the search finds no unit costing 2 or less (deck top is only a Wampa, cost 4). The search
#// resolves with no valid pick (PASS): nothing is discarded from the deck. Only SHD_115 itself sits in
#// the discard, and it is not free-playable.

## GIVEN
CommonSetup: ggk/ggk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SHD_115:1:0
WithP1Deck: SOR_164
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_115
P1DECKCOUNT:3

---

# WhenDefeated_SearchDiscardFreePlay
#// SHD_115 (3-cost 3/2 Command) — "When Defeated: Search the top 10 cards of your deck for a unit that
#// costs 2 or less and discard it. For this phase, you may play that card from your discard pile for
#// free." P1's SHD_115 attacks a Wampa (SOR_164, 4/5): deals 3 (Wampa survives), counters 4 → SHD_115
#// (2 HP) dies. Its When Defeated searches → the ≤2 unit SOR_095 (cost 2) is discarded tagged free. P1
#// then plays SOR_095 from the discard for FREE (5 resources untouched).

## GIVEN
CommonSetup: ggk/ggk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SHD_115:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SOR_095
- P1>PlayFromDiscard:1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1RESAVAILABLE:5
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_115
