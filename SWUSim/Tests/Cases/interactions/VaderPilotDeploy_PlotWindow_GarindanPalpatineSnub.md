#// Darth Vader (JTL_006) deployed as a PILOT: "When deployed as an upgrade: Create 2 TIE Fighter tokens" shares
#// the deploy's timing window with the Plot window (CR 19.a; keywords/Plot_TriggerOrdering.md), and P1 orders
#// them. His list Plots SEC_186 Garindan, SEC_082 Chancellor Palpatine and SEC_189 Lurking Snub Fighter.
#//
#// ★ OWNER RULING 2026-09-13 (multiple Plot cards): "Plot and When Deployed share a window, so those should be
#//   orderable." Each Plot card is its own trigger (CR 19.a) in the deploy's window, so after one Plot card has
#//   been played and its own abilities resolved (CR 19.b), the player chooses between the NEXT Plot card and
#//   the leader's still-pending When Deployed (CR 7.6.9). The three TwoPlots_* sections pin that: the choice is
#//   offered, and each answer leads where it says.
#//   History: the first probe (2026-09-13) played Plot 1, then resolved Vader's trigger on its own (no prompt),
#//   then offered Plot 2. keywords/Plot_TriggerOrdering.md had documented a narrower model (the window as ONE
#//   entry, no interleaving) that the engine did not actually follow either; the ruling replaces both.
#//
#// Cards: SEC_186 Garindan 1/3 ("When Played: Name a card. Look at an opponent's hand and discard a card with
#//   that name from it.") · SEC_082 Chancellor Palpatine 2/2 ("When Played: If you control a leader unit, create
#//   2 Spy tokens and give those tokens Sentinel for this phase.") · SEC_189 Lurking Snub Fighter 2/3 space
#//   ("When Played: You may exhaust a unit.") · JTL_237 TIE Bomber (Vader's host) · SOR_095 Battlefield Marine.
#//   EffectStack-0 is the Plot window and EffectStack-1 is Vader.
#//
# VaderPilot_PlotWindowAndHisTokens_ThePlayerOrdersThem
## GIVEN
CommonSetup: ryk/ggw/{myLeader:JTL_006;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_186:1,8:SOR_046:1
WithP1SpaceArena: JTL_237:1:0
WithP2Hand: [SOR_095 SOR_046 SOR_095]
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve
P1SPACEARENACOUNT:1

---

# PlotFirst_GarindanNamesTheMarine_OneCopyIsDiscarded_ThenVadersTIEs
#// "Discard A card with that name": P2 holds two Marines and loses exactly one. Then Vader's 2 TIE Fighters.
#//   No P1OnlyActions: the deploy must pass the turn exactly once.
## GIVEN
CommonSetup: ryk/ggw/{myLeader:JTL_006;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_186:1,8:SOR_046:1
WithP1SpaceArena: JTL_237:1:0
WithP2Hand: [SOR_095 SOR_046 SOR_095]
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:Battlefield Marine
- P1>AnswerDecision:OK
## EXPECT
P2HANDCOUNT:2
P2DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_186
P1SPACEARENACOUNT:3
TURNPLAYER:2

---

# VaderFirst_ThenPalpatine_ThePilotedHostIsALeaderUnit_TwoSentinelSpies
#// Vader first: 2 TIEs. Then the Plot window: Palpatine's "if you control a leader unit" is met by the host,
#//   which is a leader unit while Vader pilots it. Two Spy tokens, with Sentinel this phase.
## GIVEN
CommonSetup: ryk/ggw/{myLeader:JTL_006;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_082:1,8:SOR_046:1
WithP1SpaceArena: JTL_237:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:myResources-0
## EXPECT
P1SPACEARENACOUNT:3
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:SEC_082
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:2:HASKEYWORD:Sentinel

---

# VaderFirst_ThenTheSnubFighter_ExhaustsTheEnemyMarine
## GIVEN
CommonSetup: ryk/ggw/{myLeader:JTL_006;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_189:1,8:SOR_046:1
WithP1SpaceArena: JTL_237:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1SPACEARENACOUNT:4

---

# TwoPlots_AfterPalpatine_ThePlayerOrdersTheNextPlotAgainstVadersTrigger
#// Palpatine and the Snub Fighter both in resources; the window is taken first and Palpatine played (his own
#//   When Played, the Spy tokens, resolves as part of that Plot). Now the Snub Fighter's Plot and Vader's "When
#//   deployed as an upgrade" are both still pending from the deploy: P1 is asked which comes next, and Vader's
#//   TIE tokens do not exist yet (only the host in space).
## GIVEN
CommonSetup: ryk/ggw/{myLeader:JTL_006;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_082:1,1:SEC_189:1,8:SOR_046:1
WithP1SpaceArena: JTL_237:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>ResolveTrigger:SWU_PLOT_WINDOW
- P1>AnswerDecision:myResources-0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:3

---

# TwoPlots_VadersTriggerNext_TheTIEsArrive_ThenTheSnubFighterIsStillOffered
## GIVEN
CommonSetup: ryk/ggw/{myLeader:JTL_006;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_082:1,1:SEC_189:1,8:SOR_046:1
WithP1SpaceArena: JTL_237:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>ResolveTrigger:SWU_PLOT_WINDOW
- P1>AnswerDecision:myResources-0
- P1>ResolveTrigger:WhenPlayedAsUpgrade
## EXPECT
P1SPACEARENACOUNT:3
P1HASDECISION
P1DECISIONTOOLTIP:Play_a_Plot_card_from_your_resources

---

# TwoPlots_TheWindowNext_TheSnubFighterBeforeTheTIEs_ThenVader
#// The other answer: the window again, so the Snub Fighter is played (it exhausts the Marine) while Vader's
#//   trigger still waits; then Vader's trigger resolves and the deploy ends with everything in play.
## GIVEN
CommonSetup: ryk/ggw/{myLeader:JTL_006;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_082:1,1:SEC_189:1,8:SOR_046:1
WithP1SpaceArena: JTL_237:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>ResolveTrigger:SWU_PLOT_WINDOW
- P1>AnswerDecision:myResources-0
- P1>ResolveTrigger:SWU_PLOT_WINDOW
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1SPACEARENACOUNT:4
P1NODECISION
TURNPLAYER:2
NOEXTRAACTION
