# TWI_084 Kraken — "On Attack: Give each friendly token unit +1/+1 for this phase." Kraken (ready)
# attacks P2's base (its only target → auto-resolves). On Attack fires: the friendly Battle Droid
# token (TWI_T01, 1/1) becomes 2/2 for the phase. Kraken itself is NOT a token → unaffected (2/5).

## GIVEN
CommonSetup: gyk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_084:1:0
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
P1GROUNDARENAUNIT:1:POWER:2
P1GROUNDARENAUNIT:1:HP:2
P1GROUNDARENAUNIT:0:POWER:2
