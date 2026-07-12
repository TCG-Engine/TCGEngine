# TWI_047 Satine Kryze (Unit, 0/6, cost 4, Vigilance/Heroism) — "Each unit (including enemy units) gains:
# Action [Exhaust]: Discard cards from an opponent's deck equal to half this unit's remaining HP, rounded
# up." Satine grants the Action to herself too: with 6 remaining HP she discards ceil(6/2) = 3 from the
# opponent's (P2's) deck and exhausts.
## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: TWI_047:1:0
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P2DISCARDCOUNT:3
P2DECKCOUNT:2
P1GROUNDARENAUNIT:0:EXHAUSTED
