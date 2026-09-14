# BotPractice_UndoRewindsToBeforeYourLastAction
#// Bot Practice (owner ruling 2026-09-14): a step Undo rewinds past the BOT's moves to just before the human's own
#// last action. Owner report: "i can't Undo my action. the bot moves too fast for me to roll it back to my action"
#// — Undo reverted only the newest snapshot (the bot's reply), and the bot, now owing the move again, replayed it
#// within ~100 ms (deterministic RNG → the same move). The human could never get back to their own action.
#// Each undo record stores the acting seat; SWUComputeUndoTarget now walks down to the requester's own record.
#// P1 plays a Marine, the bot (P2) plays one; P1's Undo takes BOTH back and it is P1's action again.
## GIVEN
CommonSetup: grw/brk/{myResources:12;theirResources:12}
WithP1GlobalEffect: SWU_MODE_BOTPRACTICE
WithP1Hand: [SOR_095 SOR_095]
WithP2Hand: [SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>Undo
## EXPECT
P1HANDCOUNT:2
P1GROUNDARENACOUNT:0
P2HANDCOUNT:2
P2GROUNDARENACOUNT:0
TURNPLAYER:1

---

# BotPractice_UndoAgainGoesToYourPreviousAction
#// Two rounds of play: P1, bot, P1, bot. One Undo → back before P1's SECOND play (P1: 1 unit, bot: 1 unit);
#// a second Undo → back before P1's FIRST play (nothing on the board).
## GIVEN
CommonSetup: grw/brk/{myResources:12;theirResources:12}
WithP1GlobalEffect: SWU_MODE_BOTPRACTICE
WithP1Hand: [SOR_095 SOR_095]
WithP2Hand: [SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>PlayHand:0
- P2>PlayHand:0
- P1>Undo
## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P1HANDCOUNT:1
TURNPLAYER:1

---

# BotPractice_TwoUndosGoBackTwoOfYourActions
## GIVEN
CommonSetup: grw/brk/{myResources:12;theirResources:12}
WithP1GlobalEffect: SWU_MODE_BOTPRACTICE
WithP1Hand: [SOR_095 SOR_095]
WithP2Hand: [SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>PlayHand:0
- P2>PlayHand:0
- P1>Undo
- P1>Undo
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1HANDCOUNT:2
TURNPLAYER:1

---

# Control_PrivateGameWithoutABot_UndoStillStepsOneAction
#// A private game that is NOT Bot Practice keeps the one-step Undo: P1's Undo reverts only P2's play.
## GIVEN
CommonSetup: grw/brk/{myResources:12;theirResources:12}
WithPrivateGame: true
WithP1Hand: [SOR_095 SOR_095]
WithP2Hand: [SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>Undo
## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
