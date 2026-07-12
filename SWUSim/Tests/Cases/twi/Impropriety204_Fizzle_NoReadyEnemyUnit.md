# TWI_204 Impropriety Among Thieves — "Choose a ready non-leader unit controlled by each player. If you
# do..." The swap requires a valid READY non-leader unit for BOTH players. Here P2's only unit (SEC_080)
# is EXHAUSTED, so there is no eligible enemy unit: the event fizzles — no choice is offered and no
# control changes. (Guards the "ready" requirement and the "if you do" conditional.)
## GIVEN
CommonSetup: rrk/bbw/{myResources:10;handCardIds:TWI_204}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:0:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P1NODECISION
