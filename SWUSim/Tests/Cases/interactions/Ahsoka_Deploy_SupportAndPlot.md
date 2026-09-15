#// Ahsoka Tano (ASH_009) deploying: her SUPPORT and a PLOT window share one timing window, and her Support
#// attack carries her granted On Attack alongside the attacker's own.
#//
#// FOUND BY: sweep retro #1 of run 2 (2026-09-13). The most frequent uncovered combo shapes of the first
#//   2,110 games: ORDER ASH_009:Support + SEC_111:SWU_PLOT_WINDOW (340×) and ORDER ASH_009:SupportOnAttack +
#//   ASH_248:OnAttack (344×), e.g. aggro_ahsoka_yellow v control_lando_blue s065, aggro_ahsoka_blue v
#//   aggro_ahsoka_yellow s093. All sections were green on first run: this file is REGRESSION COVERAGE, not a bug.
#//
#// THE RULES.
#//   - ASH_009 deployed: "Support (When you deploy this leader, you may attack with another unit. It gains this
#//     unit's other abilities for this attack.) On Attack: You may give a unit with less power than this unit
#//     +2/+0 for this phase."
#//   - Plot, CR 19.a: "When you deploy a leader: you may play this card from your resource zone…". Both triggers
#//     are P1's and fire on the same deploy, so P1 orders them (CR 7.6.9; see keywords/Plot_TriggerOrdering.md).
#//   - Support's attacker GAINS Ahsoka's On Attack, and has its own. Both are On Attack triggers of the same
#//     attack, controlled by P1, so P1 orders them too (CR 7.6.9).
#// Cards: SEC_111 Jar Jar Binks (2/1, Plot; "When Played: You may give another friendly unit +2/+2 for this
#//   phase") · SOR_095 Battlefield Marine 3/3 · ASH_248 Neel 1/4 ("When Played/On Attack: The next unit you
#//   play this phase with 1 or less power enters play ready") · SOR_108 Vanguard Infantry 1/2.
#// EffectStack-0 is the Plot window and EffectStack-1 is Ahsoka's Support (the window is armed first in
#//   SWUDeployLeader).
#//
# Deploy_SupportAndPlotWindow_ThePlayerOrdersThem
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_111:1,7:SOR_095:1
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>DeployLeader
## EXPECT
P1LEADER:DEPLOYED
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve

---

# PlotFirst_JarJarBuffsTheMarine_ThenTheSupportAttackHitsFor5
#// Plot first: Jar Jar is played and gives the Marine +2/+2. Then Support: the Marine (3 +2 = 5) attacks the
#//   base. It gains Ahsoka's On Attack, whose only legal target is Jar Jar (power 2 < 5); declined.
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_111:1,7:SOR_095:1
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-
## EXPECT
P2BASEDMG:5
P1GROUNDARENACOUNT:3
TURNPLAYER:2

---

# SupportFirst_TheMarineAttacksFor3_ThenThePlotWindowOpens
#// Support first: the Marine attacks for its printed 3. Ahsoka's granted On Attack has no legal target (no unit
#//   with less than 3 power) and so does not prompt. THEN the Plot window: Jar Jar is played and buffs the
#//   Marine, too late for the attack.
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_111:1,7:SOR_095:1
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:3
P1GROUNDARENACOUNT:3
TURNPLAYER:2

---

# SupportAttacker_OwnOnAttack_AndAhsokasGranted_ThePlayerOrdersThem
#// Neel attacks through Support. His own On Attack and the On Attack he gains from Ahsoka trigger together, and
#//   P1 must be asked to order them.
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8:SOR_095:1
WithP1GroundArena: ASH_248:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve

---

# NeelFirst_TheAttackResolves_AndTheNextSmallUnitEntersReady
#// Neel's On Attack first. Ahsoka's granted one then has no legal target (nothing below Neel's 1 power). The
#//   attack hits for 1, and the 1-power Vanguard Infantry P1 plays next this phase enters play READY.
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8:SOR_095:1
WithP1GroundArena: ASH_248:1:0
WithP1Hand: SOR_108
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:EffectStack-1
- P1>PlayHand:0
## EXPECT
P2BASEDMG:1
P1GROUNDARENAUNIT:2:CARDID:SOR_108
P1GROUNDARENAUNIT:2:READY

---

# AhsokaGrantedFirst_SameResult
#// The other order: the granted On Attack first (no target), then Neel's. Same outcome, which proves both
#//   triggers resolved and the order made no difference here.
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8:SOR_095:1
WithP1GroundArena: ASH_248:1:0
WithP1Hand: SOR_108
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:EffectStack-2
- P1>PlayHand:0
## EXPECT
P2BASEDMG:1
P1GROUNDARENAUNIT:2:CARDID:SOR_108
P1GROUNDARENAUNIT:2:READY

---

# CONTROL_NoNeelAttack_TheSmallUnitEntersExhausted
#// The control that makes the READY checks above mean something: Support is DECLINED, so Neel never attacks,
#//   and the same Vanguard Infantry enters play exhausted, as units normally do.
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8:SOR_095:1
WithP1GroundArena: ASH_248:1:0
WithP1Hand: SOR_108
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:-
- P1>PlayHand:0
## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:2:CARDID:SOR_108
P1GROUNDARENAUNIT:2:EXHAUSTED

---

# HanFirst_HisExperienceMakesHimTwo_AhsokasGrantedBuffCanTargetTheOnePowerUnit
#// Retro #6–#8 (ASH_009:SupportOnAttack + LAW_037:OnAttack, 7–24×). LAW_037 Han Solo 1/1: "On Attack: Give an
#//   Experience token to this unit." Han's own On Attack first makes him 2/2 (a lasting stat), so Ahsoka's
#//   granted "a unit with less power than this unit" now reaches the 1-power Vanguard Infantry.
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8:SOR_095:1
WithP1GroundArena: LAW_037:1:0
WithP1GroundArena: SOR_108:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myGroundArena-0
- P1>ResolveTrigger:OnAttack
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-1

---

# AhsokasGrantedFirst_HanIsStillOne_NothingIsWeaker_ThenHanGetsHisExperience
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8:SOR_095:1
WithP1GroundArena: LAW_037:1:0
WithP1GroundArena: SOR_108:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myGroundArena-0
- P1>ResolveTrigger:SupportOnAttack
## EXPECT
P1NODECISION
P2BASEDMG:2
P1GROUNDARENAUNIT:0:CARDID:LAW_037
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
