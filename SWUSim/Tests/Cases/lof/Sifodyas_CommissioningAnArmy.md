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
#// in the deck (not discarded, not free-playable), and only Sifo-Dyas is in the discard. Intended: "player choose
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

---

# WhenDefeated_UnderEnemyControl_OpponentSearchesTheirOwnDeck
#// "Search the top 8 cards of YOUR deck" resolves for whoever CONTROLS Sifo-Dyas at the moment of defeat.
#// P2 plays JTL_043 (No Glory, Only Results) to take control of P1's Sifo-Dyas and defeat it, so P2 — not
#// P1 — searches P2's OWN deck, discards the Clone into P2's discard, and plays it for free on P2's side.
#// (Sifo-Dyas is P1's only non-leader unit, so NGOR's single-target choice auto-resolves with no prompt.)
## GIVEN
CommonSetup: ggw/bbk/{theirResources:6}
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: LOF_117:1:0
WithP2Hand: JTL_043
WithP2Deck: [TWI_240 SOR_046 SOR_046 SOR_046 SOR_046 SOR_046 SOR_046 SOR_046]
WithP1Deck: SHD_198
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:TWI_240
- P2>PlayFromDiscard:1
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:TWI_240
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1

---

# WhenDefeated_PreexistingDiscardClone_NotFreePlayable
#// Scope exclusion: only the Clones THIS search discards become free-playable. A Clone already sitting in
#// the discard (defeated earlier) is NOT retroactively marked, so it cannot be replayed for free.
#// P1 starts with TWI_240 (Clone, cost 1) already in the discard and 0 resources. Sifo-Dyas dies and finds
#// SHD_198 (Clone, cost 2) in the deck → only SHD_198 is playable for free. Attempting to play the
#// pre-existing TWI_240 (discard index 0) does nothing; playing the freshly-found SHD_198 works.
## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: LOF_117:1:0
WithP2GroundArena: SOR_039:1:0
WithP1Discard: TWI_240
WithP1Deck: SHD_198
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SHD_198
- P1>PlayFromDiscard:0
## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:3

---

# WhenDefeated_FreshlyFoundClone_IsFreePlayable_ControlSection
#// Control for the section above, same board: playing the FRESHLY-FOUND SHD_198 (discard index 2 — behind
#// the pre-existing TWI_240 and Sifo-Dyas himself) does resolve for free at 0 resources.
## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: LOF_117:1:0
WithP2GroundArena: SOR_039:1:0
WithP1Discard: TWI_240
WithP1Deck: SHD_198
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SHD_198
- P1>PlayFromDiscard:2
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_198
P1DISCARDCOUNT:2
