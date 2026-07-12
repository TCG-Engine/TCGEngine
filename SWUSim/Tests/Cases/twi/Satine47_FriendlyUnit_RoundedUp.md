# TWI_047 Satine Kryze — a friendly non-Satine unit also gains the Action, and the amount is rounded UP.
# SOR_095 (3/3, undamaged) uses the granted Action → ceil(3/2) = 2 (not 1) discarded from P2's deck.
# Proves both the field-wide grant to an ordinary unit and the round-up.
## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: TWI_047:1:0
WithP1GroundArena: SOR_095:1:0
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]
## WHEN
- P1>UseUnitAbility:myGroundArena-1
## EXPECT
P2DISCARDCOUNT:2
P2DECKCOUNT:3
P1GROUNDARENAUNIT:1:EXHAUSTED
