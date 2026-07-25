# SearchPlayReady
#// ASH_245 Eye of Sion (Space, 5/8) — Action [Exhaust]: search the top 8 cards of your deck for a unit
#// that costs the same as or less than this unit's power (5). Play it for free; it enters play ready. Eye
#// of Sion finds SEC_080 (cost 2) and plays it ready.
## GIVEN
CommonSetup: ggk/ggk
WithP1SpaceArena: ASH_245:1:0
WithP1Deck: SEC_080
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:SEC_080
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_245
P1SPACEARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:READY

---

# NoAffordableUnit_NothingPlayed
#// ASH_245 Eye of Sion — the search only finds a unit costing ≤ its power (5). With just SOR_038 (cost 7)
#// in the deck, there is no legal unit, so nothing is played.
## GIVEN
CommonSetup: ggk/ggk
WithP1SpaceArena: ASH_245:1:0
WithP1Deck: SOR_038
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:mySpaceArena-0
## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:0

---

# UsesCurrentPower_ForCostCheck
#// ASH_245 Eye of Sion — the cost check uses this unit's CURRENT power, not its printed 5. With an
#// Experience token (+1/+1) Eye is 6 power, so it can find and free-play JTL_251 (cost 6), which would be
#// ineligible at power 5.
## GIVEN
CommonSetup: ggk/ggk
WithP1SpaceArena: ASH_245:1:0
WithP1SpaceArenaUpgrade: 0:SOR_T01
WithP1Deck: JTL_251
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:JTL_251
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_245
P1SPACEARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:1:CARDID:JTL_251
P1SPACEARENAUNIT:1:READY

---

# SearchDeclineTakeNothing
#// ASH_245 Eye of Sion — the search is optional; you may take nothing even when a legal unit is found. Eye
#// exhausts for the ability, finds SEC_080 (cost 2 ≤ power 5) but P1 declines, so nothing is played and
#// SEC_080 returns to the deck.
## GIVEN
CommonSetup: ggk/ggk
WithP1SpaceArena: ASH_245:1:0
WithP1Deck: SEC_080
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:-
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_245
P1SPACEARENAUNIT:0:EXHAUSTED
P1GROUNDARENACOUNT:0
P1DECKCOUNT:1
