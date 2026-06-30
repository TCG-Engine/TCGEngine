# SOR_093 Alliance Dispatcher — the Action can only be taken if there is a unit in hand
# the player can actually play at the −1 discount. Here the hand unit (SOR_095, cost 2 →
# discounted 1) is unaffordable with 0 ready resources, so the action has no legal play
# and is a full no-op: the Dispatcher stays READY (action not spent), the Marine stays in
# hand, no resources change, and no decision is pending.

## GIVEN
CommonSetup: ggw/ggw/{myResources:0;handCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: SOR_093:1:0    # Alliance Dispatcher (ready) — index 0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:READY
P1HANDCOUNT:1
P1RESAVAILABLE:0
P1NODECISION
