# SOR_092 — only one unit in play (the dealer), no OTHER units to damage. The +2/+2 is still
# applied (the buff is NOT gated on having split targets), and no MZSPLITASSIGN is queued. Guards
# the buff-before-target-check ordering.

## GIVEN
CommonSetup: ggk/ggk/{myResources:5;handCardIds:SOR_092}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0    # 3/3 → 5/5; no other units anywhere

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1NODECISION
