# SOR_219 Sneak Attack (Cunning event, cost 2) — "Play a unit from your hand. It costs 3 less and
# enters play ready." P1's leader is Han (Cunning+Heroism) so the event plays at its printed 2.
# The hand's only unit is SOR_095 Battlefield Marine (Command,Heroism, printed 2 → +2 off-aspect
# Command penalty = effective 4); the −3 discount drops it to 1. P1 has exactly 3 ready: 2 pays the
# event, leaving 1 — exactly the discounted unit cost. The Marine enters READY (not exhausted) and
# P1 ends with 0 ready resources. (Without the discount the Marine would cost 4 and could not be
# paid from the leftover 1, so COUNT:1 + RESAVAILABLE:0 pins the discount at 3.)

## GIVEN
CommonSetup: yyw/rrk/{myResources:3;handCardIds:SOR_219,SOR_095}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:READY
P1RESAVAILABLE:0
P1HANDCOUNT:0
P1DISCARDCOUNT:1
