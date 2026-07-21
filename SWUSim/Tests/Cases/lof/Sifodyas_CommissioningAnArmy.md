# WhenDefeatedCloneSearch
#// LOF_117 Sifo-Dyas — When Defeated: search the top 8 for any number of Clone units (combined cost ≤4),
#// discard them (marked free-playable this phase), rest to the bottom. Sifo-Dyas dies attacking SOR_039,
#// finds TWI_240 (Clone, cost 1), discards it with TPF, then P1 plays it from discard for free.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: LOF_117:1:0
WithP2GroundArena: SOR_039:1:0
WithP1Deck: TWI_240

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:TWI_240
- P1>PlayFromDiscard:1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_240

---

# WhenDefeated_TwoClones_CombinedCostWithinBudget
#// LOF_117 Sifo-Dyas — the search allows ANY NUMBER of Clone units with COMBINED cost ≤ 4 (not just one).
#// Sifo-Dyas dies attacking SOR_039; the top of deck holds TWI_240 (Clone, cost 1) + SHD_198 (Clone, cost 2)
#// = combined 3 (within the 4 budget). Both are selected, discarded free-playable-this-phase, and P1 plays
#// BOTH from the discard pile for free. (The single-Clone path is covered by WhenDefeatedCloneSearch above.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: LOF_117:1:0
WithP2GroundArena: SOR_039:1:0
WithP1Deck: [TWI_240 SHD_198 SOR_046 SOR_046 SOR_046]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:TWI_240,SHD_198
- P1>PlayFromDiscard:1
- P1>PlayFromDiscard:1

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_240
P1GROUNDARENAUNIT:1:CARDID:SHD_198

---

# WhenDefeated_DeclineSearch_TakeNothing
#// LOF_117 Sifo-Dyas — the When-Defeated search is optional. Sifo-Dyas dies attacking SOR_039 with a valid
#// Clone (TWI_240, cost 1) on top of the deck, but P1 declines the search ("take nothing"): the Clone stays
#// in the deck (not discarded, not free-playable), and only Sifo-Dyas is in the discard. Ref: "player choose
#// not to discard them".

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: LOF_117:1:0
WithP2GroundArena: SOR_039:1:0
WithP1Deck: TWI_240

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:PASS

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
