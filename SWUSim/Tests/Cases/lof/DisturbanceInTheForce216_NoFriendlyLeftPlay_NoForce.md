# LOF_216 Disturbance in the Force — negative: no friendly unit left play this phase, so the event's
# condition fails — no Force token and no Shield.

## GIVEN
CommonSetup: yyw/rrk/{myResources:2;handCardIds:LOF_216}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
