# PlotPassButton_ClosesWindow_AndPassesTurn
#// Bug #923, both symptoms, one cause. Declining via the UI's PASS button — not the '-' answer the
#//   other cases use — is what breaks it.
#//
#//   _SWUPlotReoffer queues MZMAYCHOOSE (the offer) followed by CUSTOM PLOT_PLAY (the resolver), and
#//   PLOT_PLAY is what clears SWU_PLOT_IN_PROGRESS and runs the real After Action when the player
#//   declines. But DecisionQueueController skips the NEXT decision after a PASS unless it declares
#//   dontSkipOnPass, so the PASS button skips PLOT_PLAY entirely and the decline branch never runs:
#//     • the window flag stays set  → the next card played is redirected back into the Plot
#//       orchestrator and re-offers Plot (reported: attaching Ascension Cable re-prompted)
#//     • the deploy's After Action never completes → the turn never passes → a free extra action
#//
#//   '-' takes a different path through the queue (it is an answer, not a pass), which is why every
#//   '-' case below passes and only this one fails. Board is game 3303's, the reported position.

## GIVEN
CommonSetup: ngw/ngw/{myLeader:SEC_001;myBase:JTL_024;theirLeader:ASH_016;theirBase:ASH_023}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 1:SEC_176:1,1:JTL_221:1,2:JTL_162:1,1:LAW_174:1,1:ASH_191:1

## WHEN
- P2>DeployLeader
- P2>AnswerDecision:PASS

## EXPECT
P2NODECISION
TURNPLAYER:1

---

# DeclinePlot_AsP2_PassesTurn
#// Bug #923, reproduced from the reported board (game 3303 / report #923) rather than an invented one.
#//   The distinguishing detail is that the DEPLOYING player is P2: Shin Hati is P2's leader, P2 is the
#//   active player and holds initiative. Five P1-side fixtures all passed, so the seat matters.
#//
#//   P2 has 6 ready resources — satisfying ASH_016's "Epic Action: if you control 6 or more resources,
#//   deploy this leader" — one of which is SEC_176 Sudden Ferocity (Upgrade, cost 3, Plot), so the CR §19
#//   Plot window genuinely opens and is affordable. P2 declines it. The deploy is P2's action, so the
#//   turn must pass to P1; if the Plot window's interception of the deploy's terminal SWUAfterAction
#//   does not unwind, P2 keeps priority and gets a free extra action.

## GIVEN
CommonSetup: ngw/ngw/{myLeader:SEC_001;myBase:JTL_024;theirLeader:ASH_016;theirBase:ASH_023}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 1:SEC_176:1,1:JTL_221:1,2:JTL_162:1,1:LAW_174:1,1:ASH_191:1

## WHEN
- P2>DeployLeader
- P2>AnswerDecision:-

## EXPECT
TURNPLAYER:1

---

# DeclinePlot_PassesTurn_NoExtraAction
#// Bug #923 (first half). A leader deploy opens the CR §19 Plot window when the deploying player has an
#//   affordable Plot card in resources. The window is orchestrated by INTERCEPTING the deploy's terminal
#//   SWUAfterAction (GameLogic.php, SWU_PLOT_IN_PROGRESS) so a nested Plot play cannot end the deploy
#//   action early. Declining the offer must close the window AND finalise the deploy — i.e. the turn
#//   passes. If the flag survives the decline, SWUAfterAction keeps getting redirected to the Plot
#//   orchestrator and never swaps: the player keeps priority and gets a free extra action.
#//
#//   ASH_016 Shin Hati deploys via "Epic Action: if you control 6 or more resources"; 8 resources are
#//   given, one of them SEC_053 One in a Million (Event, cost 1 — the cheapest Plot card) so the window
#//   genuinely opens. P1 declines the Plot offer.

## GIVEN
CommonSetup: yrw/grw/{myLeader:ASH_016}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 1:SEC_053:1,7:SOR_046:1

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:-

## EXPECT
TURNPLAYER:2

---

# DeclinePlot_ClosesWindow
#// Bug #923 (second half). The same decline must also CLOSE the window, not merely skip one offer.
#//   The reported symptom was being prompted to Plot again on the next card played (attaching LOF_215
#//   Ascension Cable), which is what a still-set SWU_PLOT_IN_PROGRESS does: the next play's After Action
#//   is redirected back into the Plot orchestrator, which re-offers. Asserting "no decision is pending
#//   and the turn has passed" covers that without depending on a second play — if the window were still
#//   open, P1 would either hold priority or have a pending offer.

## GIVEN
CommonSetup: yrw/grw/{myLeader:ASH_016}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 1:SEC_053:1,7:SOR_046:1

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:-

## EXPECT
P1NODECISION
TURNPLAYER:2
