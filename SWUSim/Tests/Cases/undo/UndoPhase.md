# UndoPhase_JumpToActionPhaseStart
#// Undo Phase jumps straight to the beginning of the current action phase — one press reverts ALL of the
#// phase's actions (three plays here) back to the post-resource first-action state.
## GIVEN
CommonSetup: grw/brk/{myResources:12}
WithPrivateGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: SOR_095
WithP1Hand: SOR_095
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0
- P1>UndoPhase
## EXPECT
P1HANDCOUNT:3
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:12
