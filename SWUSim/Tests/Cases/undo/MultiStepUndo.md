# MultiStepUndo_ThreeActionsBackToStart
#// Multi-step undo: P1 (P2 auto-passes, so P1 keeps taking actions in one phase) plays three units, then
#// Undo x3 unwinds them one at a time back to the start of the action phase — hand refilled to 3, arena
#// empty, resources un-spent.
## GIVEN
CommonSetup: grw/brk/{myResources:12}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: SOR_095
WithP1Hand: SOR_095
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0
- P1>Undo
- P1>Undo
- P1>Undo
## EXPECT
P1HANDCOUNT:3
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:12
