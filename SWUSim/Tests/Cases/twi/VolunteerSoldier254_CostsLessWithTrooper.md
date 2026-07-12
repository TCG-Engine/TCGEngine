# TWI_254 Volunteer Soldier (Unit 2/3, Ground, cost 3) — "Raid 1. If you control a Trooper unit, this
# unit costs 1 resource less to play." With a friendly Battle Droid (Trooper) in play, it costs 2 —
# playing it with exactly 2 ready resources succeeds.

## GIVEN
CommonSetup: rrk/grw/{myResources:2;handCardIds:TWI_254}
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1GROUNDARENAUNIT:1:CARDID:TWI_254
P1RESAVAILABLE:0
