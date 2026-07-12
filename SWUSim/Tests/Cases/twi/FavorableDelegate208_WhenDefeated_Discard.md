# TWI_208 Favorable Delegate — the When Defeated discards a card from P1's hand. Pre-damaged to 3, it
# attacks SOR_046 (3/7) and dies to the 3 counter-damage; its When Defeated discards the lone hand card.

## GIVEN
CommonSetup: yyk/rrk/{myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: TWI_208:1:3
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0
