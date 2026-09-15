#// Plot cards played in a leader deploy's window (CR 19.a/b) for the Aurra, Luke and Ahsoka lists, plus one
#// candidate engine bug found while writing them: a deploy with a Plot card in resources closes its action
#// TWICE.
#//
#// FIXED 2026-09-13 (all RED sections green; full suite 11895/0): the Plot window no longer ends the action itself
#//   when the deploy's own trigger resume is still queued (_SWUPlotWindowHandOff / _SWUBareTriggerResumePending,
#//   GameLogic.php). The action-close ledger lost 32 double closes, all Plot deploys, and gained none.
#//
#// FOUND BY: sweep retros #2–#4 of run 2 (2026-09-13): ORDER LAW_004:WhenPlayed + SEC_176:SWU_PLOT_WINDOW (99×)
#//   and PLOT LAW_004 → SEC_176 (99×) · PLOT JTL_012 → SEC_176 (31–60×) / → SEC_172 (29–54×) · PLOT ASH_009 →
#//   SEC_099 (62×) / → SEC_046 (32×) and their ORDER ASH_009:Support + … shapes.
#//
#// ★ WAS RED (3 sections) — THE DEPLOY'S ACTION IS CLOSED TWICE WHEN A PLOT CARD IS IN RESOURCES. Measured
#//   2026-09-13 with the house assertion NOEXTRAACTION ("something ran SWUAfterAction's terminal swap twice for
#//   one action"): it fails whether the Plot is PLAYED or DECLINED, with or without a leader trigger of its own,
#//   and passes on the same deploy with no Plot card (CONTROL below). The authoritative gate
#//   (_SWUActionCloseGate) refuses the second close, so the turn still passes exactly once and TURNPLAYER is
#//   right. That is why nothing visible breaks, and why only the counter can see it ("[ACTION-LEDGER]
#//   BLOCKED-DOUBLE-CLOSE" on STDERR). Likely seam, from keywords/Plot_TriggerOrdering.md's trace: the Plot
#//   window's _SWUPlotAfterPlay / _SWUPlotReoffer path ends the action after the deploy's own SWUAfterAction
#//   already did. Not fixed: a sweep is running and runtime code is frozen until it ends.
#//
#// GREEN, all passed on first run (regression coverage):
#//   - LAW_004 Aurra Sing deployed: "When Deployed: You may defeat a non-leader unit with 5 or less remaining HP"
#//     + SEC_176 Sudden Ferocity (upgrade, +3/+0, Plot).
#//   - JTL_012 Luke deployed as a PILOT (no deploy trigger of his own: the Plot window is the only entry) +
#//     SEC_172 Cinta Kaz ("When Played: You may attack with a unit"): the piloted Fighter attacks and fires
#//     Luke's granted "On Attack: You may deal 3 damage to a unit".
#//   - ASH_009 Ahsoka + SEC_099 Naboo Royal Starship ("Each friendly leader unit gains Raid 2 and Overwhelm").
#//     ★ OWNER RULING 2026-09-13: "Ahsoka gains Raid 2 + Overwhelm, and the unit they Support also gains her
#//     gained abilities" — the Naboo Royal Starship + Jar Jar (SEC_111) combo is exactly what Ahsoka lists go for.
#//     Pinned by the Ruling_Support_* sections (the engine already lent EFFECTIVE keywords: see
#//     _SWUSupportGrantAbilities in CombatLogic.php).
#//   - ASH_009 Ahsoka + SEC_046 Galen Erso ("When Played: Name a card. … each non-leader card an opponent owns
#//     with that name … loses all abilities"): naming P2's Sentinel unit before Support lets the Support
#//     attacker reach the base.
#// Cards also used: SOR_046 Consular Security Force 3/7 (seeded with 2 damage = 5 remaining) · SOR_095
#//   Battlefield Marine · SOR_237 Alliance X-Wing (Luke's host) · JTL_069 Munificent Frigate · SOR_063 Cloud City
#//   Wing Guard 2/4 Sentinel. On a two-entry ordering prompt EffectStack-0 is the Plot window.
#//
# RED_PlotDeclined_TheDeployClosesItsActionOnce
## GIVEN
CommonSetup: rrk/ggw/{myLeader:JTL_012;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_176:1,7:SOR_046:1
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:-
## EXPECT
TURNPLAYER:2
NOEXTRAACTION

---

# RED_PlotPlayed_TheDeployClosesItsActionOnce
## GIVEN
CommonSetup: rrk/ggw/{myLeader:JTL_012;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_176:1,7:SOR_046:1
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
TURNPLAYER:2
NOEXTRAACTION
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# RED_AurraWhenDeployedFirst_ThenThePlot_TheDeployClosesItsActionOnce
## GIVEN
CommonSetup: rrk/ggw/{myLeader:LAW_004;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_176:1,7:SOR_046:1
WithP2GroundArena: SOR_046:1:2
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
TURNPLAYER:2
NOEXTRAACTION

---

# CONTROL_NoPlotCard_ThePlainDeployClosesOnce
## GIVEN
CommonSetup: rrk/ggw/{myLeader:JTL_012;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8
## WHEN
- P1>DeployLeader
## EXPECT
TURNPLAYER:2
NOEXTRAACTION

---

# Aurra_WhenDeployedAndThePlotWindow_ThePlayerOrdersThem
## GIVEN
CommonSetup: rrk/ggw/{myLeader:LAW_004;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_176:1,7:SOR_046:1
WithP2GroundArena: SOR_046:1:2
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>DeployLeader
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:EffectStack-0&EffectStack-1

---

# AurraFirst_DefeatsThe5RemainingHpUnit_ThenSuddenFerocityOnHer
#// The 3/7 Consular with 2 damage has exactly 5 remaining HP, so it is a legal target (boundary). Then Sudden
#//   Ferocity on Aurra: 3 +3 = 6 power. The turn has not passed mid-deploy (checked in a probe: TURNPLAYER 1
#//   while the Plot offer was open).
## GIVEN
CommonSetup: rrk/ggw/{myLeader:LAW_004;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_176:1,7:SOR_046:1
WithP2GroundArena: SOR_046:1:2
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:CARDID:LAW_004
P1GROUNDARENAUNIT:0:POWER:6

---

# LukePilot_CintaKazPlot_ThePilotedXWingAttacks_LukesGrantedOnAttackDeals3
## GIVEN
CommonSetup: rrk/ggw/{myLeader:JTL_012;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_172:1,7:SOR_046:1
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_172
P1SPACEARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# Ahsoka_StarshipPlotFirst_SheGainsRaidAndOverwhelm_ThenSupportDeclined
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_099:1,7:SOR_095:1
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:ASH_009
P1GROUNDARENAUNIT:1:HASKEYWORD:Overwhelm
P1GROUNDARENAUNIT:1:HASKEYWORD:Raid
P1GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm
P1SPACEARENAUNIT:0:CARDID:SEC_099

---

# Ahsoka_GalenPlotFirst_NamesTheSentinel_TheSupportAttackerCanReachTheBase
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_046:1,7:SOR_095:1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_063:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:Cloud City Wing Guard
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirBase-0
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# CONTROL_Ahsoka_GalenNamesSomethingElse_TheSentinelStillHoldsTheAttack
#// Galen names the Marine instead: the Wing Guard keeps Sentinel, so the attack goes to it with no target
#//   choice, and Ahsoka's granted On Attack (+2/+0 to a unit with less power) comes up next.
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_046:1,7:SOR_095:1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_063:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:Battlefield Marine
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1DECISIONTOOLTIP:Give_+2/+0_to_a_unit_with_less_power_than_this_unit

---

# Ruling_Support_StarshipFirst_TheSupportAttackerGainsHerRaid2AndOverwhelm
#// Plot the Starship first (Ahsoka gains Raid 2 + Overwhelm), then Support: the Marine attacks the 2/4 Acolyte as
#//   3 +2 = 5 with Overwhelm, so the Acolyte dies and 1 goes through to the base. Ahsoka's own granted On Attack
#//   is declined.
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_099:1,7:SOR_095:1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_028:1:0
## WHEN
- P1>DeployLeader
- P1>ResolveTrigger:SWU_PLOT_WINDOW
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# CONTROL_Ruling_Support_SupportBeforeTheStarship_NothingToLendYet
#// Support first: Ahsoka has no Raid or Overwhelm yet, so the Marine hits the Acolyte for its printed 3 (it
#//   survives, nothing reaches the base). The Starship arrives afterwards from the Plot window.
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_099:1,7:SOR_095:1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_028:1:0
## WHEN
- P1>DeployLeader
- P1>ResolveTrigger:Support
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:-
- P1>AnswerDecision:myResources-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:0
P1SPACEARENAUNIT:0:CARDID:SEC_099

---

# Ruling_Support_TheAhsokaCombo_JarJarThenStarshipThenSupport_SevenOverwhelm
#// Both Plots, as the Ahsoka lists play it (and, per the 2026-09-13 Plot ruling, ordered one Plot card at a time
#//   against Support): Jar Jar gives the Marine +2/+2; the next Plot, the Starship, gives Ahsoka Raid 2 +
#//   Overwhelm; Support lends them. The Marine swings 3 +2 +2 = 7 with Overwhelm into the 2/4 Acolyte: 3 to base.
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_111:1,1:SEC_099:1,8:SOR_095:1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_028:1:0
## WHEN
- P1>DeployLeader
- P1>ResolveTrigger:SWU_PLOT_WINDOW
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-0
- P1>ResolveTrigger:SWU_PLOT_WINDOW
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P1GROUNDARENACOUNT:3
P1SPACEARENAUNIT:0:CARDID:SEC_099
