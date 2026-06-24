# ASH — Card Implementation Plan

267 cards total: 18 Leaders, 179 Units, 34 Events, 25 Upgrades, 8 Bases, 3 tokens. **218 needs-work**, 49 auto-wired (vanilla / keyword-only / base no-op / built tokens).

Core mechanics Support + the Advantage token were built before this plan (memory `swusim-support-mechanic`, `swusim-advantage-token`). Phases are autonomy-first: autonomous phases 1–8, then pair-programmed 9–10. Tags are starting estimates — the loop escalates on emergent forks.

### Already Done
ASH_019, ASH_020, ASH_021, ASH_022, ASH_023, ASH_024, ASH_025, ASH_026, ASH_029, ASH_048, ASH_061, ASH_069, ASH_074, ASH_076, ASH_095, ASH_096, ASH_106, ASH_117, ASH_121, ASH_126, ASH_129, ASH_130, ASH_131, ASH_141, ASH_143, ASH_145, ASH_152, ASH_154, ASH_164, ASH_166, ASH_173, ASH_175, ASH_190, ASH_192, ASH_193, ASH_201, ASH_213, ASH_215, ASH_222, ASH_225, ASH_239, ASH_242, ASH_244, ASH_249, ASH_252, ASH_256, ASH_261, ASH_T01, ASH_T02, ASH_028, ASH_047, ASH_053, ASH_058, ASH_063, ASH_079, ASH_080, ASH_091, ASH_092, ASH_111, ASH_119, ASH_124, ASH_134, ASH_140, ASH_257, ASH_044, ASH_144, ASH_146, ASH_157, ASH_158, ASH_167, ASH_169, ASH_176, ASH_178, ASH_180, ASH_182, ASH_184, ASH_191, ASH_197, ASH_204, ASH_205, ASH_218, ASH_221, ASH_227, ASH_231, ASH_238, ASH_251, ASH_254, ASH_264, ASH_033, ASH_036, ASH_037, ASH_046, ASH_050, ASH_059, ASH_072, ASH_099, ASH_101, ASH_156, ASH_168, ASH_189, ASH_202, ASH_203, ASH_209, ASH_223, ASH_241, ASH_253, ASH_031, ASH_032, ASH_034, ASH_035, ASH_041, ASH_051, ASH_052, ASH_054, ASH_056, ASH_060, ASH_064, ASH_065, ASH_066, ASH_068, ASH_071, ASH_073, ASH_077, ASH_081, ASH_082, ASH_085, ASH_086, ASH_088, ASH_089, ASH_102, ASH_112, ASH_114, ASH_127, ASH_132, ASH_136, ASH_137, ASH_138, ASH_139, ASH_147, ASH_151, ASH_153, ASH_160, ASH_170, ASH_174, ASH_179, ASH_181, ASH_183, ASH_186, ASH_187, ASH_188, ASH_194, ASH_198, ASH_206, ASH_210, ASH_214, ASH_216, ASH_219, ASH_228, ASH_233, ASH_234, ASH_248, ASH_255, ASH_258, ASH_259, ASH_027, ASH_030, ASH_040, ASH_049, ASH_057, ASH_075, ASH_078, ASH_093, ASH_098, ASH_100, ASH_104, ASH_105, ASH_108, ASH_113, ASH_115, ASH_120, ASH_122, ASH_125, ASH_150, ASH_177, ASH_207, ASH_237, ASH_240, ASH_243, ASH_262, ASH_263, ASH_109, ASH_118, ASH_123, ASH_142, ASH_217, ASH_245, ASH_045, ASH_087, ASH_097, ASH_103, ASH_107, ASH_110, ASH_116, ASH_133, ASH_162, ASH_163, ASH_172, ASH_185, ASH_220, ASH_226, ASH_246, ASH_247, ASH_250, ASH_260, ASH_038, ASH_042, ASH_043, ASH_067, ASH_083, ASH_165, ASH_171, ASH_199, ASH_200, ASH_232, ASH_236, ASH_T03, ASH_070, ASH_084, ASH_148, ASH_195, ASH_211, ASH_235, ASH_002, ASH_003, ASH_004, ASH_005, ASH_007, ASH_008, ASH_009, ASH_010, ASH_011, ASH_012, ASH_013, ASH_014, ASH_015, ASH_016, ASH_017, ASH_018, ASH_039, ASH_055, ASH_159, ASH_161, ASH_128, ASH_149, ASH_208, ASH_212, ASH_006, ASH_090, ASH_229, ASH_001, ASH_224, ASH_230, ASH_062, ASH_155, ASH_196, ASH_135, ASH_094

### Backlog status (updated this session)
**CLEARED (14):** ASH_039, ASH_055, ASH_159, ASH_161 (shared upgrade-defeated hook + regroup); ASH_128, ASH_149, ASH_208, ASH_212 (capture-from-discard, Advantage-no-shed, on-attach exhaust, first-non-unit aspect-waiver); ASH_006, ASH_090, ASH_229 (cross-player Advantage+Shielded-next, Reforge defeat→search→attach, peek-top play-any-type-free); ASH_001 (play upgrade from RESOURCES onto entered-this-phase host + ramp), ASH_224 (distribute Advantage → opponent searches 2×count for an event; enters-ready-while-Force-leader), ASH_230 (ability TRANSPLANT via the Support SUPPORT_GRANT marker — search top-3, discard a ground unit, attack gaining its abilities) — built the shared `_SWUOnUpgradeDefeated` observer (SWU_FRIENDLY_UPGRADE_DEFEATED phase flag + ASH_055 return-from-discard + ASH_161 deal-1-base) wired into ALL three upgrade-defeat paths (SWUDefeatUpgrade, _SWUDefeatAllUpgradesOn, SWUDiscardHostSubcards); plus `_SWUAsh159RegroupStart`.
**CLEARED — ASH_062** The Mandalorian (Shielded + interactive prevention): mirrored the SEC_101 Queen Amidala seam exactly. Combat path arms via `AddTrigger('ASH_062_PREVENT')` for whichever combatant (attacker OR defender, not ASH_062 itself) has a friendly ASH_062 with a Shield → `Ash062PreventTrigger` offers a YESNO under the combat-pause → on YES, `SWUConsumeShieldToken(provider)` + a one-shot `SWU_ASH062_PREVENT_{protectedUID}` marker consumed at all 6 combat-damage points via `_SWUConsumeAsh062Prevent`. Ability path: `SWUDealDamageToUnit` offers the same YESNO (deferring to `ASH062_PREVENT_ABILITY`, which re-applies with `skipPrevent` on decline / no-shield). Helpers `_SWUAsh062Provider` (finds a different friendly ASH_062 with a Shield) + `_SWUConsumeAsh062Prevent`. Indirect damage unpreventable for free (writes Damage directly). 5 tests (combat accept/decline, ability accept, no-shield guard, self-damage-uses-own-Shielded guard). 2246 passing.

**CLEARED — ASH_155** Grogu ("When you take the initiative: you may attack with a unit"): hooked in `SWUTakeInitiative` (one `SWUQueueMayChooseTarget` offer per controlled Grogu over ready units → `ASH_155#0` continuation → `BeginSWUAttack`). The bonus attack is out-of-sequence (the initiative pass already swapped the turn), so the continuation sets a one-shot `SWU_SUPPRESS_AFTERACTION` flag consumed in `SWUAfterAction` (skips the pass-reset + turn-swap, keeps the deferred-replacement/cleanup). The feared 5-combat-site conflict was a non-issue: the inline combat after-actions are already `_SWUInTriggerResumeMode()`-gated, so only the single terminal after-action needed suppression. 3 tests (accept+turn-integrity via a P2 follow-up action, decline, no-ready-unit fizzle).

**CLEARED — ASH_196 passive** Gorian Shard's Corsair ("Damage dealt by friendly Underworld cards is unpreventable"): helper `_SWUDamageUnpreventable($sourceObj)` (source is Underworld AND its controller has an in-play ASH_196). Prepended as a bypass branch at all 6 combat-damage chains (source = attacker for defender-damage / defender for counter-damage) — deals full power, skipping Shield consumption AND every prevention helper. Also added to `SWUDealDamageToUnit` keyed on the threaded `$sourceMzID`. The active half was already done. 4 tests (other-Underworld bypasses Shield, no-Corsair control, non-Underworld control, ASH_196 self-attack). NOTE: ability damage from Underworld *abilities* is unpreventable only where the caller threads a source — combat (the signature) is fully covered.

**CLEARED — ASH_135** The Darksaber (4 functional effects): the feared IsLeaderUnit override was a non-issue — `IsLeaderUnit` already has the JTL_001 derived-leader pattern, so an ASH_135-subcard check is a safe mirror (host stays a normal Unit for defeat/bounce). Mandalorian-trait grant via `_SWUUnitHasTrait` (SEC_156/LAW_111 pattern). Aspect provision via appending Darksaber-wearers' aspects in `PlayerAspects`. +4/+2 stats auto. 5 tests (stats, leader-unit via LAW_139, Mandalorian via ASH_113, aspect provision via off-aspect cost waiver + control). DEFERRED sub-part: the "Attach to a unique non-Vehicle unit" attach-restriction (a play-time host-selection legality guard, separate from the functional effects).

**CLEARED — ASH_094** Moff Jerjerrod (full faithful refactor, user-chosen): added a count-aware token batch funnel. Refactored `SWUCreateUnitToken` → raw `_SWUCreateOneToken` + a wrapper that creates 1 then offers the doubling; added `SWUCreateUnitTokens($player, $tokenID, $count)` (returns the created UIDs) that creates `$count` then offers ONCE. `_SWUMaybeOfferJerjerrodDouble` (gated on an in-play ASH_094, so the no-Jerjerrod path is byte-identical) queues a YESNO + `ASH_094_DOUBLE` continuation that defeats Jerjerrod and creates `$count` MORE (net 2×count). Migrated ~22 multi-token instruction sites (pairs + loops) to the batch funnel. The funnel takes an optional `$turnEffect` applied to each created token AND carried through the doubling continuation, so Jerjerrod-doubled tokens get their per-phase marker too (JTL_092 CANT_ATTACK_BASES, JTL_130 Sentinel) — the earlier "doubled tokens lack the marker" edge is FIXED. 5 tests (batch accept→4 Spy, batch decline, no-Jerjerrod control, single-token path via index-0 CARDID, and an extreme case: Jerjerrod+Scramble Fighters → 16 TIEs trade 1-for-death to kill a 10/10 + 5/6 ship pair, the doubled half proving it can't hit bases by redirecting base-aimed attacks to the last ship). 2263 passing. Remaining edge: a mixed-token-type single instruction offers per token type.

**BACKLOG: EMPTY — all of the original hardest 5 (ASH_062, ASH_094, ASH_135, ASH_155, ASH_196) are cleared.** ASH set is card-complete pending a `swusim-set-validation` pass. (Deferred sub-part on record: ASH_135's "Attach to a unique non-Vehicle unit" attach-restriction — a play-time host-selection legality guard, separate from the functional effects which are all done.)


## Phase 1 — Mandalorian token creators (autonomous)
- [x] **Batch 1.1 — ASH_028, ASH_047, ASH_053, ASH_058** — done (1976 passing; +reusable combat-defeat marker SWU_COMBATDEF_/gCombatDefeatByMz, ASH_047 on-upgrade-play host trigger, _SWUAsh053 budget-defeat loop)
  - ASH_028 Paz Vizsla: Sentinel / When Defeated: If this unit wasn't defeated by combat damage, create 2 Mandalorian tokens.
  - ASH_047 Gar Saxon: When you play an upgrade on this unit: You may create a Mandalorian token. Use this ability only once each round.
  - ASH_053 Pre Vizsla: When Played: Defeat any number of non-leader units with a total of 6 or less remaining HP. Create a Mandalorian token fo
  - ASH_058 Duchess\'s Protector: When Defeated: Create a Mandalorian token.
- [x] **Batch 1.2 — ASH_063, ASH_079, ASH_080, ASH_091** — done (1981 passing; ASH_063 field-presence granted whenDefeated via the defeat funnel, ASH_079 conditional Sentinel + friendly-defeated whenPlayed, ASH_091 event create+Sentinel)
  - ASH_063 Bo-Katan\'s Gauntlet: Restore 1 / Each other friendly non-token unit gains: "When Defeated: Create a Mandalorian token."
  - ASH_079 Koska Reeves: While you control a token unit, this unit gains Sentinel. / When Played: If a friendly unit was defeated this phase, cre
  - ASH_080 Covert Believers: When Defeated: Create a Mandalorian token.
  - ASH_091 Buy Time: Create a Mandalorian token and give it Sentinel for this phase. (Enemy units in its arena must attack a Sentinel when th
- [x] **Batch 1.3 — ASH_092, ASH_111, ASH_119, ASH_124** — done (1986 passing; per-phase SWU_BASE_ATTACKED flag for ASH_119 unit-action, ASH_124 space-unit + unique check, ASH_092 event). ⚠ register $unitAbilities AFTER its `=[]` init or it's wiped.
  - ASH_092 Foundling Rescue: You may defeat a unit with 2 or less remaining HP. / Create a Mandalorian token.
  - ASH_111 Children of the Watch: When Played: Create 2 Mandalorian tokens.
  - ASH_119 Greef Karga: Action [1 resource, Exhaust]: If your base was attacked this phase, create a Mandalorian token.
  - ASH_124 Protectorate Fighter: When Played: If you control a <uq> unit, create a Mandalorian token.
- [x] **Batch 1.4 — ASH_134, ASH_140, ASH_257** — done (1990 passing; ASH_134 granted-WD-via-upgrade subcard scan, ASH_257 modal heal/create event)
  - ASH_134 Warrior\'s Legacy: Attached unit gains: "When Defeated: Create a Mandalorian token."
  - ASH_140 Stronger Together: Create 2 Mandalorian tokens.
  - ASH_257 Choose Your Path: Choose one: / If you control a Force unit, heal 5 damage from your base. / If you control a Mandalorian unit, create a M

## Phase 2 — Advantage-token givers (autonomous)
- [~] **Batch 2.1 — ASH_044, ASH_144, ASH_146 done; ASH_039 DEFERRED** (1993 passing; ASH_144 attack-end base-hit observer, ASH_146 dual-window deal-1+Advantage-on-kill, ASH_044 heal-up-to-2+Advantage). ⚠ ASH_039 deferred — dual-window + needs a new per-phase "friendly upgrade defeated" flag.
  - ASH_039 Baylan Skoll: Overwhelm / When Played/When Attack Ends: If an enemy base was damaged this phase, give an Advantage token to a unit. If
  - ASH_044 Barriss Offee: When Played: Heal up to 2 damage from a unit. Give an Advantage token to it for each damage healed this way.
  - ASH_144 Vane\'s Snub Fighter: When a friendly unit's attack ends: If it dealt combat damage to a base, give an Advantage token to this unit.
  - ASH_146 Justifier: When Played/On Attack: You may deal 1 damage to a unit. If that unit is defeated this way, give an Advantage token to a 
- [~] **Batch 2.2 — ASH_157, ASH_158 done; ASH_149, ASH_159 DEFERRED** (1995 passing; ASH_157 onAttack give-another, ASH_158 self-dmg+3-Advantage). ⚠ ASH_149 (Advantage-tokens-lose-abilities + no-shed = modifies AdvantageShed), ASH_159 (regroup-start give to a unit — build the regroup-Advantage hook with ASH_227).
  - ASH_149 Eviscerator: Advantage tokens on friendly units lose all abilities. (They aren't defeated after combat.) / When Played/On Attack: Giv
  - ASH_157 Danger Squadron Wingmen: On Attack: You may give an Advantage token to another unit.
  - ASH_158 Han Solo: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / When Played: Deal 3 damage to th
  - ASH_159 Alphabet Squadron U-Wing: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) / When the regroup phase starts: Gi
- [~] **Batch 2.3 — ASH_167, ASH_169, ASH_176 done; ASH_161 DEFERRED** (1998 passing; ASH_167 dual-window may-give, ASH_169 on-draw Advantage incl. regroup, ASH_176 deal-3+3-Advantage-on-kill). ⚠ ASH_161 deferred — reactive 'when a friendly upgrade is defeated' (build with ASH_039's upgrade-defeat hook).
  - ASH_161 Zeb Orrelios: When Played: Give 3 Advantage tokens to another unit. / When a friendly upgrade is defeated: Deal 1 damage to a base.
  - ASH_167 Flarestar Attack Shuttle: When Played/When Defeated: You may give an Advantage token to a unit.
  - ASH_169 Axe Woves: When you draw 1 or more cards (including during the regroup phase): Give an Advantage token to this unit.
  - ASH_176 Imposing Scout Walker: When Played: You may deal 3 damage to a ground unit. If it's defeated this way, give 3 Advantage tokens to this unit.
- [x] **Batch 2.4 — ASH_178, ASH_180, ASH_182, ASH_184** — done (2002 passing; ASH_178 advantage-per-enemy WhenPlayed, ASH_180 onAttackEndFromUpgrade grant + non-Vehicle attach, ASH_182 advantage-per-non-Advantage-upgrade WhenPlayed, ASH_184 event attack + post-attack ASH_184 marker → Ash184GiveAdvTrigger gives 3 Advantage regardless of attacker survival)
  - ASH_178 Knobby White Ice Spider: Hidden (This unit can't be attacked if it was played this phase.) / When Played: For each enemy unit, give an Advantage 
  - ASH_180 Bokken Saber: Attach to a non-Vehicle unit. / Attached unit gains: "When Attack Ends: Give an Advantage token to this unit."
  - ASH_182 Unfettered Ambition: When Played: For each upgrade on attached unit not named Advantage (including this one), give an Advantage token to atta
  - ASH_184 Follow Me: Attack with a unit. After completing the attack, give 3 Advantage tokens to a unit.
- [x] **Batch 2.5 — ASH_191, ASH_197, ASH_204, ASH_205** — done (2007 passing; ASH_191 WhenDefeated combat→2/non-combat→3 Advantage via gCombatDefeatByMz, ASH_197 +1/+0-per-upgrade-on-others passive + WhenPlayed Advantage to each other friendly, ASH_204 base-damage reaction _SWUCollectAsh204Reaction inline in SWUDealDamageToBase, ASH_205 MZMULTICHOOSE Advantage to up to 3 exhausted units)
  - ASH_191 Shin Hati\'s Fiend Fighter: When Defeated: You may give 2 Advantage tokens to a unit. If this unit wasn't defeated by combat damage, you may give 3 
  - ASH_197 Executor: This unit gets +1/+0 for each upgrade on other friendly units. / When Played: Give an Advantage token to each other frie
  - ASH_204 Blade Three: When your base is dealt damage: Give an Advantage token to this unit.
  - ASH_205 Inspiring Veteran: When Played: Give an Advantage token to each of up to 3 exhausted units.
- [x] **Batch 2.6 — ASH_218, ASH_221, ASH_227, ASH_231** — done (2012 passing; ASH_218 WhenPlayed 4-Advantage-self, ASH_221 conditional Shield-vs-2-Advantage on opp-space-unit, ASH_227 regroup-start Advantage hook _SWUAsh227RegroupStart in RegroupPhaseStart [also unblocks ASH_159], ASH_231 event exhaust-friendly+enemy then 2-Advantage-to-friendly)
  - ASH_218 Ferry Droid: When Played: Give 4 Advantage tokens to this unit.
  - ASH_221 Helix Starfighter: When Played: If an opponent controls a space unit, give a Shield token to this unit. Otherwise, give 2 Advantage tokens 
  - ASH_227 Heightened Awareness: Attached unit gains: "When the regroup phase starts: Give an Advantage token to this unit."
  - ASH_231 Diplomatic Pageantry: Exhaust a friendly unit and an enemy unit. If you do, give 2 Advantage tokens to that friendly unit.
- [x] **Batch 2.7 — ASH_238, ASH_251, ASH_254, ASH_264** — done (2016 passing; ASH_238 may-give-2-to-space-unit WhenPlayed, ASH_251 1-Advantage-self WhenPlayed, ASH_254 WhenDefeated 2-Advantage-to-friendly, ASH_264 event MZMULTICHOOSE 1-Advantage-to-up-to-2-units)
  - ASH_238 Attendant Navigator: When Played: You may give 2 Advantage tokens to a space unit.
  - ASH_251 Zealous Soldier: When Played: Give an Advantage token to this unit.
  - ASH_254 Gallofree Transport: When Defeated: Give 2 Advantage tokens to a friendly unit.
  - ASH_264 A New Order: Give an Advantage token to each of up to 2 units.

## Phase 3 — Support cards — triggered/constant other-abilities (autonomous)
- [x] **Batch 3.1 — ASH_033, ASH_036, ASH_037, ASH_046** — done (2021 passing; built the shared Support-passive seam: SWU_LAST_DEFENDER_DEFEATED var [OnAttackEnd "if defender defeated" — own+lent] + _SWUAttackerGrants helper [own CardID or SUPPORT_GRANT]. ASH_033 ready-self, ASH_036 may-give-3-Advantage, ASH_037 cross-arena via SWUGetValidAttackTargets, ASH_046 defender -1/-1 STAT_DEBUFF. Lent-passive graft validated.)
  - ASH_033 Grand Admiral Thrawn: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack
  - ASH_036 Rukh: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack
  - ASH_037 Red Leader: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack
  - ASH_046 Scion Shuttle: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack
- [x] **Batch 3.2 — ASH_050, ASH_059, ASH_072, ASH_099** — done (2026 passing; ASH_050 WhenDefeated may -2/-2 a unit, ASH_059 OnAttack self-dmg-1+heal-2-base, ASH_072 OnAttack draw if 3+ remaining HP, ASH_099 OnAttack gains Sentinel this phase. On-Attack ones lent via Support automatically.)
  - ASH_050 Morgan Elsbeth: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack
  - ASH_059 Leia Organa: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack
  - ASH_072 Doctor Pershing: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack
  - ASH_099 Gozanti Assault Carrier: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack
- [x] **Batch 3.3 — ASH_101, ASH_156, ASH_168, ASH_189** — done (2029 passing; ASH_101 combat-hit defeat-the-damaged-non-leader-defender via SWUCollectCombatHitTriggers (support-aware, reuses RukhDefeatTrigger), ASH_156 OnAttack defeat-all-upgrades-on-defender (_SWUDefeatAllUpgradesOn), ASH_189 OnAttack ready-a-resource. ASH_168 already done.)
  - ASH_101 The Great Mothers: Support / When Attack Ends: If this unit dealt combat damage to 1 or more non-leader units, defeat those units.
  - ASH_156 R5-D4: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack
  - ASH_168 Migs Mayfeld: Support / On Attack: Deal 1 damage to the defending unit. If this unit is upgraded, deal 2 damage to the defending unit 
  - ASH_189 Emperor\'s Messenger: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack
- [x] **Batch 3.4 — ASH_202, ASH_203, ASH_209, ASH_223** — done (2035 passing; ASH_202 deal-first via $hasShootFirst (support-aware), ASH_203 OnAttack may-exhaust-leader→+2/+0, ASH_209 OnAttack-if-upgraded may -3/-0, ASH_223 OnAttackEnd shield-on-kill. ASH_223 OnAttackEnd stub added in 3.1.)
  - ASH_202 Carson Teva: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack
  - ASH_203 Mando\'s N-1 Starfighter: Support / On Attack: You may exhaust a friendly (non-upgrade) leader. If you do, this unit gets +2/+0 for this attack.
  - ASH_209 Ezra Bridger: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack
  - ASH_223 Halo: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack
- [x] **Batch 3.5 — ASH_241, ASH_253** — done (2039 passing; ASH_241 +2/+0-vs-damaged combat buff (support-aware; Overwhelm auto-lent), ASH_253 OnAttack-if-upgraded deal-2-to-a-base. Phase 3 Support cards COMPLETE.)
  - ASH_241 Marrok\'s Fiend Fighter: Support / Overwhelm / This unit gets +2/+0 while attacking a damaged unit.
  - ASH_253 Yellow Aces Bomber: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack

## Phase 4 — Triggered effects (damage / heal / exhaust / ready / tokens) (autonomous)
- [x] **Batch 4.1 — ASH_031, ASH_032, ASH_034, ASH_035** — done (2042 passing; ASH_031 heal-base-on-base-hit (CollectAfterAttackTriggers + baseCombatDmg), ASH_032 reactive deal-1-to-any-bases on friendly-damaged-survives once/round (_SWUOnUnitDamaged observer, SEC_002 pattern), ASH_034 Saboteur+can't-attack-bases (noBases mirror of SOR_072 — UI-list rider, no regression test), ASH_035 can't-be-attacked-while-2+-exhausted (UI-list) + OnAttack deal-2-per-exhausted)
  - ASH_031 Hera Syndulla: When Attack Ends: If this unit dealt combat damage to a base, heal that much damage from your base.
  - ASH_032 Rancor Keeper: When a friendly unit is dealt damage and survives: Deal 1 damage to any number of bases. Use this ability only once each
  - ASH_034 Wicket: Saboteur / This unit can't attack bases.
  - ASH_035 Tatooine Repulsor Train: This unit can't be attacked while you control 2 or more exhausted units (unless it gains Sentinel). / On Attack: Deal 2 
- [x] **Batch 4.2 — ASH_041, ASH_051, ASH_052, ASH_054** — done (2047 passing; ASH_041 entering-friendly +1/+0 via CollectEntryTriggers, ASH_051 Restore1+WhenPlayed may-exhaust, ASH_052 WhenPlayed defeat-friendly+enemy + enemy-defeated heal-2 observer, ASH_054 -3/-0-vs-base combat debuff + enemy-attachable Condition)
  - ASH_041 Outcast: When a friendly unit enters play (including this one): It gets +1/+0 for this phase.
  - ASH_051 Reinforcing Light Cruiser: Restore 1 (When this unit attacks, heal 1 damage from your base.) / When Played: You may exhaust a unit.
  - ASH_052 Chimaera: When Played: You may choose a friendly unit and an enemy non-leader unit. If you do, defeat those units. / When an enemy
  - ASH_054 Pointless to Resist: Attached unit gets -3/-0 while attacking a base.
- [x] **Batch 4.3 — ASH_056, ASH_060, ASH_064, ASH_065** — done (2052 passing; ASH_056 OnAttack may -4/-0 upgraded unit, ASH_060 own-play reaction deal-2-self→Shield-played-unit (CobbVanthReaction, carries cobbUID+playedUID), ASH_064 WhenPlayed Shield-each-Shielded, ASH_065 WhenPlayed heal-all-friendly)
  - ASH_056 Huyang: On Attack: You may give an upgraded unit -4/-0 for this phase.
  - ASH_060 Cobb Vanth: Grit / When you play another unit: You may deal 2 damage to this unit. If you do, give a Shield token to that unit.
  - ASH_064 The Armorer: Shielded (When you play this unit, give a Shield token to her.) / When Played: Give a Shield token to each friendly unit
  - ASH_065 Home One: Sentinel (Enemy units in this arena must attack a Sentinel when they attack you.) / When Played: Heal all damage from ea
- [x] **Batch 4.4 — ASH_066, ASH_068, ASH_071, ASH_073** — done (2056 passing; ASH_066 conditional Sentinel for Luke + non-Vehicle attach, ASH_068 field-presence AMBUSH/SUPPORT suppression on enemy units via SWUKeywordSuppressed, ASH_071 WhenPlayed self-1+enemy-space-1, ASH_073 +2/+0-while-defending counter buff)
  - ASH_066 Luke\'s Jedi Lightsaber: Attach to a non-Vehicle unit. / If attached unit is Luke Skywalker, he gains Sentinel. (Enemy units in this arena must a
  - ASH_068 Domesticated Loth-Cat: Enemy units lose Ambush and Support.
  - ASH_071 Battered Haulcraft: When Played: Deal 1 damage to this unit and 1 damage to an enemy space unit.
  - ASH_073 Palace Chef Droid: Sentinel (Enemy units in this arena must attack a Sentinel when they attack you.) / This unit gets +2/+0 while defending
- [x] **Batch 4.5 — ASH_077, ASH_081, ASH_082, ASH_085** — done (0 failed; ASH_077 name-card play-block (SOR_062 clone, UID-keyed SWU_NAMEBLOCK), ASH_081 WhenPlayed may-heal-3-unit-or-base, ASH_082 Grit+WhenPlayed Shield-a-≤3-cost-unit, ASH_085 onAttackEndFromUpgrade deal-4-to-host+self-defeat (_SWUDefeatNamedUpgrade) + enemy-attachable Condition)
  - ASH_077 Ryder Azadi: Restore 1 / When Played: Name a card. While this unit is in play, opponents can't play cards with that name.
  - ASH_081 Nebulon-C Frigate: When Played: You may heal 3 damage from a unit or base.
  - ASH_082 Trexler Armored Marauder: Grit (This unit gets +1/+0 for each damage on it.) / When Played: You may give a Shield token to a unit that costs 3 or 
  - ASH_085 Grav Charge: When attached unit's attack ends: Deal 4 damage to it and defeat this upgrade.
- [x] **Batch 4.6 — ASH_086, ASH_088, ASH_089, ASH_102** — done (2069 passing; ASH_086 upgrade WhenPlayed shield-host, ASH_088 regroup ready-step pay-3-or-exhaust (clone of JTL_192), ASH_089 event heal-3+shield-a-unit, ASH_102 Restore2+own-play-reaction played-unit-deals-its-power-in-same-arena (RavagerReaction))
  - ASH_086 Durasteel Plating: When Played: Give a Shield token to attached unit.
  - ASH_088 The Conflict Within: Attached unit gains: "When this unit readies: You may pay 3 resources. If you don't, exhaust this unit."
  - ASH_089 Perseverance: Heal 3 damage from a unit and give a Shield token to it.
  - ASH_102 Ravager: Restore 2 / When you play a unit: You may have it deal damage equal to its power to a unit in the same arena.
- [x] **Batch 4.7 — ASH_112, ASH_114, ASH_127, ASH_132** — done (2075 passing; ASH_112 WhenPlayed AoE-3-if-4+-units, ASH_114 conditional Restore-2-for-Sabine/Force + non-Vehicle attach, ASH_127 WhenPlayed/OnAttack grant-Sentinel + friendly-defeated heal-1, ASH_132 WhenPlayed/OnAttack reveal-hand-unit→deal-3-to-same-cost-unit)
  - ASH_112 Luke Skywalker: Restore 1 / When Played: If you control at least 4 units, deal 3 damage to each enemy unit.
  - ASH_114 Sabine\'s Lightsaber: Attach to a non-Vehicle unit. / If attached unit is Sabine Wren or a Force unit, it gains Restore 2.
  - ASH_127 The Twins: When Played/On Attack: You may give another friendly unit Sentinel for this phase. / When another friendly unit is defea
  - ASH_132 Queen Soruna: When Played/On Attack: You may reveal a unit from your hand. If you do, deal 3 damage to a unit with the same cost as th
- [~] **Batch 4.8 — ASH_136, ASH_137, ASH_138 done; ASH_135 DEFERRED** (2078 passing; ASH_136 event +3/+3, ASH_137 event attack+excess-to-another-unit-in-arena (marker → Ash137ExcessTrigger, SOR_088 pattern), ASH_138 event deal-1-per-friendly-unit. ⚠ ASH_135 The Darksaber DEFERRED — Hard: "attached unit IS a leader unit + gains Mandalorian + provides its aspect icons while paying costs" — 3 unusual mechanics (leader-unit conversion, aspect-icon-provider for cost payment).)
  - ASH_135 The Darksaber: Attach to a <uq> non-Vehicle unit. / Attached unit is a leader unit and gains the Mandalorian trait. / Attached unit gai
  - ASH_136 Display of Strength: Give a unit +3/+3 for this phase.
  - ASH_137 Wipe Them Out: Attack with a unit. For this attack, you may deal its excess damage to another unit in the same arena.
  - ASH_138 Turning the Tide: Choose a unit. Deal 1 damage to it for each friendly unit.
- [x] **Batch 4.9 — ASH_139, ASH_147, ASH_151, ASH_153** — done (2083 passing; ASH_139 event split-power-among-arena-units (MZSPLITASSIGN→SPLIT_DAMAGE), ASH_147 Grit+WhenPlayed 5-to-damaged/2-to-undamaged-ground-unit, ASH_151 event 5-to-own-base+5-to-each-unit, ASH_153 WhenDefeated may deal 2)
  - ASH_139 Hold Them Off: Choose a friendly unit. That unit deals damage equal to its power divided as you choose among any number of units in its
  - ASH_147 The Cyborg Mech: Grit (This unit gets +1/+0 for each damage on it.) / When Played: Either deal 2 damage to an undamaged ground unit or 5 
  - ASH_151 Operation Cinder: Deal 5 damage to your base. Then, deal 5 damage to each unit.
  - ASH_153 Green Leader: When Defeated: You may deal 2 damage to a unit.
- [~] **Batch 4.10 — ASH_160, ASH_170, ASH_174 done; ASH_155 DEFERRED** (2086 passing; ASH_160 ready-on-enemy-ground-base-attack once/round (_SWUAsh160ReadyOnBaseAttack in ExecuteSWUAttack), ASH_170 WhenPlayed may deal-2-upgraded-ground, ASH_174 WhenPlayed may deal-6-non-unique-ground. ⚠ ASH_155 Grogu DEFERRED — "When you take the initiative: you may attack with a unit" — the bonus attack's after-action conflicts with SWUTakeInitiative's SWUPassAction; needs careful extra-action handling.)
  - ASH_155 Grogu: When you take the initiative: You may attack with a unit.
  - ASH_160 Kachirho Militia: Hidden (This unit can't be attacked if it was played this phase.) / When an enemy ground unit attacks your base: Ready t
  - ASH_170 Desert Sharpshooter: When Played: You may deal 2 damage to an upgraded ground unit.
  - ASH_174 StarFortress Heavy Bomber: When Played: You may deal 6 damage to a non-<uq> ground unit.
- [x] **Batch 4.11 — ASH_179, ASH_181, ASH_183, ASH_186** — done (2091 passing; ASH_179 WhenPlayed 5-base+5+5-enemy-ground + OnAttack deal-N-base-per-5-own-base-dmg, ASH_181 Overwhelm grant + attach-to-damaged, ASH_183 onAttackEndFromUpgrade AoE-2-on-base-hit (SWU_LAST_ATTACKER_BASEHIT var) + non-Vehicle, ASH_186 event arena-wide granted On-Attack-self-damage marker)
  - ASH_179 Boba Fett\'s Rancor: When Played: Deal 5 damage to your base. Then, deal 5 damage to an enemy ground unit. Then, deal 5 damage to the same un
  - ASH_181 Mark My Words: Attach to a damaged unit. / Attached unit gains Overwhelm. (When attacking an enemy unit, deal excess damage to the oppo
  - ASH_183 Whistling Birds: Attach to a non-Vehicle unit. / Attached unit gains: "When Attack Ends: If this unit dealt combat damage to an opponent'
  - ASH_186 Treacherous Minefield: Choose an arena. For this phase, each unit in that arena gains: "On Attack: Deal 2 damage to this unit."
- [~] **Batch 4.12 — ASH_187, ASH_188, ASH_194 done; ASH_196 PARTIAL** (2095 passing; ASH_187 event deal-total-friendly-damage, ASH_188 event ready-a-unit-damaged-this-phase (new SWU_DAMAGED_PHASE marker in _SWUOnUnitDamaged), ASH_194 Ambush+WhenPlayed deal-1-space-unit. ⚠ ASH_196 active WhenPlayed/OnAttack may-deal-2 DONE; its "friendly Underworld damage is unpreventable" passive DEFERRED — cross-cutting source-trait shield-bypass at every damage point.)
  - ASH_187 Reckoning: Deal damage to a unit equal to the total amount of damage on all units you control.
  - ASH_188 Galvanized Leap: Ready a unit that was damaged this phase.
  - ASH_194 Snub Fighter Squadron: Ambush (When you play this unit, it may attack an enemy unit.) / When Played: Deal 1 damage to a space unit.
  - ASH_196 Gorian Shard\'s Corsair: Damage dealt by friendly Underworld cards is unpreventable. / When Played/On Attack: You may deal 2 damage to a unit.
- [~] **Batch 4.13 — ASH_198, ASH_206, ASH_210 done; ASH_208 DEFERRED** (2098 passing; ASH_198 conditional Sentinel grant + any-unit attach, ASH_206 +1/+0-per-other-0-power-unit passive, ASH_210 onDefenseFromUpgrade may deal-1+exhaust-arena-unit (combat-pause) + non-Vehicle. ⚠ ASH_208 Sabine Wren DEFERRED — "when 1+ upgrades attach to this unit (including from Shielded): may exhaust a ground unit" — multi-site upgrade-attach reactive (real upgrades + DoGiveShieldToken) with re-entrancy risk.)
  - ASH_198 Nowhere to Hide: Attached unit gains Sentinel. (Enemy units in this arena must attack a Sentinel when they attack you.)
  - ASH_206 Kelleran Beq: Ambush / This unit gets +1/+0 for each other unit (friendly and enemy) with 0 power.
  - ASH_208 Sabine Wren: Shielded (When you play this unit, give a Shield token to her.) / When 1 or more upgrades attach to this unit (including
  - ASH_210 DDC Defender: Attach to a non-Vehicle unit. / Attached unit gains: "On Defense: You may deal 1 damage to a unit in this unit's arena a
- [~] **Batch 4.14 — ASH_214, ASH_216, ASH_219 done; ASH_212 PARTIAL** (2101 passing; ASH_214 WhenPlayed may-exhaust-unit-with-a-keyword (_SWUUnitHasAnyKeyword), ASH_216 WhenDefeated exhaust-ready-resource, ASH_219 Sentinel+WhenPlayed may-pay-4→exhaust-each-unit-in-an-arena. ⚠ ASH_212 Peli Motto: Shielded works (auto); "ignore aspect penalties of the first non-unit card each phase" passive DEFERRED — needs a glow-safe first-non-unit flag in ActivateCard.)
  - ASH_212 Peli Motto: Shielded / Ignore the aspect penalties of the first non-unit card you play each phase.
  - ASH_214 Amnesty Officer: When Played: You may exhaust a unit with one or more keywords.
  - ASH_216 Mandalorian Scout: When Defeated: Exhaust a ready friendly resource.
  - ASH_219 Jod Na Nawood: Sentinel / When Played: You may pay 4 resources. If you do, choose an arena. Exhaust each unit in that arena.
- [x] **Batch 4.15 — ASH_228, ASH_233, ASH_234, ASH_248** — done (2105 passing; ASH_228 upgrade WhenPlayed exhaust-host, ASH_233 event exhaust-up-to-2-≤3-cost, ASH_234 event attack+bonus-per-enemy-unit-in-arena, ASH_248 next-≤1-power-unit-enters-ready (SWU_ASH248_READY flag in ActivateCard))
  - ASH_228 Preparation: When Played: Exhaust attached unit.
  - ASH_233 Keep Them Talking: Exhaust up to 2 units that each cost 3 or less.
  - ASH_234 Masterstroke: Attack with a unit. It gets +1/+0 for this attack for each unit the defending player controls in its arena.
  - ASH_248 Neel: When Played/On Attack: The next unit you play this phase with 1 or less power enters play ready.
- [x] **Batch 4.16 — ASH_255, ASH_258, ASH_259** — done (2108 passing; ASH_255 Hidden+Saboteur+WhenPlayed shield-another-friendly, ASH_258 event deal-3+heal-3-base, ASH_259 WhenPlayed may deal-1-ground. Phase 4 COMPLETE.)
  - ASH_255 Anakin Skywalker: Hidden / Saboteur / When Played: Give a Shield token to another friendly unit.
  - ASH_258 Grassroots Resistance: Deal 3 damage to a unit. / Heal 3 damage from your base.
  - ASH_259 LEP Ratcatcher: When Played: You may deal 1 damage to a ground unit.

## Phase 5 — Passive & constant abilities (stat/keyword grants, cost mods) (autonomous)
- [x] **Batch 5.1 — ASH_027, ASH_030, ASH_040, ASH_049** — done (2113 passing; ASH_027 WhenDefeated NumberChoose-≤6-self-base→discount-next-unit (SWU_ASH027_DISCOUNT_NEXT count charges), ASH_030 Sentinel→Saboteur-while-upgraded (suppress+conditional), ASH_040 all-units-lose-Sentinel field suppression, ASH_049 solo-non-leader-ground Sentinel)
  - ASH_027 Enoch: When Defeated: You may deal up to 6 damage to your base. The next unit you play this phase costs 1 resource less for eve
  - ASH_030 Marrok: Sentinel / While this unit is upgraded, he loses Sentinel and gains Saboteur.
  - ASH_040 Poe Dameron: All units lose Sentinel.
  - ASH_049 Shin Hati: While this is the only friendly non-leader ground unit, she gains Sentinel. (Enemy units in this arena must attack a Sen
- [~] **Batch 5.2 — ASH_057, ASH_075, ASH_078 done; ASH_090 DEFERRED** (2116 passing; ASH_057 conditional Restore-2-while-enemy-upgraded, ASH_075 first-upgrade-on-another-friendly -1 (SEC_064 mirror, host≠Pit-Droid consume), ASH_078 conditional Sentinel-while-control-ground. ⚠ ASH_090 Reforge DEFERRED — Hard: defeat-an-upgrade → search-top-8-for-an-upgrade-that-can-attach-to-that-unit → play-it-on-that-unit-at-4-less (deck search + conditional play on a specific host).)
  - ASH_057 Lothal E-Wing: While an enemy unit is upgraded, this unit gains Restore 2. (When this unit attacks, heal 2 damage from your base.)
  - ASH_075 Pit Droid Team: The first upgrade you play on another friendly unit each phase costs 1 resource less.
  - ASH_078 B-Wing Rearguard: While you control a ground unit, this unit gains Sentinel. (Enemy units in this arena must attack a Sentinel when they a
  - ASH_090 Reforge: Defeat an upgrade on a friendly unit. If you do, search the top 8 cards of your deck for an upgrade that can attach to t
- [x] **Batch 5.3 — ASH_093, ASH_098, ASH_100, ASH_104** — done (2120 passing; ASH_093 Raid-3-while-leader-defeated-this-phase (new SWU_LEADER_DEFEATED_PHASE flag), ASH_098 Ambush-while-control-another-non-unique, ASH_100 +2/+2-to-other-friendly-units-with-2+-keywords (_SWUCountDistinctKeywords/_SWUAsh100Bonus), ASH_104 -1-if-Force + play-up-to-3-cheap-non-Vehicle-from-discard-free)
  - ASH_093 Captain Pellaeon: While a leader unit has been defeated this phase, this unit gains Raid 3. (He gets +3/+0 while attacking.)
  - ASH_098 AT-ST Raider: While you control another non-<uq> unit, this unit gains Ambush. (When you play this unit, it may attack an enemy unit.)
  - ASH_100 Gallius Rax: Other friendly units with 2 or more different keywords get +2/+2.
  - ASH_104 Dathomiri Magicks: If you control a Force unit, this event costs 1 resource less to play. / Play up to 3 non-Vehicle units that each cost 2
- [x] **Batch 5.4 — ASH_105, ASH_108, ASH_113, ASH_115** — done (2124 passing; ASH_105 Raid-2-while-another-Mandalorian, ASH_108 WhenPlayed play-Heroism-unit-from-hand at -2-per-arena-majority (DISCOUNT_PLAY_FROM_HAND), ASH_113 Ambush-while-leader-unit + power-per-other-Mandalorian, ASH_115 event buff-friendly-+N-per-weaker-friendly)
  - ASH_105 Bo-Katan Kryze: While you control another Mandalorian unit, this unit gains Raid 2.
  - ASH_108 Crix Madine: When Played: You may play a Heroism unit from your hand. It costs 2 resources less for each arena in which you control t
  - ASH_113 Mandalorian Flagship: While you control a leader unit, this unit gains Ambush. (When you play this unit, it may attack an enemy unit.) / This 
  - ASH_115 The Student Guides the Master: Give a friendly unit +1/+0 for this phase for each other friendly unit with less power than it.
- [x] **Batch 5.5 — ASH_120, ASH_122, ASH_125, ASH_150** — done (2128 passing; ASH_120 Sentinel-while-another-exhausted, ASH_122 Restore-2-while-initiative, ASH_125 Hidden + +2/+0-while-initiative, ASH_150 double-damage (in _SWUApplyDamagePrevention, covers combat+ability) + attacker-loses-Overwhelm-while-defending + any-unit attach)
  - ASH_120 Warrior of Clan Kryze: While you control another exhausted unit, this unit gains Sentinel. (Enemy units in this arena must attack a Sentinel wh
  - ASH_122 Consortium StarViper: While you have the initiative, this unit gains Restore 2. (When this unit attacks, heal 2 damage from your base.)
  - ASH_125 Stolen Eta Shuttle: Hidden (This unit can't be attacked if it was played this phase.) / While you have the initiative, this unit gets +2/+0.
  - ASH_150 Deadly Vulnerability: If attached unit would take damage, it takes twice as much damage instead. / While attached unit is defending, the attac
- [x] **Batch 5.6 — ASH_177, ASH_207, ASH_237, ASH_240** — done (2132 passing; ASH_177 grant-Hidden-to-other-friendly (HasConditionalKeyword_Hidden), ASH_207 +2/+0-while-attacking-using-Ambush (SWU_AMBUSH_ATTACK marker set in SWUAmbushAnswer), ASH_237 Raid1+next-Imperial-unit -1, ASH_240 +2/+0-while-control-leader-unit)
  - ASH_177 Onyx Cinder: Hidden / Other friendly units gain Hidden.
  - ASH_207 Heroic Purrgil: Ambush (When you play this unit, it may attack an enemy unit.) / While attacking using Ambush, this unit gets +2/+0.
  - ASH_237 Mouse Droid: Raid 1 (This unit gets +1/+0 while attacking.) / When Played: The next Imperial unit you play this phase costs 1 resourc
  - ASH_240 Mandalorian Super Commandos: While you control a leader unit, this unit gets +2/+0.
- [x] **Batch 5.7 — ASH_243, ASH_262, ASH_263** — done (2135 passing; ASH_243 Shielded + Sentinel-while-ready, ASH_262 upgrade -1-on-Imperial-host, ASH_263 upgrade -1-on-Mandalorian-host (LAW_129 host-trait pattern). Phase 5 COMPLETE.)
  - ASH_243 Darth Vader: Shielded / While this unit is ready, he gains Sentinel.
  - ASH_262 Faith in the Empire: This upgrade costs 1 resource less to play on an Imperial unit.
  - ASH_263 The Way of the Mand\'alor: This upgrade costs 1 resource less to play on a Mandalorian unit.

## Phase 6 — Activated abilities (Action [Exhaust]) (autonomous)
- [x] **Batch 6.1 — ASH_109, ASH_118, ASH_123, ASH_142** — done (2139 passing; unit Action [Exhaust] abilities via $unitAbilities + SWUUnitActionAffordable gates: ASH_109 buff-another-+2/+2+may-attack, ASH_118 Hidden+deal-1-friendly→search-top-5-unit-draw, ASH_123 deal-power-to-ground, ASH_142 deal-1-to-up-to-3-ground)
  - ASH_109 T-6 Shuttle 1974: Sentinel (Enemy units in this arena must attack a Sentinel when they attack you.) / Action [Exhaust]: Give another unit 
  - ASH_118 8D8: Hidden / Action [Exhaust]: Deal 1 damage to another friendly unit. If you do, search the top 5 cards of your deck for a 
  - ASH_123 Lang: Action [Exhaust]: This unit deals damage equal to his power to a ground unit.
  - ASH_142 Mortar Trooper: Action [Exhaust]: Deal 1 damage to each of up to 3 ground units.
- [x] **Batch 6.2 — ASH_217, ASH_245** — done (2141 passing; ASH_217 Action [Exhaust, discard a card]: exhaust-a-unit, ASH_245 Action [Exhaust]: search-top-8-for-unit-≤-power → play-free-enters-ready (LAW_074 clone). Phase 6 COMPLETE.)
  - ASH_217 Mayor\'s Majordomo: Action [Exhaust, discard a card from your hand]: Exhaust a unit.
  - ASH_245 Eye of Sion: Action [Exhaust]: Search the top 8 cards of your deck for a unit that costs the same as or less than this unit's power. 

## Phase 7 — Deck & hand interaction (draw / search / discard / peek) (autonomous)
- [~] **Batch 7.1 — ASH_045, ASH_087, ASH_097 done; ASH_055 DEFERRED** (2144 passing; ASH_045 WhenDefeated look-at-a-deck-top + may-discard, ASH_087 upgrade WhenPlayed draw-1, ASH_097 Sentinel+WhenDefeated return-non-unique-Imperial-from-discard. ⚠ ASH_055 Blade of Talzin DEFERRED — "When Defeated (the upgrade): if it was on a friendly Night unit, return it from discard to hand" — needs an upgrade-defeated hook with host-trait capture at the subcard-discard point.)
  - ASH_045 Reanimated Night Trooper: When Defeated: Look at the top card of a deck. You may discard it.
  - ASH_055 Blade of Talzin: Attach to a non-Vehicle unit. / When Defeated: If this upgrade was on a friendly Night unit, return this upgrade from yo
  - ASH_087 Cybernetic Enhancements: When Played: Draw a card.
  - ASH_097 Moff Gideon: Sentinel (Enemy units in this arena must attack a Sentinel when they attack you.) / When Defeated: You may return a non-
- [x] **Batch 7.2 — ASH_103, ASH_107, ASH_110, ASH_116** — done (2148 passing; ASH_103 event defeat-friendly-Imperial→resource-top, ASH_107 WhenPlayed search-top-5-trait-match-draw, ASH_110 WhenPlayed may-self-defeat→search-top-10-space-units-≤5-combined-play-free (DoTopDeckPlay), ASH_116 WhenDefeated draw-1)
  - ASH_103 Long Live the Empire: Defeat a friendly Imperial unit. If you do, resource the top card of your deck.
  - ASH_107 Clan Wren Loyalist: When Played: Search the top 5 cards of your deck for a card that shares a Trait with a unit you control, reveal it, and 
  - ASH_110 Admiral Ackbar: When Played: You may defeat this unit. If you do, search the top 10 cards of your deck for any number of space units wit
  - ASH_116 Ant Droid: When Defeated: Draw a card.
- [~] **Batch 7.3 — ASH_128, ASH_133, ASH_162, ASH_163** — DONE (2155 passing): ASH_133 whenPlayed/onAttack modal (bottom-of-deck+heal3 OR return-to-hand) via OPTIONCHOOSE; ASH_162 event = attack +1/+0 + ASH_162 attack-marker → opp discards on base hit (combatCtx ash162Discard in CollectAfterAttackTriggers); ASH_163 event = discard a hand unit then deal 5 to a unit costing strictly more. **ASH_128 DEFERRED** (capture-a-defeated-friendly-from-discard, reactive once/round — novel capture-from-discard).
  - ASH_128 Bothan-5: When another friendly non-Vehicle unit is defeated: You may have this unit capture that unit from your discard pile. Use
  - ASH_133 Trask Walker: When Played/On Attack: Choose a unit in your discard pile that costs 7 or less. Either put that card on the bottom of yo
  - ASH_162 Rash Action: Attack with a unit. For this attack, it gets +1/+0 and gains: "When Attack Ends: If this unit dealt combat damage to an 
  - ASH_163 Reckless Sacrifice: Discard a unit from your hand. Deal 5 damage to a unit that costs more than the discarded card.
- [x] **Batch 7.4 — ASH_172, ASH_185, ASH_220, ASH_226** — DONE (2164 passing): ASH_172 onAttack YESNO→discard a hand card→+2/+0 this attack (closure-level YESNO ok, hand pick from CUSTOM continuation); ASH_185 event = draw 2 if you control a 4+ power unit; ASH_220 whenPlayed look-at-opp-hand + may discard → opp draws (reuses SEC_017#2 + SWULookAtOpponentHand); ASH_226 = ObjectCurrentPower –1/–0 per card in your hand + whenPlayed may discard→deal 3.
  - ASH_172 Razor Crest: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / On Attack: You may discard a car
  - ASH_185 Intimidation: If you control a unit with 4 or more power, draw 2 cards.
  - ASH_220 Remnant Lookouts: When Played: Look at an opponent's hand. You may discard a card from it. If you do, they draw a card.
  - ASH_226 Qi\'ra: This unit gets -1/-0 for each card in your hand. / When Played: You may discard a card from your hand. If you do, deal 3
- [~] **Batch 7.5 — ASH_229, ASH_230, ASH_246, ASH_247** — DONE (2168 passing): ASH_246 event = defeat a FRIENDLY upgrade (friendly-scoped host pick → reuses DEFEAT_UPGRADE flow + DefeatUpgThen) → draw 2; ASH_247 event = defeat a friendly non-leader unit → may replay it from discard free (SWUDefeatUnit + SWUPlayDiscardUnitDiscounted). **ASH_229 DEFERRED** (peek top card → may play ANY type for free; DoTopDeckPlay only plays units). **ASH_230 DEFERRED** (Action: search top 3, discard a ground unit, then attack GAINING that discarded unit's abilities — novel ability transplant).
  - ASH_229 Camtono: Attached unit gains: "When Attack Ends: Look at the top card of your deck. If it costs 2 or less, you may play it for fr
  - ASH_230 Improvised Identity: Attach to a ground unit. / Attached unit gains: "Action: Search the top 3 cards of your deck for a ground unit and disca
  - ASH_246 Exploit Advantage: Defeat a friendly upgrade. If you do, draw 2 cards.
  - ASH_247 One Must Destroy to Create: Defeat a friendly non-leader unit. Then, you may play that unit from your discard pile for free.
- [x] **Batch 7.6 — ASH_250, ASH_260** — DONE (2171 passing): ASH_250 whenPlayed look-at-opp-hand (SWULookAtOpponentHand, info-only); ASH_260 whenPlayed may draw 1 then discard 1 (YESNO → draw → choose discard).
  - ASH_250 Imperial Defector: When Played: Look at an opponent's hand.
  - ASH_260 Mos Espa Watermonger: When Played: You may draw a card. If you do, discard a card.

## Phase 8 — Unit manipulation (bounce / capture / control / targeted defeat) (autonomous)
- [~] **Batch 8.1 — ASH_038, ASH_042, ASH_043, ASH_062** — DONE (2179 passing): ASH_038 whenPlayed/whenDefeated may return another friendly unit (SWUBounceUnit) → deal returned cost to a unit; ASH_042 whenPlayed return an upgrade (SEC_200-style) → if your own, replay free (SWUReturnUpgradeToHand + _SWUFinalizeUpgradeAttach ignoreCost); ASH_043 onAttack may give -2/-0 (APPLY_PHASE_DEBUFF) + whenDefeated may defeat a 0-power non-leader unit. **ASH_062 DONE** (Shielded + interactive "defeat a Shield to prevent damage to another friendly unit" — mirrored SEC_101 Queen Amidala: combat AddTrigger→pause→YESNO + ability-path defer/re-apply; see Backlog status above).
  - ASH_038 Purrgil Ultra: When Played/When Defeated: You may return another friendly non-leader unit to its owner's hand. If you do, deal damage t
  - ASH_042 Jabba the Hutt: Restore 2 / When Played: You may return an upgrade to its owner's hand. If it's returned to your hand, you may play it f
  - ASH_043 Corona Four: On Attack: You may give a unit -2/-0 for this phase. / When Defeated: You may defeat a non-leader unit with 0 power.
  - ASH_062 The Mandalorian: Shielded / If damage would be dealt to another friendly unit, you may defeat a Shield token on this unit. If you do, pre
- [x] **Batch 8.2 — ASH_067, ASH_083, ASH_165, ASH_171** — DONE (2185 passing): ASH_067 event defeat an upgraded non-leader unit (_SWUIsUpgraded → DEFEAT_UNIT); ASH_083 onAttack defeat all OTHER space units (UID snapshot → SWUDefeatUnit); ASH_165 whenDefeated may defeat an upgrade (SWUQueueDefeatUpgrade may/min0); ASH_171 whenPlayed may defeat a FRIENDLY upgrade → ready self (friendly-scoped DEFEAT_UPGRADE + DefeatUpgThen reads ASH171SelfUID).
  - ASH_067 Get Lost: Defeat an upgraded non-leader unit.
  - ASH_083 Summa-verminoth: Sentinel / On Attack: Defeat all other space units.
  - ASH_165 Clan Vizsla Soldier: When Defeated: You may defeat an upgrade.
  - ASH_171 Pegasus Tri-Wing: When Played: You may defeat a friendly upgrade. If you do, ready this unit.
- [x] **Batch 8.3 — ASH_199, ASH_200, ASH_232, ASH_236** — DONE (2190 passing): ASH_199 upgrade whenPlayed (fires via upgrade-WhenPlayed fallback, $mzID=host) → MZMULTICHOOSE return any number of OTHER non-token upgrades (temp-zone staged, SWUReturnUpgradeToHand each); ASH_200 event take control until regroup (SWUTakeControlOfUnit + TEMPORARY_STEAL, LOF_189 pattern) + -3/-0 phase debuff; ASH_232 event return a ≤2 upgrade + give a Shield (chained, both auto-resolve); ASH_236 event return friendly then (if done) enemy non-leader unit (SWUBounceUnit + BOUNCE_UNIT).
  - ASH_199 There Is No Conflict: When Played: Return any number of other upgrades on attached unit to their owners' hands.
  - ASH_200 Rehabilitation: Choose a non-leader unit. Give that unit -3/-0 for this phase, then take control of it. At the start of the regroup phas
  - ASH_232 Full of Surprises: Return an upgrade that costs 2 or less to its owner's hand. / Give a Shield token to a unit.
  - ASH_236 Far Far Away: Return a friendly non-leader unit to its owner's hand. If you do, return an enemy non-leader unit to its owner's hand.
- [x] **Batch 8.4 — ASH_T03** — DONE (verify-only no-op): the Shield token is a fully-implemented core mechanic (SWUConsumeShieldToken handles "prevent the damage + defeat a Shield" in CombatLogic; GiveShieldToken creates it; SHIELDCOUNT covered by keywords/Shielded_* and exercised this session in ASH_232/ASH_255). No new code or tests.
  - ASH_T03 Shield: If damage would be dealt to attached unit, prevent that damage. If you do, defeat a Shield token on it.

## Phase 9 — Complex / new infrastructure (distribute, modal, opponent-decision, replacement) (pair)
- [~] **Batch 9.1 — ASH_070, ASH_084, ASH_094, ASH_148** — DONE (2196 passing): ASH_070 base-damage cap (>4 → 4 in SWUDealDamageToBase, gated by _SWUControlsCardInPlay); ASH_084 search-doubling (×2 N at the _topDeckSearchBegin funnel — covers DoTopDeckSearch + DoTopDeckPlay — gated by _SWUControlsUnitWithUpgrade); ASH_148 Overwhelm + whenPlayed opponent-discards → may deal its cost as MZSPLITASSIGN-UPTO split (reads the just-discarded card's cost from the top of opp discard). **ASH_094 DEFERRED** (Moff Jerjerrod: interactive token-count-doubling replacement with a mid-creation self-defeat decision across every token funnel — Experience/Shield/unit/Credit).
  - ASH_070 At Attin Safety Droid: If your base would be dealt more than 4 damage, prevent all but 4 of that damage.
  - ASH_084 Arcana Star Map: Attached unit gains: "If you would search a number of cards from your deck, search twice that number of cards instead."
  - ASH_094 Moff Jerjerrod: If you would create a number of tokens, you may defeat this unit. If you do, create twice that number of tokens instead.
  - ASH_148 Ninth Sister: Overwhelm / When Played: An opponent discards a card from their hand. You may deal damage equal to its cost divided as y
- [~] **Batch 9.2 — ASH_195, ASH_211, ASH_224, ASH_235** — DONE (2201 passing): built **SWUGiveSplitAdvantage / SWUQueueDistributeAdvantage** (the SPLIT_DAMAGE analogue: MZSPLITASSIGN → SPLIT_ADVANTAGE → DoGiveAdvantageToken per count) + **SWU_FRIENDLY_LEADER_LEFT_PLAY** phase flag. ASH_195 whenDefeated distribute (power) Advantage among friendly (upto); ASH_211 event distribute 3 (unit left) / 5 (leader left) — uses the two left-play flags; ASH_235 event NUMBERCHOOSE → top-5 search (custom finalize ASH_235#1 carries the number) → draw → if cost==number may give 3 Advantage to a Force unit. **ASH_224 DEFERRED** (Elzar Mann: distribute up-to-5 + conditional enters-ready + force the OPPONENT to search 2×(count) of THEIR deck for an event and draw it — opponent-forced-search coupled to the distributed count).
  - ASH_195 Helgait: When Defeated: You may distribute a number of Advantage tokens equal to this unit's power among friendly units (divided 
  - ASH_211 Fateful Goodbye: If a friendly unit left play this phase, distribute 3 Advantage tokens among friendly units. If a friendly leader unit l
  - ASH_224 Elzar Mann: While you control a Force leader, this unit enters play ready. / When Played: Distribute up to 5 Advantage tokens among 
  - ASH_235 Sense Through the Force: Choose a number, then search the top 5 cards of your deck for a card, reveal it, and draw it. If its cost is the chosen 

## Phase 10 — Leaders (front Action + deployed side) (pair)
- [~] **Batch 10.1 — ASH_001, ASH_002, ASH_003, ASH_004** — DONE (2206 passing): ASH_002 leader Action play a hand unit (exhaust-a-friendly cost) → enters ready ($gForceEnterReady + ActivateCard wrapped in the turn/PASS save-restore); ASH_003 give +2/+2 to a unit alone in its arena (APPLY_PHASE_BUFF); ASH_004 Thrawn attack-with-a-unit + Restore 2 (= heal 2 from base) if equal unit counts. **ASH_001 DEFERRED** (The Armorer: play an upgrade from your RESOURCE zone onto a unit that entered play this phase + resource the top card — play-from-resources + entered-this-phase tracking).
  - ASH_001 The Armorer: Action [Exhaust]: Play an upgrade from your resources on a unit that entered play this phase (paying its cost). If you d
  - ASH_002 Fennec Shand: Action [1 resource, Exhaust, exhaust a friendly unit]: Play a unit from your hand (paying its cost). It enters play read
  - ASH_003 Baylan Skoll: Action [1 resource, Exhaust]: Give a friendly unit +2/+2 for this phase if it's the only unit you control in its arena. 
  - ASH_004 Grand Admiral Thrawn: Action [Exhaust]: Attack with a unit. It gains Restore 2 for this attack if you control the same number of units as the 
- [~] **Batch 10.2 — ASH_005, ASH_006, ASH_007, ASH_008** — DONE (2210 passing): ASH_005 Luke triggered "when a friendly unit's attack ends: may exhaust leader → heal 1 from that unit" (combat hook in CollectAfterAttackTriggers + DispatchTrigger case + Ash005Trigger); ASH_007 Sloane modal "give each ground OR space unit Sentinel+Overwhelm this phase" (generic SENTINEL/OVERWHELM registry turn-effects); ASH_008 Gideon "if a friendly Imperial defeated this phase, play a hand unit −1" (new SWU_IMPERIAL_DEFEATED flag + ActivateCard discount). **ASH_006 DEFERRED** (Sabine: opponent gives 2 Advantage to a unit they control, then your next unit gains Shielded this phase — cross-player decision + delayed next-unit Shielded grant).
  - ASH_005 Luke Skywalker: When a friendly unit's attack ends: You may exhaust this leader. If you do, heal 1 damage from that unit. / Epic Action:
  - ASH_006 Sabine Wren: Action [Exhaust]: An opponent gives 2 Advantage tokens to a unit they control. If they do, the next unit you play this p
  - ASH_007 Grand Admiral Sloane: Action [Exhaust]: Choose one: / Give each ground unit Sentinel and Overwhelm for this phase. / Give each space unit Sent
  - ASH_008 Moff Gideon: Action [Exhaust]: If a friendly Imperial unit was defeated this phase, play a unit from your hand. It costs 1 resource l
- [x] **Batch 10.3 — ASH_009, ASH_010, ASH_011, ASH_012** — DONE (2215 passing): ASH_009 Ahsoka +2/+0 to a unit with less power than the highest friendly; ASH_010 Bo-Katan [2 res] if a unit in each arena → create a Mandalorian token (ASH_T01); ASH_011 Cad Bane deal 1 to a unit with 2+ remaining HP; ASH_012 Vane [defeat a friendly upgrade cost → friendly-scoped DEFEAT_UPGRADE + DefeatUpgThen] → deal 2 to a base.
  - ASH_009 Ahsoka Tano: Action [Exhaust]: Choose a unit with less power than a friendly unit. It gets +2/+0 for this phase. / Epic Action: If yo
  - ASH_010 Bo-Katan Kryze: Action [2 resources, Exhaust]: If you control a unit in each arena, create a Mandalorian token. / Epic Action: If the nu
  - ASH_011 Cad Bane: Action [Exhaust]: Deal 1 damage to a unit with 2 or more remaining HP. / Epic Action: If you control 6 or more resources
  - ASH_012 Vane: Action [Exhaust, defeat a friendly upgrade]: Deal 2 damage to a base. / Epic Action: If you control 5 or more resources,
- [x] **Batch 10.4 — ASH_013, ASH_014, ASH_015, ASH_016** — DONE (2220 passing): ASH_013 Ezra attack-end-base≥3 → may exhaust → Advantage to a different unit (combat hook + DispatchTrigger); ASH_014 Mandalorian take-initiative → may pay 1 → draw (hook in SWUTakeInitiative); ASH_015 Palpatine give an exhausted friendly unit Advantage per other friendly; ASH_016 Shin attack-end → may exhaust → exhaust a unit costing < base combat damage (baseCombatDmg via trigger extra).
  - ASH_013 Ezra Bridger: When a friendly unit's attack ends: If it dealt 3 or more combat damage to a base, you may exhaust this leader. If you d
  - ASH_014 The Mandalorian: When you take the initiative: You may pay 1 resource. If you do, draw a card. / Epic Action: If you control 6 or more re
  - ASH_015 Emperor Palpatine: Action [Exhaust]: Choose an exhausted friendly unit. Give an Advantage token to it for each other friendly unit. / Epic 
  - ASH_016 Shin Hati: When a friendly unit's attack ends: You may exhaust this leader. If you do, exhaust a unit that costs less than the amou
- [x] **Batch 10.5 — ASH_017, ASH_018** — DONE (2224 passing): ASH_017 Greef "when you play OR create a unit: may exhaust → Advantage to that unit" (hook in SWUCollectOwnPlayReactions for play + SWUCreateUnitToken for create); ASH_018 Grogu "when you play a uq unit costing 4+: if ready, may deploy him" (own-play hook + new ASH_018 branch in the SWUDeployLeader gate — Grogu has no Epic Action, this trigger is his only deploy path).
  - ASH_017 Greef Karga: When you play or create a unit: You may exhaust this leader. If you do, give an Advantage token to that unit. / Epic Act
  - ASH_018 Grogu: When you play a <uq> unit that costs 4 or more: If this leader is ready, you may deploy him.

