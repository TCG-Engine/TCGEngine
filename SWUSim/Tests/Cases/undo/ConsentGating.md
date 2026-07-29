# ConsentGating_Private_AllFree
#// Private match (requirement #7): undo is ALWAYS free — no request, no opponent consent. Undo Phase here
#// reverts all three plays immediately even though a public game would have required a request.
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

---

# ConsentGating_Public_UndoPhase_Request
#// Public match: Undo Phase ALWAYS requires an opponent request (requirement #4), so it does NOT apply
#// immediately — the board is unchanged (all three units still deployed) while the request is pending.
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
## EXPECT
P1HANDCOUNT:0
P1GROUNDARENACOUNT:3

---

# ConsentGating_Public_OwnNoInfo_Free
#// Public match: a plain step Undo of your OWN within-phase action that revealed no new info is free (no
#// request) — one unit returns to hand immediately.
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
## EXPECT
P1HANDCOUNT:1
P1GROUNDARENACOUNT:2
