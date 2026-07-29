# RequestApprove_ApproveAppliesMultiStepTarget
#// Public match: P1 plays three units then requests Undo Phase (always a request in public). It does NOT
#// apply yet — the board is untouched. When P2 approves, the undo reverts to the SAME target P1 chose
#// (the action-phase start = all three plays undone), proving the approval carries the multi-step target
#// rather than reverting only the single top action.
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
- P1>UndoPhase
- P2>ApproveUndo
## EXPECT
P1HANDCOUNT:3
P1GROUNDARENACOUNT:0

---

# RequestApprove_DenyLeavesStateIntact
#// Public match: P1 requests Undo Phase; P2 denies. The board stays exactly as it was — all three units
#// remain deployed, nothing reverts.
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
- P1>UndoPhase
- P2>DenyUndo
## EXPECT
P1HANDCOUNT:0
P1GROUNDARENACOUNT:3
