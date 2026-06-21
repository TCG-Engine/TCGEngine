# LOF_264 It's Worse — Defeat a non-leader unit. The enemy SOR_046 is defeated.

## GIVEN
CommonSetup: ggk/rrw/{myResources:7;handCardIds:LOF_264}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
