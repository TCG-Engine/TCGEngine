# SingleActionUndo
#// Playing a unit from hand is an action; Undo reverts it — the unit returns to hand, the arena empties,
#// and the resources are un-spent. Proves the harness Undo verb + the multi-step snapshot stack.
## GIVEN
CommonSetup: grw/brk/{myResources:2}
WithActivePlayer: 1
P1OnlyActions: true
WithP1Hand: SOR_095
## WHEN
- P1>PlayHand:0
- P1>Undo
## EXPECT
P1HANDCOUNT:1
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:2
