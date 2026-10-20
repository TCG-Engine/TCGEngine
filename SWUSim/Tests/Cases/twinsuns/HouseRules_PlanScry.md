#// TWIN SUNS HOUSE RULE — Plan Scry (docs/superpowers/specs/2026-10-18-twinsuns-house-rules-design.md).
#// Taking Plan normally draws 1, then you may bottom 1 card from hand. With the rule it instead looks at the top N
#// cards of your deck (N = the room's value, default 2) and puts each on the top or bottom: a SCRY. Nothing is drawn —
#// no card is kept, no "when you draw" trigger, no empty-deck draw damage. The turn stays with the plan taker until the
#// scry is answered (the same deferred SWU_PLAN_PASS as the normal Plan), then passes exactly one seat.
# Scry2_LooksAtTheTopTwo_DrawsNothing
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithHouseRules: planScry=2
WithP1Hand: SOR_251
WithP1Deck: [SOR_095 SOR_128 SOR_095]
## WHEN
- P1>TakeCounter:plan
## EXPECT
PLANCOUNTER:P1
P1DECISIONTYPE:SCRY
TURNPLAYER:1
P1HANDCOUNT:1
P1DECKCOUNT:3

---

# Scry2_AnsweringPutsCardsBack_AndPassesTheTurn
#// SOR_128 stays on top, SOR_095 (the first card) goes to the bottom. Hand and deck sizes are unchanged.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithHouseRules: planScry=2
WithP1Hand: SOR_251
WithP1Deck: [SOR_095 SOR_128 SOR_095]
## WHEN
- P1>TakeCounter:plan
- P1>AnswerDecision:SOR_128|SOR_095
## EXPECT
P1DECKTOPCARD:SOR_128
P1DECKCOUNT:3
P1HANDCOUNT:1
TURNPLAYER:2
P1NODECISION
LOGCONTAINS:took the plan counter (scry 2)

---

# Scry3_UsesTheRoomsValue
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithHouseRules: planScry=3
WithP1Deck: [SOR_095 SOR_128 SOR_095 SOR_128]
## WHEN
- P1>TakeCounter:plan
- P1>AnswerDecision:|SOR_095,SOR_128,SOR_095
## EXPECT
P1DECKTOPCARD:SOR_128
P1DECKCOUNT:4
TURNPLAYER:2

---

# ShortDeck_ScriesWhatIsThere
#// One card left, Scry 2: the scry offers that one card, and the turn passes after it.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithHouseRules: planScry=2
WithP1Deck: [SOR_128]
## WHEN
- P1>TakeCounter:plan
- P1>AnswerDecision:SOR_128|
## EXPECT
P1DECKTOPCARD:SOR_128
P1DECKCOUNT:1
TURNPLAYER:2

---

# EmptyDeck_NothingHappens_AndTheTurnPasses
#// Nothing to look at: no decision, no draw, so no empty-deck damage — and the Plan still ends the round.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithHouseRules: planScry=2
## WHEN
- P1>TakeCounter:plan
## EXPECT
PLANCOUNTER:P1
P1NODECISION
TURNPLAYER:2
P1BASEDMG:0
