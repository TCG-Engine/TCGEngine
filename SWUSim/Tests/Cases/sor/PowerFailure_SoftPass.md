# SOR_170 Power Failure — soft-pass (defeat none)
# "Defeat any number" is min 0, so even a single-upgrade host shows the picker and
# the player may confirm with nothing selected (AnswerDecision:-). The upgrade survives.
# (Covers the SOR_072 Entrenched "defeat 0" intent.)

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_170}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:LOF_215

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2DISCARDCOUNT:0
P1DISCARDCOUNT:1
