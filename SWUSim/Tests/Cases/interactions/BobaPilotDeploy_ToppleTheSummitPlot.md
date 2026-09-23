#// Boba Fett (JTL_009) deployed as a PILOT: his "When deployed as an upgrade: Deal up to 4 damage divided as you
#// choose among any number of units" shares the deploy's timing window with a Plot window (CR 19.a), and P1
#// orders them (keywords/Plot_TriggerOrdering.md). With SEC_183 Topple the Summit ("Deal 3 damage to each
#// damaged unit") the ORDER is the whole combo: Boba first damages the units that Topple then finishes.
#//
#// FOUND BY: sweep retro #2 of run 2 (2026-09-13). Uncovered combo shapes PLOT JTL_009 → SEC_183 (154×) and
#//   ORDER JTL_009:WhenPlayedAsUpgrade + SEC_183:SWU_PLOT_WINDOW (64×), from boba_lakecountry.
#//   All sections were green on first run: this file is REGRESSION COVERAGE, not a bug.
#//
#// Cards: JTL_237 TIE Bomber 0/4 (Boba's host) · SEC_028 Trayus Acolyte 2/4 · SOR_095 Battlefield Marine 3/3 ·
#//   JTL_069 Munificent Frigate 4/7. EffectStack-0 is the Plot window, EffectStack-1 is Boba.
#//
# BobaPilot_PlotWindowAndHisDeployDamage_ThePlayerOrdersThem
## GIVEN
CommonSetup: rrk/ggw/{myLeader:JTL_009;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_183:1,7:SOR_046:1
WithP1SpaceArena: JTL_237:1:0
WithP2GroundArena: SEC_028:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: JTL_069:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve

---

# BobaFirst_2And2_ThenToppleFinishesBothSoftenedUnits
#// Boba puts 2 on the Acolyte (2/4) and 2 on the Marine (3/3). Topple then deals 3 to each damaged unit: both
#//   are defeated. The undamaged Frigate and Boba's own undamaged host are untouched. No P1OnlyActions, so the
#//   deploy's action must pass the turn exactly once.
## GIVEN
CommonSetup: rrk/ggw/{myLeader:JTL_009;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_183:1,7:SOR_046:1
WithP1SpaceArena: JTL_237:1:0
WithP2GroundArena: SEC_028:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: JTL_069:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:theirGroundArena-0:2,theirGroundArena-1:2
- P1>AnswerDecision:myResources-0
## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:CARDID:JTL_237
P1SPACEARENAUNIT:0:DAMAGE:0
P1LEADER:DEPLOYED
TURNPLAYER:2

---

# ToppleFirst_NothingIsDamagedYet_ThenBobasDamageStays
#// Topple first: no unit is damaged, so it deals nothing. Boba's 2 and 2 then land and stay.
## GIVEN
CommonSetup: rrk/ggw/{myLeader:JTL_009;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_183:1,7:SOR_046:1
WithP1SpaceArena: JTL_237:1:0
WithP2GroundArena: SEC_028:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: JTL_069:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:theirGroundArena-0:2,theirGroundArena-1:2
## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:2
P2SPACEARENAUNIT:0:DAMAGE:0

---

# ToppleFirst_OnlyTheAlreadyDamagedFrigateIsHit
#// The same order with the Frigate already carrying 1 damage: Topple hits exactly that unit (1 +3 = 4) and
#//   nothing else. Boba's damage follows.
## GIVEN
CommonSetup: rrk/ggw/{myLeader:JTL_009;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_183:1,7:SOR_046:1
WithP1SpaceArena: JTL_237:1:0
WithP2GroundArena: SEC_028:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: JTL_069:1:1
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:theirGroundArena-0:2,theirGroundArena-1:2
## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:2
