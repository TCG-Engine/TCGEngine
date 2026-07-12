# TWI_047 Satine Kryze — the granted Action is a constant ability that only applies while a Satine is in
# play. With NO Satine on the board, an ordinary SOR_095 has no activated Action: using it is a no-op —
# nothing is milled and the unit stays ready. (Guards that the grant is conditional on Satine's presence.)
## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2Deck: [SEC_080 SEC_080 SEC_080]
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P2DISCARDCOUNT:0
P2DECKCOUNT:3
P1GROUNDARENAUNIT:0:READY
