#// A Plot play whose When Played ATTACKS, resolved inside a leader deploy's trigger window, while the
#// defender owes a decision mid-combat.
#//
#// FOUND BY: the real-deck self-play sweep, 2026-09-13 (SWUSim/DevTools/rl/sweep_fixtures.sh). Fixture
#//   boba_lakecountry, e.g. ahsoka_blue v boba_lakecountry seed s009. 51 of the first 12k
#//   games froze here: the engine looped forever. A human playing that deck can hit it too.
#//
#// THE SHAPE (all four are needed):
#//   1. JTL_009 Boba Fett deploys as a Pilot. That puts TWO triggers in one window: his "When deployed as an
#//      upgrade: deal up to 4 damage divided…" and the Plot window (CR 19.a / 7.6.9 — the controller orders
#//      them; see keywords/Plot_TriggerOrdering.md).
#//   2. The Plot window resolves FIRST, so Boba's trigger is left on the stack.
#//   3. The Plotted SEC_172 Cinta Kaz's When Played attacks (a nested attack, inside the trigger window).
#//   4. The attacker's On Attack hands the DEFENDER a decision mid-combat: JTL_237 TIE Bomber, "On Attack:
#//      Deal 3 indirect damage to the defending player", so P1 must assign it.
#//
#// THE LOOP (traced on the sweep's game 1699). P1's indirect assignment blocks, so the COMBAT resume hops
#// onto P1's queue to wait for it (correct). The OUTER bare resume — the one that will resolve Boba's leftover
#// trigger — defers behind "a pending COMBAT resume" (bug #976d's deferral in SWU_TRIGGER_RESUME) by
#// re-queuing itself on the ATTACKER's queue. But the combat resume is on P1's queue, not there: the bare
#// resume re-fires at once, sees the combat resume still pending, re-queues, and spins forever.
#//
#// CONFIRMED IN LIVE PLAY by the owner (2026-09-13): deploy Boba as a Pilot → Plot Cinta before Boba's
#// "When deployed as an upgrade" → attack the base. P1's window then shows "waiting for opponent" instead of the
#// indirect assignment, and P2 is asked to choose the attack target AGAIN. That is the loop seen through HTTP:
#// the request spins until PHP's 30 s limit kills it, nothing is saved, and the game rewinds to the last saved
#// state — P2's pending target choice. Every retry repeats it, so to a player the game just ignores the click.
#//
#// The two CONTROL cases each drop one ingredient and should stay green; only the full shape loops.
#// TIE Bomber 0/4 + Boba as a Pilot +4/+4 → attacks for 4. P1 puts the 3 indirect on its base.
#//
# PlotFirst_BomberAttack_DefenderAssignsIndirect_NoSpin
#// The whole shape: Plot first, Cinta attacks with the Boba-piloted Bomber, P1 assigns the indirect.
#//   Expected per the rules: the attack resolves (4 combat + 3 indirect on P1's base = 7), then Boba's
#//   leftover trigger comes up for P2. FIXED 2026-09-13: the deferred bare resume now waits on the queue that
#//   HOLDS the combat resume (SWU_TRIGGER_RESUME, GameLogic.php). _SWUResumeSpinGuard turns any recurrence
#//   into an immediate "Trigger resolution looped" failure instead of a hang (verified by reverting the fix).
## GIVEN
CommonSetup: ngw/ngw/{myLeader:HMW_011;myBase:JTL_024;theirLeader:JTL_009;theirBase:JTL_031}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 1:SEC_172:1,7:SOR_046:1
WithP2SpaceArena: [JTL_237:1:0]
WithP1SpaceArena: [JTL_087:1:0]
## WHEN
- P2>DeployLeader
- P2>AnswerDecision:Pilot
#// EffectStack-0 is the Plot window, EffectStack-1 is Boba (keywords/Plot_TriggerOrdering.md).
- P2>AnswerDecision:EffectStack-0
- P2>AnswerDecision:myResources-0
- P2>AnswerDecision:mySpaceArena-0
- P2>AnswerDecision:theirBase-0
- P1>AnswerDecision:myBase-0:3
## EXPECT
P1BASEDMG:7
P2HASDECISION
P2DECISIONTOOLTIP:Divide_up_to_4_damage_among_units

---

# CONTROL_BobaFirst_NoLeftoverTrigger_Resolves
#// Drops ingredient 2: Boba's own trigger resolves FIRST (1 damage to P1's TIE Ambush Squadron), so nothing
#//   is left on the stack when Cinta's attack happens. Same attack, same indirect assignment.
## GIVEN
CommonSetup: ngw/ngw/{myLeader:HMW_011;myBase:JTL_024;theirLeader:JTL_009;theirBase:JTL_031}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 1:SEC_172:1,7:SOR_046:1
WithP2SpaceArena: [JTL_237:1:0]
WithP1SpaceArena: [JTL_087:1:0]
## WHEN
- P2>DeployLeader
- P2>AnswerDecision:Pilot
- P2>AnswerDecision:EffectStack-1
- P2>AnswerDecision:theirSpaceArena-0:1
- P2>AnswerDecision:myResources-0
- P2>AnswerDecision:mySpaceArena-0
- P2>AnswerDecision:theirBase-0
- P1>AnswerDecision:myBase-0:3
## EXPECT
P1BASEDMG:7
P1SPACEARENAUNIT:0:DAMAGE:1

---

# CONTROL_PlotFirst_MarineAttacks_NoMidCombatDecision_Resolves
#// Drops ingredient 4: Plot first (Boba's trigger left over), but Cinta attacks with a vanilla Battlefield
#//   Marine (SOR_095, 3 power), whose attack hands P1 no decision. Expected: 3 to P1's base, then Boba's
#//   leftover trigger for P2.
## GIVEN
CommonSetup: ngw/ngw/{myLeader:HMW_011;myBase:JTL_024;theirLeader:JTL_009;theirBase:JTL_031}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 1:SEC_172:1,7:SOR_046:1
WithP2SpaceArena: [JTL_237:1:0]
WithP2GroundArena: [SOR_095:1:0]
WithP1SpaceArena: [JTL_087:1:0]
## WHEN
- P2>DeployLeader
- P2>AnswerDecision:Pilot
- P2>AnswerDecision:EffectStack-0
- P2>AnswerDecision:myResources-0
- P2>AnswerDecision:myGroundArena-0
## EXPECT
P1BASEDMG:3
P2HASDECISION
P2DECISIONTOOLTIP:Divide_up_to_4_damage_among_units
