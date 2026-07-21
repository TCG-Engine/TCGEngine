# DamageThenSearch
#// ASH_118 8D8 (Ground, 1/4, Hidden) — Action [Exhaust]: deal 1 damage to another friendly unit; if you
#// do, search the top 5 of your deck for a unit, reveal it, and draw it. 8D8 deals 1 to SOR_095, then
#// searches and draws SEC_080 (the unit on top of the deck).
## GIVEN
CommonSetup: ggk/ggk
WithP1GroundArena: ASH_118:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SEC_080
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SEC_080
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:1
P1HANDCOUNT:1

---

# Action_DamageFriendlyThenSearchDraw
#// ASH_118 8D8 — Action [Exhaust]: deal 1 to another friendly unit; if you do, search the top 5 for a unit
#// and draw it. 8D8 damages SOR_046 (the only other friendly) and draws SOR_095 from the deck.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_118:1:0
WithP1GroundArena: SOR_046:1:0
WithP1Deck: SOR_095
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SOR_095
## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1
P1HANDCOUNT:1
