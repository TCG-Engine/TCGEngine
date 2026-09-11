# Undo_UndoneActionStaysVisible_ThenTheUndoLine
#// Game-log follow-up (2026-09-11). The game log is part of the gamestate, so restoring an undo snapshot
#// rewinds it too — the undone play's line vanished and nothing said an undo happened. USER DECISION
#// (gamelog-updates #3): keep undone actions visible — the erased lines come back marked "(undone)", then
#// the undo line. Everything is written AFTER the restore, so it survives it.

## GIVEN
CommonSetup: grw/brk/{myResources:12}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>Undo

## EXPECT
P1GROUNDARENACOUNT:1
LOGCOUNT:1:(undone) P1 played [[SOR_095
LOGCOUNT:2:P1 played [[SOR_095
LASTLOGCONTAINS:P1 undid their last action

---

# Undo_ThreeInARow_OneLineWithTheCount
#// Each restore rewinds the previous undo's lines too; the carry re-adds them, and this player's trailing
#// undo lines fold into ONE count. (Fixture from undo/MultiStepUndo.md.)

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
LOGCOUNT:3:(undone) P1 played [[SOR_095
LOGCOUNT:1:undid
LASTLOGCONTAINS:P1 undid their last 3 actions

---

# Undo_PrivateLineStaysPrivate
#// A carried line keeps its ORIGINAL visibility: the undone "You drew X" is still P1's alone. (A draw needs
#// the opponent's consent to undo in a public game, hence the approval.) SOR_042 Search Your Feelings.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_042
WithP1Resources: 4
WithP1Deck: SOR_063
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_063
- P1>Undo
- P2>ApproveUndo

## EXPECT
P1LOGSEES:(undone) You drew [[SOR_063
P2LOGNOTSEES:[[SOR_063
LASTLOGCONTAINS:P1 undid an action (approved by P2)

---

# UndoRequest_Approved
#// Public match: an Undo Phase is a request; the opponent approves. (Fixture from undo/RequestApprove.md.)

## GIVEN
CommonSetup: grw/brk/{myResources:12}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>UndoPhase
- P2>ApproveUndo

## EXPECT
P1GROUNDARENACOUNT:0
LOGCOUNT:2:(undone) P1 played [[SOR_095
LASTLOGCONTAINS:P1 undid an action (approved by P2)

---

# UndoRequest_Denied
#// The denial is public too — both players saw the request prompt. Nothing was undone.

## GIVEN
CommonSetup: grw/brk/{myResources:12}
P1OnlyActions: true
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>UndoPhase
- P2>DenyUndo

## EXPECT
P1GROUNDARENACOUNT:1
LOGCOUNT:0:(undone)
LASTLOGCONTAINS:P2 denied P1's undo request
