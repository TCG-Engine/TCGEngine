# Plot shares a timing window with the deployed leader's OWN trigger — the player orders them.
#
# Bug report #1024 (game 4161): "Deploying Boba JTL does not let me choose order of Plot Cinta Kaz and
# Boba's When attached as Pilot trigger. These are both supposed to be in the same timing window."
#
# THE RULES, and they are unambiguous:
#   CR 19.a — "'Plot' is a keyword that resolves like the triggered ability: **'When you deploy a
#              leader: You may play this card from your resource zone…'**"
#   CR 7.6.9 — "If a player must resolve multiple triggered abilities on cards they control at the same
#              time, **that player chooses the order** in which to resolve those abilities."
# JTL_009 Boba Fett's "When deployed as an upgrade: Deal up to 4 damage…" triggers on the same deploy,
# for the same player. Two simultaneous triggers → the controller orders them. Compare CR 7.6.13.b,
# which says exactly this for the other keyword-that-resolves-like-a-trigger family: "'When Played'
# abilities, Ambush, and Shielded all resolve in the same timing window, in the order that the card's
# controller chooses."
#
# WHAT WAS WRONG. The order was fixed by ARCHITECTURE, not by a rule. `SWUDeployLeader` armed the Plot
# window but the window only OPENED from the deploy's `SWUAfterAction`, which runs after the leader's
# own entry triggers have already been queued. Traced on the reported board — one request, three calls:
#
#     [P] FlushEntryTriggerBag     ← Boba's damage queued first …
#     [P] SWUAfterAction
#     [P] _SWUPlotAfterPlay
#     [P] _SWUPlotReoffer          ← … Plot queued behind it, always
#
# and the observed prompts were `OPTIONCHOOSE [Unit&Pilot]` → `MZSPLITASSIGN [4|…]` → `MZMAYCHOOSE
# [myResources-0]`, with no ordering step anywhere. The Plot window is now added to the SAME pending
# trigger bag as every other "when you deploy a leader" trigger (JTL_191 Invincible already rode it),
# so `FlushEntryTriggerBag` orders it with the existing `Choose_trigger_to_resolve` MZCHOOSE.
#
# ⚠ SCOPE, stated plainly: the Plot WINDOW is one entry in that ordering, not one entry per Plot card.
# With two Plot cards you may still play them in any order within the window (CR 19.b), but you cannot
# interleave the leader's trigger between them. That is a narrower reading than CR 7.6.9 strictly
# allows; it is called out in `_SWUPlotWindowTriggerCardID`'s comment rather than left silent.

---

# BothTriggersPresent_ThePlayerIsASKEDWhichResolvesFirst
#// THE BUG. P2's board is game 4161's: Boba Fett (JTL_009) deploying as a Pilot onto SEC_171 Punishing
#// One, with SEC_172 Cinta Kaz sitting in the resources as the Plot card. Both triggers belong to P2 and
#// fire on the same deploy, so an ordering prompt must come BEFORE either one resolves.
#// Before the fix this read `MZSPLITASSIGN … Divide_up_to_4_damage_among_units` — Boba's ability had
#// already started resolving and the choice was never offered.
## GIVEN
CommonSetup: ngw/ngw/{myLeader:HMW_011;myBase:JTL_024;theirLeader:JTL_009;theirBase:JTL_031}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 1:SEC_172:1,7:SOR_046:1
WithP2SpaceArena: [SEC_171:1:0]
WithP1SpaceArena: [JTL_087:1:0]
## WHEN
- P2>DeployLeader
- P2>AnswerDecision:Pilot
## EXPECT
P2HASDECISION
P2DECISIONTOOLTIP:Choose_trigger_to_resolve

---

# PlotFirst_CintaIsPlayedBEFOREBobasDamage
#// The point of having the choice at all. P2 resolves the Plot window first: Cinta Kaz (cost 6) is
#// played from resources and her own "When Played: you may attack with a unit" is declined, and only
#// THEN does Boba's split damage come up. Cinta being on the board while the damage is assigned is the
#// whole reason a player would pick this order — and it is unreachable in the old code.
## GIVEN
CommonSetup: ngw/ngw/{myLeader:HMW_011;myBase:JTL_024;theirLeader:JTL_009;theirBase:JTL_031}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 1:SEC_172:1,7:SOR_046:1
WithP2SpaceArena: [SEC_171:1:0]
WithP1SpaceArena: [JTL_087:1:0]
## WHEN
- P2>DeployLeader
- P2>AnswerDecision:Pilot
#// ⚠ EffectStack-0 is the PLOT WINDOW and EffectStack-1 is Boba. The window is armed in
#// SWUDeployLeader before the Unit/Pilot branch stages the leader's own trigger, so it is entry 0.
- P2>AnswerDecision:EffectStack-0
- P2>AnswerDecision:myResources-0
#// Cinta's own When Played ("you may attack with a unit") — declined, so the section measures the
#// ORDERING and not an attack's side effects.
- P2>AnswerDecision:-
## EXPECT
P2GROUNDARENACOUNT:1
P2HASDECISION
P2DECISIONTOOLTIP:Divide_up_to_4_damage_among_units

---

# LeaderTriggerFirst_TheOldORDERIsStillREACHABLE
#// The other branch, and the regression guard for the fix: making Plot orderable must not make the
#// previous behaviour impossible. Resolving Boba's damage first must still leave the Plot window open
#// afterwards — that is the line every existing Plot test walks.
## GIVEN
CommonSetup: ngw/ngw/{myLeader:HMW_011;myBase:JTL_024;theirLeader:JTL_009;theirBase:JTL_031}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 1:SEC_172:1,7:SOR_046:1
WithP2SpaceArena: [SEC_171:1:0]
WithP1SpaceArena: [JTL_087:1:0]
## WHEN
- P2>DeployLeader
- P2>AnswerDecision:Pilot
- P2>AnswerDecision:EffectStack-1
- P2>AnswerDecision:theirSpaceArena-0:4
## EXPECT
P1SPACEARENACOUNT:0
P2HASDECISION
P2DECISIONTOOLTIP:Play_a_Plot_card_from_your_resources

---

# NoPlotCard_NoOrderingPromptAtAll
#// THE FIRST NEGATIVE CONTROL. A single trigger must still auto-dispatch — an ordering MZCHOOSE with
#// one option is a dead click, and `FlushEntryTriggerBag` deliberately skips it. Same board with the
#// Plot card swapped out for a plain resource: Boba's damage comes straight up.
## GIVEN
CommonSetup: ngw/ngw/{myLeader:HMW_011;myBase:JTL_024;theirLeader:JTL_009;theirBase:JTL_031}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 8:SOR_046:1
WithP2SpaceArena: [SEC_171:1:0]
WithP1SpaceArena: [JTL_087:1:0]
## WHEN
- P2>DeployLeader
- P2>AnswerDecision:Pilot
## EXPECT
P2HASDECISION
P2DECISIONTOOLTIP:Divide_up_to_4_damage_among_units

---

# PlotCardUNAFFORDABLE_NoOrderingPrompt
#// THE SHARPEST NEGATIVE. Cinta Kaz IS in the resources, but P2 cannot pay her cost 6.
#// `PlayerHasPlotsToPlay` already gates the window on affordability, so no Plot trigger may be armed and
#// no ordering prompt may appear. Without this, a fix that armed the trigger on mere PRESENCE would
#// offer a choice between Boba's damage and a window that then closes immediately with nothing in it.
#// ⚠ THE TWO RESOURCE COUNTS ARE DIFFERENT ONES, which is the only way this section can exist. Boba's
#// Epic Action needs 6+ resources CONTROLLED, while paying a Plot cost needs them READY — so four of
#// the seven are exhausted: 7 controlled (Boba deploys) but capacity 3 (Cinta at 6 is out of reach).
#// An earlier draft used six ready resources and quietly measured nothing: cost 6 ≤ capacity 6 made the
#// window affordable after all, and this section failed as a green-looking false negative.
## GIVEN
CommonSetup: ngw/ngw/{myLeader:HMW_011;myBase:JTL_024;theirLeader:JTL_009;theirBase:JTL_031}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 1:SEC_172:1,2:SOR_046:1,4:SOR_046:0
WithP2SpaceArena: [SEC_171:1:0]
WithP1SpaceArena: [JTL_087:1:0]
## WHEN
- P2>DeployLeader
- P2>AnswerDecision:Pilot
## EXPECT
P2HASDECISION
P2DECISIONTOOLTIP:Divide_up_to_4_damage_among_units
