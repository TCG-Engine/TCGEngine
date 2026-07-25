# UseForce_Buff
#// LOF_173 Unleash Rage — "Use the Force. If you do, give a friendly unit +3/+0 for this phase." With the
#// Force, P1 buffs its 3/3 to power 6.

## GIVEN
CommonSetup: rrw/rrk/{myResources:1;handCardIds:LOF_173}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:0:POWER:6

---

# NoForce_NoBuff_Discards
#// LOF_173 Unleash Rage — "Use the Force (lose your Force token). If you do, give a friendly unit +3/+0."
#// Without the Force P1 has no token to spend, so the "If you do" rider fails: the event is still played
#// (goes to discard) but NO buff is applied — the 3/3 stays at power 3 and no target decision is offered.
#// Ref: "has no effect if the player does not have the force" (Play anyway → discarded, no buff).

## GIVEN
CommonSetup: rrw/rrk/{myResources:1;handCardIds:LOF_173}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:0:POWER:3
P1DISCARDCOUNT:1
P1NODECISION
