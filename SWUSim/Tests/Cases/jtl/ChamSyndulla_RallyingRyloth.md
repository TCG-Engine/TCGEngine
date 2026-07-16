# WhenPlayed_OpponentControlsMore_RampsResource
#// JTL_164 Cham Syndulla, Rallying Ryloth — When Played: If an opponent controls MORE resources than you,
#// you may put the top card of your deck into play as a resource. P1 (4 resources) plays Cham while P2
#// controls 5 → condition met → YES → top of deck (SOR_095) enters as a resource. P1 now controls 5, deck empty.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP2Resources: 5
WithP1Hand: JTL_164
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_164
P1RESCOUNT:5
P1DECKCOUNT:0

---

# WhenPlayed_EqualResources_NoRamp
#// JTL_164 Cham Syndulla — the negative case: if the opponent does NOT control more resources than you,
#// the ability does nothing (no YES/NO prompt). P1 (5 resources) plays Cham while P2 also controls 5 →
#// 5 is not more than 5 → no trigger → top of deck (SOR_095) stays in the deck, resource count unchanged.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP2Resources: 5
WithP1Hand: JTL_164
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_164
P1RESCOUNT:5
P1DECKCOUNT:1
P1NODECISION