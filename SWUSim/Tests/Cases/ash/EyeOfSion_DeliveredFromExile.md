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
