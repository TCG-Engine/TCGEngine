# LOF_173 Unleash Rage — "Use the Force. If you do, give a friendly unit +3/+0 for this phase." With the
# Force, P1 buffs its 3/3 to power 6.

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
