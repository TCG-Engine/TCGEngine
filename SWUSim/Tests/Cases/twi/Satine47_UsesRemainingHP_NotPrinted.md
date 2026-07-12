# TWI_047 Satine Kryze — the amount uses the unit's REMAINING HP, not printed HP. SOR_046 (3/7) with 2
# damage has 5 remaining HP → ceil(5/2) = 3 discarded from P2's deck. (Printed 7 would give ceil(7/2) = 4,
# so 3 confirms remaining-HP is used.)
## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: TWI_047:1:0
WithP1GroundArena: SOR_046:1:2
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]
## WHEN
- P1>UseUnitAbility:myGroundArena-1
## EXPECT
P2DISCARDCOUNT:3
P2DECKCOUNT:3
P1GROUNDARENAUNIT:1:EXHAUSTED
