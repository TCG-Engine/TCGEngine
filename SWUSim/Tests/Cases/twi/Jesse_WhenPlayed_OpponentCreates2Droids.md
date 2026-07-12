# TWI_145 Jesse (Unit 4/4, Ground, cost 3, Aggression/Heroism) — "Raid 1. When Played: An opponent
# creates 2 Battle Droid tokens." Jesse enters P1's ground; her When Played makes the OPPONENT create
# 2 Battle Droid (TWI_T01) tokens on P2's side. (Raid 1 keyword is covered generically.)
# Base r = Aggression + leader rw = Aggression/Heroism cover both pips → no penalty.

## GIVEN
CommonSetup: rrw/grw/{myResources:3;handCardIds:TWI_145}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_145
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:TWI_T01
