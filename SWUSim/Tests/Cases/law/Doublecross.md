# ExchangeControlCredits
#// LAW_170 Double-Cross (Command event, cost 6) — "Choose a friendly non-leader unit and an enemy
#// non-leader unit. Exchange control of those units. The player who takes control of the lower-cost unit
#// creates Credit tokens equal to the difference between costs." Friendly SOR_046 (cost 4) swaps with
#// enemy SEC_080 (cost 2); caster takes the cheaper SEC_080 -> caster creates 2 Credits.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_170

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P1CREDITCOUNT:2
