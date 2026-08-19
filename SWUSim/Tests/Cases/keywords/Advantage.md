# CreatedAndAttached_GivesPlusOnePlusZero
#// ASH_T02 Advantage token — "When attached unit's attack or defense ends: Defeat this upgrade."
#// Baseline: the token is a real upgrade subcard giving +1/+0.
#// COVERAGE: offer=Interleave_TokensGroupAsONEEntry… (the ordering pool asserted while pending) ·
#//           decline=N/A (the shed is mandatory; the only choice is defeat-one-vs-all, both branches
#//                 covered by the two Interleave_Resolve* sections) ·
#//           boundary=MultipleTokensOnADefender_AllShed (1 token vs 3) + DefeatONEAtATime (1 fire vs 3) ·
#//           control=BothSidesShed_EachControllerResolvesTHEIROwnTokens (each side's tokens are its own
#//                 controller's upgrades) · reqboundary=N/A (the shed resolves inside one combat; no
#//                 state is written before a decision and read behind it)

## GIVEN
CommonSetup: rrw/bbk/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_T02

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:3

---

# ShedsWhenTheAttachedUnitsATTACKEnds
#// ASH_T02 — the attacker side. Battlefield Marine (3/3) + one Advantage attacks at 4 power, kills the
#// 2/4 Trayus Acolyte, takes 2 back, and then sheds the token. One token with nothing else pending
#// resolves with no prompt at all.

## GIVEN
CommonSetup: rrw/bbk/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArena: SEC_028:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:POWER:3
P1NODECISION

---

# ShedsWhenTheAttachedUnitsDEFENSEEnds
#// ASH_T02 — the DEFENDER side, which is the half a shed-on-attack-only reading gets wrong. The token
#// says "attack OR defense ends", so a unit that never attacked still loses it.
#// P1's Trayus Acolyte (2/4) attacks P2's Battlefield Marine (3/3 + Advantage = 4/3): the Marine takes 2
#// and survives, deals 4 back and kills Trayus, then sheds.
#// ⚠ SWUSim sheds the defender's tokens SYNCHRONOUSLY rather than through the ordering bag — a
#// deliberate, documented choice (CombatLogic: there is no When-Defense-Ends window to order against, so
#// shedding all is equivalent to the "nothing else pending" branch of the attacker path). The observable
#// end state is what this asserts; the absence of a prompt is asserted by P1NODECISION.

## GIVEN
CommonSetup: bbk/rrw/{}
P1OnlyActions: true
WithP1GroundArena: SEC_028:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:ASH_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION

---

# MultipleTokensOnADefender_AllShed_NoPrompt
#// ASH_T02 — three tokens on a defender. Each is a separate "defeat this upgrade" event, and all three
#// must go. The Marine defends at 3+3 = 6 power and 3 HP.
#// This is the boundary partner for the single-token section above: a "defeat THE upgrade" reading that
#// only removes one would leave 2 here and is invisible with a single token.

## GIVEN
CommonSetup: bbk/rrw/{}
P1OnlyActions: true
WithP1GroundArena: SEC_028:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArenaUpgrade: 0:ASH_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:POWER:3
P1NODECISION

---

# Interleave_TokensGroupAsONEEntryBesideTheUnitsOwnAttackEndAbility
#// ASH_T02 — the grouped-trigger model. Three Advantage tokens plus the unit's OWN "When this unit
#// completes an attack" ability (SOR_146 Zeb Orrelios) are pending at once, and the player orders them.
#// The three tokens occupy ONE ordering entry, not three: the shed is a single bag slot that then offers
#// defeat-one-or-all. That is what makes the ordering prompt readable when a unit carries many tokens.
#// Left pending so the ordering pool itself is asserted.
#// Zeb is 5/5 and the tokens make him 8 power, so the 3/3 Battlefield Marine dies and his "if the
#// defender was defeated" condition is satisfied — both triggers are genuinely live.

## GIVEN
CommonSetup: rrw/bbk/{}
P1OnlyActions: true
WithP1GroundArena: SOR_146:1:0
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_028:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1DECISIONTOOLTIP:Choose_trigger_to_resolve
P1SELECTABLEEXACT:EffectStack-0&EffectStack-1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3

---

# Interleave_ResolveAllTokensAtOnce_ThenTheUnitsOwnAbility
#// ASH_T02 — choosing the Advantage entry offers "defeat one or all"; taking ALL sheds every token in one
#// step and the unit's own attack-end ability still resolves afterwards.
#// Note the picker only appears with 2+ tokens AND something else pending — with either absent the shed
#// auto-resolves (asserted by the no-prompt sections above).

## GIVEN
CommonSetup: rrw/bbk/{}
P1OnlyActions: true
WithP1GroundArena: SOR_146:1:0
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_028:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:Defeat_all_Advantage_tokens

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:5
P1DECISIONTOOLTIP:Deal_4_damage_to_a_ground_unit

---

# Interleave_ResolveOneTokenAtATime_ReturnsToTheOrderingPrompt
#// ASH_T02 — the other branch: "defeat 1" sheds a single token and the REMAINDER re-enters the ordering
#// bag, so the player can slot the unit's own ability between tokens. After one token the unit is at
#// 5+2 = 7 power and the ordering prompt is back with both entries.
#// This is the section that proves the re-entry: a "defeat 1" that simply dropped the rest would leave
#// UPGRADECOUNT 2 with no pending decision, and a "defeat 1" that shed everything would leave 0.

## GIVEN
CommonSetup: rrw/bbk/{}
P1OnlyActions: true
WithP1GroundArena: SOR_146:1:0
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_028:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:Defeat_1_Advantage_token

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:7
P1DECISIONTOOLTIP:Choose_trigger_to_resolve
#// ⚠ EffectStack-2, not -1: the re-queued shed is a NEW stack entry appended after the consumed one, so
#// the remaining tokens do not reuse the index they were first offered under.
P1SELECTABLEEXACT:EffectStack-0&EffectStack-2

---

# CONTROL_ShieldTokenDefeated_FiresTheUpgradeDefeatedObserver
#// The PASSING CONTROL for the section below — same board, same combat, same host, one token swapped.
#// A Shield token (SOR_T02) consumed by the surviving defender is a defeated friendly upgrade, so
#// ASH_161 Zeb Orrelios ("When a friendly upgrade is defeated: deal 1 damage to a base") fires once.
#// SEC_028 Trayus Acolyte (2/4) attacks: it deals 2 into the shield and takes 3+1 back, so the defender
#// SURVIVES and the only upgrade leaving play is the token — nothing else can explain the base damage.

## GIVEN
CommonSetup: rrw/bbk
WithP1GroundArena: [ASH_161:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: 1:SOR_T02
WithP2GroundArena: SEC_028:1:0
WithActivePlayer: 2

## WHEN
- P2>AttackGroundArena:0:1
- P1>AnswerDecision:theirBase-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
P2BASEDMG:1

---

# AdvantageTokensDefeated_MustFireTheUpgradeDefeatedObserver
#// ENGINE BUG FOUND AND FIXED (2026-08-18). This section was RED until the fix; it is the guard.
#//
#// An Advantage token is an UPGRADE, and shedding it at the end of an attack/defense DEFEATS it. Every
#// "when a friendly upgrade is defeated" observer must therefore fire — once per token.
#// Identical board to the CONTROL above, with the Shield swapped for three Advantage tokens. Before the
#// fix the tokens WERE defeated (UPGRADECOUNT:0 passed) while ASH_161 Zeb fired ZERO times (P2BASEDMG 0,
#// expected 3) — the tell that the removal and the observer had come apart.
#//
#// ROOT CAUSE (read from code, not inferred): `_SWUDefeatAllAdvantageTokens` (GameLogic.php:4107) and
#// `_SWUDefeatOneAdvantageToken` (GameLogic.php:4200) splice the subcard out of `$obj->Subcards`
#// directly and never called `_SWUOnUpgradeDefeated`. All EIGHT other leave-play paths do call it
#// (shield-consumed, upgrade-defeated-with-host, both capture paths, defeat-all-upgrades …) — the
#// Advantage shed is the one that was missed. Same family as the session-95 fix that added the observer
#// to the CAPTURE path.
#//
#// BLAST RADIUS — three cards observe a friendly upgrade defeat, and all three missed every Advantage shed:
#//   ASH_039 Baylan Skoll (the SWU_FRIENDLY_UPGRADE_DEFEATED phase flag)
#//   ASH_055 Blade of Talzin (return to hand from a Night host)
#//   ASH_161 Zeb Orrelios (deal 1 damage to a base)
#// ASH_161 Zeb GIVES 3 Advantage tokens when played, so Zeb + Advantage is a natural pairing that had
#// never fired. Note this was flagged once in session 94 and DEFERRED as "possibly deliberate" — it was
#// not; no test ever asserted the opposite.

## GIVEN
CommonSetup: rrw/bbk
WithP1GroundArena: [ASH_161:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: 1:ASH_T02
WithP1GroundArenaUpgrade: 1:ASH_T02
WithP1GroundArenaUpgrade: 1:ASH_T02
WithP2GroundArena: SEC_028:1:0
WithActivePlayer: 2

## WHEN
- P2>AttackGroundArena:0:1
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P2BASEDMG:3

---

# DefeatONEAtATime_AlsoFiresTheObserver_OncePerToken
#// The one-at-a-time branch of the same fix. "Defeat all" and "defeat 1" are SEPARATE functions
#// (_SWUDefeatAllAdvantageTokens / _SWUDefeatOneAdvantageToken) and both had the same omission, so a fix
#// applied to only one leaves the other silently wrong — this section is what tells them apart.
#// Board: ASH_161 Zeb (the observer) plus SOR_146 Zeb Orrelios carrying 3 Advantage. SOR_146 attacks at
#// 5+3 = 8 and kills the 3/3 Marine, so his own "when this unit completes an attack" ability AND the
#// Advantage shed are both pending — which is exactly the condition that offers defeat-one-or-all.
#// Taking "defeat 1" must fire the observer exactly ONCE (1 base damage), not zero and not three.

## GIVEN
CommonSetup: rrw/bbk
P1OnlyActions: true
WithP1GroundArena: ASH_161:1:0
WithP1GroundArena: SOR_146:1:0
WithP1GroundArenaUpgrade: 1:ASH_T02
WithP1GroundArenaUpgrade: 1:ASH_T02
WithP1GroundArenaUpgrade: 1:ASH_T02
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_028:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:Defeat_1_Advantage_token
- P1>AnswerDecision:theirBase-0

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
P2BASEDMG:1

---

# BothSidesShed_EachControllerResolvesTHEIROwnTokens
#// ASH_T02 — attacker and defender both carry Advantage, so both shed in the same combat. Each side's
#// tokens are ITS controller's upgrades, so each player's own observer fires for its own two tokens and
#// no others. A controller-attribution bug (firing the active player's observer for both piles) shows up
#// here as 2 base-damage prompts on one side and none on the other.
#// Both units are SOR_046 (3/7) at 5 power with their tokens, so each deals 5 and both SURVIVE at 5
#// damage — that matters, because a unit that dies sheds via the host-leaves-play path instead and this
#// section would silently stop testing the shed.
#// P1 (active) resolves its two first, then P2's own queue is drained for its two.
#// ⚠ This shape only became reachable with the 2026-08-18 observer fix — before it, both sides shed in
#// total silence.

## GIVEN
CommonSetup: rrw/rrw
P1OnlyActions: true
WithP1GroundArena: ASH_161:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 1:ASH_T02
WithP1GroundArenaUpgrade: 1:ASH_T02
WithP2GroundArena: ASH_161:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 1:ASH_T02
WithP2GroundArenaUpgrade: 1:ASH_T02

## WHEN
- P1>AttackGroundArena:1:1
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirBase-0
- P2>Drain
- P2>AnswerDecision:theirBase-0
- P2>AnswerDecision:theirBase-0

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P2GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:DAMAGE:5
P2GROUNDARENAUNIT:1:DAMAGE:5
P2BASEDMG:2
P1BASEDMG:2
