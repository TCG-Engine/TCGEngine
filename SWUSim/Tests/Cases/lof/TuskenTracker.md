# EnemiesLoseHidden
#// LOF_209 Tusken Tracker — Raid 2 + When Played: each enemy unit loses Hidden for this phase. P1 plays
#// it; the enemy Hidden unit (LOF_228) no longer has Hidden.

## GIVEN
CommonSetup: yyk/rrw/{myResources:3;handCardIds:LOF_209}
P1OnlyActions: true
WithP2GroundArena: LOF_228:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:NOTKEYWORD:Hidden
