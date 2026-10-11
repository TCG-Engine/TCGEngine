# SkipPreGame_P1DeckIsTheLiteralDeck
#// Harness contract (SchemaTestRunner::_buildInitialState docblock): under SkipPreGame — which every
#// CommonSetup board implies — `P1Deck:` IS the literal current deck, top card first. It used to build
#// count(list) copies of the FIRST card, so a mixed list silently became a one-card deck (found via
#// twi/Foresight.md, whose "Marines below" were really more Consular Security Forces).
#// Three distinct cards; the regroup draws two, so the THIRD listed card must be left on top.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
P1Deck: [SOR_046 SOR_095 SOR_128]
P2Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_128
