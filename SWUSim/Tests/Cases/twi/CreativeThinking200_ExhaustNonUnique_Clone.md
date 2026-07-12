# TWI_200 Creative Thinking (Event, cost 2, Trick) — "Exhaust a non-unique unit. Create a Clone Trooper
# token." Exhausts the (non-unique) enemy SOR_095 and creates a Clone Trooper (TWI_T02).

## GIVEN
CommonSetup: yyw/bbw/{myResources:2;handCardIds:TWI_200}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T02
