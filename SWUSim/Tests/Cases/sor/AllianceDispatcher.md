# Action_PlaysUnitDiscounted
#// SOR_093 Alliance Dispatcher (1/2) — Action [Exhaust]: Play a unit from your hand.
#// It costs 1 resource less. Host is the only arena unit (idx 0, ready). Hand holds
#// Battlefield Marine (SOR_095, Command/Heroism, cost 2). With exactly 1 ready resource
#// the play succeeds ONLY because of the −1 discount (2 → 1): the Marine enters the
#// ground arena, the single resource is spent, and the Dispatcher is exhausted.
#// Single hand unit → auto-resolves (no AnswerDecision needed).

## GIVEN
CommonSetup: ggw/ggw/{myResources:1;handCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: SOR_093:1:0    # Alliance Dispatcher (ready) — index 0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1RESAVAILABLE:0
P1HANDCOUNT:0

---

# Action_Unaffordable_NoOp
#// SOR_093 Alliance Dispatcher — the Action can only be taken if there is a unit in hand
#// the player can actually play at the −1 discount. Here the hand unit (SOR_095, cost 2 →
#// discounted 1) is unaffordable with 0 ready resources, so the action has no legal play
#// and is a full no-op: the Dispatcher stays READY (action not spent), the Marine stays in
#// hand, no resources change, and no decision is pending.

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
