#// A Support attack is a NESTED ability: the triggers that fired alongside Support wait until the attack — and
#// everything triggered during it — has resolved.
#//   CR 7.5 Support, e.: "An attack resulting from Support resolves during the same turn the unit with Support
#//   entered play, as a nested ability. If the active player chooses to resolve Support before other
#//   simultaneous triggered abilities, those other triggered abilities are not resolved until after the attack
#//   resulting from Support and any abilities triggered during the attack are resolved." (Ambush: the same, 7.5 f.)
#//
#// FOUND BY: sweep run 5, retro #1 (2026-09-14): ORDER ASH_009:SupportOnAttack + LAW_037:OnAttack +
#//   SEC_099:SWU_PLOT_WINDOW (aggro_ahsoka_blue.normal_talzin_force.s047, round 5). Ahsoka deployed; P1 resolved
#//   Support first; Han Solo attacked; the STILL-PENDING Plot window was offered beside the attack's two On
#//   Attacks, the bot took it, and Jar Jar and Naboo Royal Starship were played MID-ATTACK (Jar Jar's +2/+2 landed
#//   on the attacking Han). A sibling shape in the same retro: Blue Leader's pending Ambush offered beside Mando's
#//   N-1 Support attack's On Attacks (aggro_ahsoka_yellow.control_dedra_colossus.s030, round 6).
#//
#// FIXED 2026-09-14 (section 1 was RED): the combat resume already scoped its prompts to the attack's own batch
#//   (bug #976d's batchStart), but FlushCombatTriggerBag's FIRST ordering prompt — and the cross-player
#//   SWU_TRIGGER_ORDER_CHOICE — were built from the whole top layer, so the outer batch's leftovers (the Plot window,
#//   Blue Leader's Ambush) were offered beside the attack's On Attacks. Both now pass batchStart to
#//   _SWUEffectStackTargetsForPlayer. Hot-swapped into the live tree while run 5 was playing (20:34:06 UTC, ~6,750
#//   games in): the first test of the no-maintenance-window update process.
#//
#// Cards: ASH_009 Ahsoka Tano (deployed: Support; On Attack: give a unit with less power than this unit +2/+0) ·
#//   SEC_111 Jar Jar Binks (Plot; When Played: another friendly unit +2/+2 for this phase) · ASH_248 Neel 1/4 ("On
#//   Attack: The next unit you play this phase with 1 or less power enters play ready"). P2 has no unit, so Neel's
#//   attack goes to the base. EffectStack-0 is the Plot window, EffectStack-1 Ahsoka's Support (the window is armed
#//   first in SWUDeployLeader).
#//
# SupportFirst_NeelsAttackOrdersOnlyItsOwnOnAttacks_NotThePlotWindow
#// After Support is chosen and Neel attacks, P1 orders Neel's On Attack and Ahsoka's granted one — and ONLY those.
#//   The Plot window (EffectStack-0, still pending from the deploy) must not be selectable until the attack is over.
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_111:1,7:SOR_095:1
WithP1GroundArena: ASH_248:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve
P1SELECTABLENOT:EffectStack-0

---

# SupportFirst_TheAttackEndsBeforeThePlotWindowOpens_JarJarIsNotPlayedMidAttack
#// The whole sequence in rules order: Neel's On Attack, then Ahsoka's granted one (no unit has less than Neel's 1
#//   power, so it asks nothing), combat damage (1 to the base) — and only THEN the Plot window. When it opens, Jar
#//   Jar is still in the resources. GREEN today: once Neel's own On Attack is taken, the engine happens to hold the
#//   window — the leak shows only in the offer (section 1), which the bot (and a player) can take.
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_111:1,7:SOR_095:1
WithP1GroundArena: ASH_248:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:myGroundArena-0
- P1>ResolveTrigger:OnAttack
## EXPECT
P2BASEDMG:1
P1GROUNDARENACOUNT:2
P1HASDECISION
P1DECISIONTOOLTIP:Play_a_Plot_card_from_your_resources

---

# CONTROL_PlotFirst_JarJarIsPlayedBeforeTheSupportAttack
#// The other order is legal and stays so: the Plot window first (Jar Jar played, +2/+2 on Neel — myGroundArena-0),
#//   then Support: Neel attacks, and his two On Attacks are ordered with nothing else pending.
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1:SEC_111:1,7:SOR_095:1
WithP1GroundArena: ASH_248:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:3
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve
