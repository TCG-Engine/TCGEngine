# SHD — Card Implementation Plan

> ⚠ SHD is normally deferred to the Eternal format; this plan was generated at the user's explicit request (guard lifted). Confirm scope before running.

264 cards total: 160 Unit, 46 Event, 30 Upgrade, 18 Leader, 8 Base, 2 Token. 229 needs-work, 35 auto-wired (vanilla/keyword-only/base no-op/engine token + SHD_114 done).

**Core-mechanic gate: CLEAR** — Bounty, Smuggle, Capture, Experience/Shield tokens, Grit, Ambush all already built. The ONE genuinely new seam SHD needs is a **reactive play/discard trigger subsystem** (Phase in the pair-programmed section).

⚠ **Already partially implemented — verify, do NOT duplicate:** SHD_012, SHD_028, SHD_135, SHD_166, SHD_182. ⚠ **SHD_187** immunity halves likely already wired via `SWUAvoids*` helpers — only the Raid 2 (auto) + any remaining text needs checking.

### Already Done
SHD_019, SHD_020, SHD_021, SHD_022, SHD_023, SHD_024, SHD_025, SHD_026, SHD_029, SHD_043, SHD_055, SHD_060, SHD_061, SHD_062, SHD_063, SHD_070, SHD_098, SHD_100, SHD_110, SHD_114, SHD_121, SHD_136, SHD_146, SHD_152, SHD_162, SHD_200, SHD_210, SHD_218, SHD_237, SHD_238, SHD_240, SHD_257, SHD_259, SHD_T01, SHD_T02, SHD_006, SHD_123, SHD_027, SHD_031, SHD_033, SHD_058, SHD_068, SHD_071, SHD_095, SHD_116, SHD_125, SHD_134, SHD_161, SHD_165, SHD_167, SHD_173, SHD_176, SHD_185, SHD_195, SHD_211, SHD_221, SHD_222, SHD_261, SHD_032, SHD_050, SHD_052, SHD_065, SHD_075, SHD_086, SHD_089, SHD_097, SHD_107, SHD_111, SHD_113, SHD_119, SHD_127, SHD_129, SHD_148, SHD_149, SHD_160, SHD_174, SHD_175, SHD_184, SHD_197, SHD_201, SHD_203, SHD_204, SHD_213, SHD_215, SHD_225, SHD_252, SHD_030, SHD_038, SHD_041, SHD_044, SHD_048, SHD_054, SHD_059, SHD_078, SHD_079, SHD_091, SHD_108, SHD_138, SHD_150, SHD_151, SHD_154, SHD_158, SHD_159, SHD_164, SHD_166, SHD_169, SHD_171, SHD_178, SHD_190, SHD_229, SHD_234, SHD_235, SHD_246, SHD_254, SHD_262, SHD_034, SHD_035, SHD_039, SHD_040, SHD_045, SHD_046, SHD_047, SHD_049, SHD_057, SHD_066, SHD_081, SHD_082, SHD_099, SHD_103, SHD_140, SHD_141, SHD_186, SHD_212, SHD_232, SHD_258, SHD_028, SHD_080, SHD_118, SHD_139, SHD_182, SHD_183, SHD_188, SHD_189, SHD_191, SHD_196, SHD_199, SHD_216, SHD_219, SHD_220, SHD_223, SHD_227, SHD_236, SHD_076, SHD_088, SHD_120, SHD_131, SHD_170, SHD_180, SHD_187, SHD_192, SHD_243, SHD_206, SHD_209, SHD_233, SHD_260, SHD_037, SHD_042, SHD_056, SHD_067, SHD_077, SHD_083, SHD_094, SHD_101, SHD_112, SHD_117, SHD_168, SHD_179, SHD_207, SHD_231, SHD_244, SHD_247, SHD_249, SHD_085, SHD_093, SHD_102, SHD_105, SHD_115, SHD_156, SHD_198, SHD_214, SHD_245, SHD_253, SHD_053, SHD_069, SHD_072, SHD_073, SHD_074, SHD_104, SHD_124, SHD_143, SHD_177, SHD_193, SHD_224, SHD_251, SHD_051, SHD_064, SHD_128, SHD_130, SHD_135, SHD_157, SHD_181, SHD_002, SHD_003, SHD_004, SHD_006, SHD_007, SHD_009, SHD_011, SHD_012, SHD_013, SHD_016, SHD_084, SHD_096, SHD_133, SHD_137, SHD_147, SHD_163, SHD_217, SHD_239, SHD_241, SHD_250, SHD_255, SHD_036, SHD_106, SHD_132, SHD_205, SHD_248, SHD_226, SHD_256, SHD_208, SHD_144

## Phase 1 — Bounty payloads (reuse SWUCollectBounty dispatch) (autonomous)
- [x] **Batch 1.1 — SHD_027, SHD_031, SHD_033, SHD_058** ✅ 2356 passing. SHD_027 was already done (verify-only). Fixed the SHD_033/165 always-true `Status !== 2` conditional + the CR 13.f collector (bounty now goes to OtherPlayer(controller), incl. exploit park); conditional-innate bounty snapshot at defeat + capture (new `$capturedObj` param).
  - SHD_027 Hylobon Enforcer: Grit (This unit gets +1/+0 for each damage on it.) Bounty - Draw a card. (When this unit is defeated or captured, y
  - SHD_031 The Client: Shielded Action [Exhaust]: Choose a unit. For this phase, it gains: "Bounty - Heal 5 damage from a base." (When tha
  - SHD_033 Synara San: Grit While this unit is exhausted, she gains, "Bounty - Deal 5 damage to a base." (When this unit is defeated or ca
  - SHD_058 Val: Bounty - Deal 3 damage to a unit. When Defeated: Give 2 Experience tokens to a friendly unit. (The active player ch
- [x] **Batch 1.2 — SHD_068, SHD_071, SHD_095, SHD_116** ✅ 2362 passing. Generalized the SHD_123 one-off into `SWUBountyGrantUpgrades()` (drives badge + defeat snapshot; param = host-unique). SHD_116 ramp enters exhausted.
  - SHD_068 Public Enemy: Attached unit gains: "Bounty - Give a Shield token to a unit." (When this unit is defeated or captured, its opponen
  - SHD_071 Top Target: Attached unit gains: "Bounty - Heal 4 damage from a unit or base. If this unit is unique, heal 6 damage instead." (
  - SHD_095 Clone Deserter: Restore 1 (When this unit attacks, heal 1 damage from your base.) Bounty - Draw a card. (When this unit is defeated
  - SHD_116 Outlaw Corona: Bounty - Put the top card of your deck into play as a resource. (When this unit is defeated or captured, your oppon
- [x] **Batch 1.3 — SHD_123, SHD_125, SHD_134, SHD_161** ✅ 2366 passing. SHD_123 verified already-done. New `SWU_PLAYED_FROM_HAND_{uid}` flag (ActivateCard, hand-source only; also serves SHD_204); SHD_161 owner-snapshot param on the innate bounty offer + free discard replay w/ $gPlayGrantExp.
  - SHD_123 Bounty Hunter\'s Quarry: Attached unit gains: "Bounty - Search the top 5 cards of your deck, or 10 cards instead if this unit is unique, for
  - SHD_125 Price on Your Head: Attached unit gains: "Bounty - Put the top card of your deck into play as a resource." (When this unit is defeated 
  - SHD_134 Guavian Antagonizer: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) Bounty - Draw a card. (When t
  - SHD_161 Stolen Landspeeder: When Played: If you played this unit from your hand, an opponent takes control of it. Bounty - If you own this unit
- [x] **Batch 1.4 — SHD_165, SHD_167, SHD_173, SHD_176** ✅ 2372 passing. All rode batch-1.1/1.2 seams (conditional snapshot, grant-upgrade list, reward cases).
  - SHD_165 Unlicensed Headhunter: Saboteur While this unit is exhausted, it gains: "Bounty - Heal 5 damage from your base." (When this unit is defeat
  - SHD_167 Wanted Insurgents: Bounty - Deal 2 damage to a unit. (When this unit is defeated or captured, your opponent collects its bounty.)
  - SHD_173 Guild Target: Attached unit gains: "Bounty - Deal 2 damage to a base. If this unit is unique, deal 3 damage instead." (When this 
  - SHD_176 Death Mark: Attached unit gains: "Bounty - Draw 2 cards." (When this unit is defeated or captured, its opponent collects its bo
- [x] **Batch 1.5 — SHD_185, SHD_195, SHD_211, SHD_221** ✅ 2379 passing (run together with 1.6). SWUReadyResources for the ready-resource bounties; all else rode existing seams.
  - SHD_185 Doctor Evazan: Shielded (When you play this unit, give a Shield token to him.) Bounty - Ready up to 12 resources. (When this unit 
  - SHD_195 Cartel Turncoat: Bounty - Draw a card. (When this unit is defeated or captured, your opponent collects its bounty.)
  - SHD_211 Fugitive Wookiee: Bounty - Exhaust a unit. (When this unit is defeated or captured, your opponent collects its bounty.)
  - SHD_221 Wanted: Attached unit gains: "Bounty - Ready 2 friendly resources." (When this unit is defeated or captured, its opponent c
- [x] **Batch 1.6 — SHD_222, SHD_261** ✅ 2379 passing. New universal `GIVE_EXP_EACH` (multi-target sibling of GIVE_EXPERIENCE|N); SHD_222 chains an own-hand discard after DoTopDeckSearch (unique-host gate via the snapshot param).
  - SHD_222 Enticing Reward: Attached unit gains: "Bounty - Search the top 10 cards of your deck for 2 non-unit cards, reveal them, and draw the
  - SHD_261 Rich Reward: Attached unit gains: "Bounty - Give an Experience token to each of up to 2 units." (When this unit is defeated or c

## Phase 2 — Smuggle alt-cost cards (Smuggle built — implement each card's own effect) (autonomous)
- [x] **Batch 2.1 — SHD_032, SHD_050, SHD_052, SHD_065** ✅ 2385 passing. SHD_065 keyword-only no-op; SHD_052 Sugi was already implemented (added 2 guard tests); SHD_050 uses remaining-HP (CurrentHP−Damage) filter.
  - SHD_032 Lom Pyke: On Attack: You may give a Shield token to an enemy unit. If you do, give a Shield token to a friendly unit. Smuggle
  - SHD_050 Chewbacca: Grit When Played: You may defeat a unit with 5 or less remaining HP. Smuggle [9 resources Aggression Heroism]
  - SHD_052 Sugi: While an enemy unit is upgraded, this unit gains Sentinel. Smuggle [6 resources Vigilance] (If this card is a resou
  - SHD_065 Vigilant Pursuit Craft: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.)  Smuggle [7 resources, vigilance]
- [x] **Batch 2.2 — SHD_075, SHD_086, SHD_089, SHD_097** ✅ 2390 passing. SHD_089 keyword-only no-op; SHD_086 = one case label next to SOR_161/SEC_108; SHD_097 MZMAYCHOOSE (OnAttack-safe) + STAT_BUFF registry row.
  - SHD_075 Covert Strength: Heal 2 damage from a unit and give an Experience token to it. Smuggle [3 resources Vigilance] (If this card is a re
  - SHD_086 Warbird Stowaway: While you have the initiative, this unit gets +2/+0. Smuggle [4 resources Command Villainy] (If this card is a reso
  - SHD_089 Pirate Battle Tank: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.) Smuggle [7 resources Command Vill
  - SHD_097 Freetown Backup: On Attack: Give another friendly unit +2/+2 for this phase. Smuggle [4 resources Command Heroism] (If this card is 
- [x] **Batch 2.3 — SHD_107, SHD_111, SHD_113, SHD_119** ✅ 2394 passing. SHD_111/119 keyword-only no-ops. **Wired the declared-but-never-dispatched `$whenPlayedUsingSmuggleAbilities` seam** in SWUSmuggleResource (serves SHD_113 now; 148/174/213 later). ⚠ SOR_164 Wampa is 4/5 (an old test comment said 5/4 — dictionary wins).
  - SHD_107 Enterprising Lackeys: When Defeated: You may defeat a friendly resource. If you do, put this unit into play as a resource. Smuggle [6 res
  - SHD_111 Collections Starhopper: Smuggle [3 resources Command] (If this card is a resource, you may play it for its smuggle cost. Replace it with th
  - SHD_113 Privateer Crew: Smuggle [6 resources, command] (If this card is a resource, you may play it for its smuggle cost. Replace it with t
  - SHD_119 Weequay Pirate Gang: Ambush (When you play this unit, it may ready and attack an enemy unit.) Smuggle [5 resources Command] (If this car
- [x] **Batch 2.4 — SHD_127, SHD_129, SHD_148, SHD_149** ✅ 2397 passing. SHD_149 keyword-only no-op; SHD_129 = SEC_007 mirror (event form, no after-action); SHD_148 rides the new smuggle-trigger seam.
  - SHD_127 Commission: Search the top 10 cards of your deck for a Bounty Hunter, Item, or Transport card, reveal it, and draw it. (Put the
  - SHD_129 Timely Intervention: Play a unit from your hand. Give it Ambush for this phase. (When you play it, it may ready and attack an enemy unit
  - SHD_148 Cassian Andor: Smuggle [5 resources Aggression Heroism] (If this card is a resource, you may play him for his smuggle cost. Replac
  - SHD_149 Nite Owl Skirmisher: Smuggle [5 resources Aggression Heroism] (If this card is a resource, you may play it for its smuggle cost. Replace
- [x] **Batch 2.5 — SHD_160, SHD_174, SHD_175, SHD_184** ✅ 2403 passing. **New SWUSmuggleResource UPGRADE branch** (pre-payment host guard + universal SMUGGLE_ATTACH continuation; smuggle-trigger closures receive the HOST mz). **Fixed a mis-mapping: SHD_184 Bazine was wrongly wired into the JTL_111 draw-reaction hook** — removed; her real When Played implemented (look-at-hand + may-discard → opponent draws).
  - SHD_160 Reckless Gunslinger: When Played: Deal 1 damage to each base. Smuggle [3 resources Aggression] (If this card is a resource, you may play
  - SHD_174 Hotshot DL-44 Blaster: Attach to a non-VEHICLE unit.  Smuggle [3 resources, cunning]  When played using Smuggle: Attack with attached unit
  - SHD_175 Armed to the Teeth: Attached unit gains: "On Attack: Give another friendly unit +2/+0 for this phase." Smuggle [4 resources Aggression]
  - SHD_184 Bazine Netal: When Played: Look at an opponent's hand. You may discard 1 of those cards. If you do, that player draws a card. Smu
- [x] **Batch 2.6 — SHD_197, SHD_201, SHD_203, SHD_204** ✅ 2411 passing. New targeted-rescue picker (TempZone staging, captorUID:subIdx map — SHD_197); Zorii regroup-discard drain-loop; SHD_204 conditional Ambush reads SWU_PLAYED_FROM_HAND flag.
  - SHD_197 L3-37: When Played: You may rescue a captured card. If you don't, give a Shield token to this unit. Smuggle [4 resources C
  - SHD_201 Principled Outlaw: On Attack: You may exhaust a ground unit. Smuggle [6 resources Cunning Heroism] (If this card is a resource, you ma
  - SHD_203 Zorii Bliss: On Attack: Draw a card. At the start of the regroup phase, discard a card from your hand. Smuggle [6 resources Cunn
  - SHD_204 Millennium Falcon: If you play this unit from your hand, it gains Ambush. Smuggle [6 resources Cunning Heroism] (If this card is a res
- [x] **Batch 2.7 — SHD_213, SHD_215, SHD_225, SHD_252** ✅ 2418 passing. DJ resource-steal + `_SWURevertShd213Steals` lazy leave-play sweep (SEC_192 twin); Jetpack regroup token-defeat drain (approximation noted: removes one shield if the original was consumed and another gained).
  - SHD_213 DJ: Smuggle [7 resources Cunning Cunning] When played using Smuggle: Take control of an enemy resource. When this unit 
  - SHD_215 Smuggler\'s Starfighter: When Played: If you control another Underworld unit, give an enemy unit -3/-0 for this phase. Smuggle [4 resources 
  - SHD_225 Jetpack: Attach to a non-Vehicle unit. When Played: Give a Shield token to attached unit. At the start of the regroup phase,
  - SHD_252 Smuggler\'s Aid: Heal 3 damage from your base. Smuggle [3 resources Heroism] (If this card is a resource, you may play it for its sm

## Phase 3 — Direct damage / heal / defeat effects (autonomous)
- [x] **Batch 3.1 — SHD_030, SHD_038, SHD_041, SHD_044** ✅ 2441 passing. SHD_030 two-sided WhenPlayed damage (chained #0). SHD_041 On Attack mill+aspect-share-return (⚠ GetBase returns an ARRAY — `$base[0]->CardID`). SHD_038 dynamic play-from-discard: new `_SWUEffectiveDiscardModifier` returns TPP for SHD_038 while SWU_ENEMY_DEFEATED>0 (used in `SWUPlayFromDiscard` + the discard-offer loop) — no combat-site edits, unlike LAW_200's discard-time TPP stamp. SHD_044 may-return-upgrade-from-discard.
  - SHD_030 Death Trooper: When Played: Deal 2 damage to a friendly ground unit and 2 damage to an enemy ground unit.
  - SHD_038 Brutal Traditions: Action: If an enemy unit was defeated this phase, play this upgrade from your discard pile (paying its cost).
  - SHD_041 Kuiil: Restore 1 (When this unit attacks, heal 1 damage from your base.) On Attack: Discard a card from your deck. If it s
  - SHD_044 Razor Crest: Restore 2 (When this unit attacks, heal 2 damage from your base.)  When Played: You may return an upgrade from your
- [x] **Batch 3.2 — SHD_048, SHD_054, SHD_059, SHD_078** ✅ 2449 passing. SHD_048 Grit+OnAttack may-heal-another = own Damage. SHD_054 event heal-split (MZSPLITASSIGN UPTO, SOR_052 pattern minus self-damage; new SHD_054#0). SHD_059 onAttackEnd gated on `SWU_LAST_DEFENDER_DEFEATED==='1'` (ASH_036 mirror). SHD_078 defeat-event = SOR_078 + ObjectCurrentPower≥5 filter.
  - SHD_048 Gentle Giant: Grit (This unit gets +1/+0 for each damage on it.) On Attack: You may heal damage from another unit equal to the da
  - SHD_054 Midnight Repairs: Heal up to 8 total damage from any number of units.
  - SHD_059 Embo: When this unit completes an attack: If the defender was defeated, heal up to 2 damage from a unit.
  - SHD_078 Fell the Dragon: Defeat a non-leader unit with 5 or more power.
- [~] **Batch 3.3 — SHD_079, SHD_091, SHD_108 done; ⚠ SHD_090 DEFERRED** ✅ 2456 passing. SHD_079 defeat-any-unit event. SHD_108 defeat-friendly-then-draw-2 event (SHD_108#0). SHD_091 cost-1-with-Jabba (`$playCostModifiers` + `_SWUControlsTitle`) + shared whenPlayed/onAttack "deal 3 friendly + 3 enemy ground" (MZMAYCHOOSE friendly for OnAttack-safety, enemy in #0 continuation). **SHD_090 Maul DEFERRED** — needs a combat-damage REDIRECTION seam (attacker's incoming combat damage for this attack rerouted to a chosen friendly Underworld unit); first-of-kind combat modification, revisit at retro.
  - SHD_079 Rival\'s Fall: Defeat a unit.
  - SHD_090 Maul: Ambush, Overwhelm On Attack: You may choose another friendly Underworld unit. If you do, all combat damage that wou
  - SHD_091 Jabba\'s Rancor: If you control Jabba the Hutt (as a leader or unit), this unit costs 1 resource less to play. When Played/On Attack
  - SHD_108 Enforced Loyalty: Defeat a friendly unit. If you do, draw 2 cards.
- [~] **Batch 3.4 — SHD_138, SHD_150, SHD_151 done; ⚠ SHD_142 DEFERRED** ✅ 2462 passing. SHD_138 Jango: combat-time +3/+0 & Overwhelm vs a Bounty defender (`$shd138VsBounty` in SWUCombatDamage, mirrors `$sor130VsDamaged`) + "attacks and defeats a unit: draw" (SWUCollectCombatHitTriggers case + DispatchTrigger). SHD_150 OnAttack if-upgraded may-deal-2-to-ground. SHD_151 Saboteur + OnAttack +2/+0 if defender controls more resources. **SHD_142 Pre Vizsla DEFERRED** — "pay the cost of an upgrade on another non-Vehicle unit, take control of it and re-attach to this unit (else defeat)" needs a cost-payment + cross-unit upgrade-control-move seam; revisit at retro.
  - SHD_138 Jango Fett: While attacking a unit with a Bounty, this unit gets +3/+0 and gains Overwhelm. When this unit attacks and defeats 
  - SHD_142 Pre Vizsla: When Played/On Attack: You may pay the cost of an upgrade attached to another non-Vehicle unit. If you do, take con
  - SHD_150 Koska Reeves: On Attack: If this unit is upgraded, you may deal 2 damage to a ground unit.
  - SHD_151 Valiant Assault Ship: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) On Attack: If the defending p
- [~] **Batch 3.5 — SHD_154, SHD_158, SHD_159 done; ⚠ SHD_153 DEFERRED** ✅ 2466 passing. SHD_154 Overwhelm + WhenPlayed may-defeat-resource (MZMAYCHOOSE resources, SHD_107 pattern) → deal 5 to ground. SHD_158 WhenPlayed deal-2-to-each-other-ground (IBH_072 UID-snapshot). SHD_159 event: each player's hand-count to their own base (⚠ `CleanupRemovedCards()` before counting the caster's hand — the just-played event lingers). **SHD_153 Poe Dameron DEFERRED** — OnAttack "discard up to 3 from hand; for EACH, choose a DIFFERENT option (deal 2 / defeat upgrade / opp discards)"; couples a hand-discard count to N modal picks with a distinct-option constraint in an OnAttack window. Revisit at retro.
  - SHD_153 Poe Dameron: On Attack: Discard up to 3 cards from your hand. For each card discarded this way, choose a different option: <bull
  - SHD_154 Wrecker: Overwhelm When Played: You may defeat a friendly resource. If you do, deal 5 damage to a ground unit.
  - SHD_158 Wild Rancor: Overwhelm When Played: Deal 2 damage to each other ground unit.
  - SHD_159 The Chaos of War: Deal damage to each player's base equal to the number of cards in that player's hand.
- [x] **Batch 3.6 — SHD_164, SHD_166, SHD_169, SHD_171** ✅ 2470 passing. SHD_164 WhenDefeated deal-1-to-unit-or-base (`_SWUAllUnitsAndBases` + DEAL_TARGET; tested via attacker self-defeat). SHD_166 & SHD_169 verified already-implemented (SHD_166 shares SOR_162's SWUQueueDefeatUpgrade; SHD_169 Raid 3 auto + Overwhelm-while-upgraded in KeywordEffects — added a guard test). SHD_171 Grit + shared whenPlayed/onAttack may-deal-2 filtered to Bounty units (ObjectHasBounty).
  - SHD_164 Rhokai Gunship: When Defeated: Deal 1 damage to a unit or base.
  - SHD_166 Disabling Fang Fighter: When Played: You may defeat an upgrade. ⚠VERIFY-PARTIAL
  - SHD_169 Clan Challengers: Raid 3 (This unit gets +3/+0 while attacking.) While this unit is upgraded, it gains Overwhelm. (When attacking an 
  - SHD_171 Covetous Rivals: Grit (This unit gets +1/+0 for each damage on it.) When Played/On Attack: You may deal 2 damage to a unit with a Bo
- [x] **Batch 3.7 — SHD_178, SHD_190, SHD_229, SHD_234** ✅ 2476 passing. SHD_178 deal-2-to-unit-or-base event. SHD_190 Zuckuss field passive: friendly units named 4-LOM get +1/+1 (ObjectCurrentPower/HP) + Saboteur (HasConditionalKeyword_Saboteur), gated on a friendly SHD_190 in play (CardTitle==='4-LOM'). SHD_229 event bounce-friendly-Underworld-then-deal-3 (SHD_229#0). SHD_234 innate Shoot First (added CardID to the `$hasShootFirst` check).
  - SHD_178 Daring Raid: Deal 2 damage to a unit or base.
  - SHD_190 Zuckuss: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) Each friendly unit named 4-LO
  - SHD_229 Ma Klounkee: Return a friendly non-leader Underworld unit to its owner's hand. If you do, deal 3 damage to a unit.
  - SHD_234 Incinerator Trooper: While attacking, this unit deals combat damage before the defender. (If the defender is defeated, it deals no comba
- [~] **Batch 3.8 — SHD_235, SHD_246, SHD_254 done; ⚠ SHD_242 DEFERRED** ✅ 2481 passing. SHD_235 Overwhelm + WhenPlayed deal-2-to-friendly. SHD_254 WhenPlayed gated on another-friendly-Bounty-Hunter → may-deal-2-ground. SHD_246 cross-player OnAttack: opponent chooses a unit OR base they control (queued from a CUSTOM continuation under $playerID=opp), caster may-deal-2 (target carried by UID/BASE sentinel across frames; SHD_246#0/#1/#2). **SHD_242 Gideon's Light Cruiser DEFERRED** — "if you control Moff Gideon, play a ≤3-cost Villainy unit from HAND OR DISCARD for free" needs a combined two-zone free-play-with-filter picker. Revisit at retro.
  - SHD_235 Ruthless Assassin: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) When Played: Deal 2 damage to 
  - SHD_242 Gideon\'s Light Cruiser: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.)  When Played: If you control M
  - SHD_246 Grey Squadron Y-Wing: On Attack: An opponent chooses a unit or base they control. You may deal 2 damage to it.
  - SHD_254 Bounty Guild Initiate: When Played: If you control another Bounty Hunter unit, you may deal 2 damage to a ground unit.
- [x] **Batch 3.9 — SHD_262** ✅ verify-only — already implemented (CardEffects.php SHD_262 case, shares SWUQueueDefeatUpgrade) + tested (Confiscate_Reprint.md).
  - SHD_262 Confiscate: Defeat an upgrade.

## Phase 4 — Experience / Shield token granters (autonomous)
- [x] **Batch 4.1 — SHD_034, SHD_035, SHD_039, SHD_040** ✅ 2485 passing. SHD_034 Sentinel-while-upgraded verified already-done (guard test added). SHD_035 On Defense (combat-pause auto) may-give-Exp. SHD_039 event: defeat ≤3-cost non-leader, per REAL upgrade give Exp to a friendly unit (SHD_039#0, count before defeat). SHD_040 whenPlayed give-Exp-to-a-unit.
  - SHD_034 Supercommando Squad: Shielded (When you play this unit, give a Shield token to it.) While this unit is upgraded, it gains Sentinel. (Uni
  - SHD_035 Clan Saxon Gauntlet: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.) When this unit is attacked: You m
  - SHD_039 Calculated Lethality: Defeat a non-leader unit that costs 3 or less. For each upgrade that was on that unit, give an Experience token to 
  - SHD_040 Clan Wren Rescuer: When Played: Give an Experience token to a unit.
- [x] **Batch 4.2 — SHD_045, SHD_046, SHD_047, SHD_049** ✅ 2492 passing. SHD_045 OnAttack defeat-a-Shield-then-give-2-Exp. SHD_046 Rey: Heroism-aspect-penalty waiver in SWUAspectPenalty gated on _SWUControlsTitle('Kylo Ren') (drops one Heroism pip) + OnAttack heal-2-then-shield-if-non-Heroism. SHD_047 WhenPlayed MZMULTICHOOSE shield up-to-3 Mandalorians. SHD_049 WhenPlayed may heal-all + 2 shields on a ≤2-cost unit.
  - SHD_045 Rose Tico: Shielded (When you play this unit, give a Shield token to her.) On Attack: You may defeat a Shield token on a frien
  - SHD_046 Rey: While playing this unit, ignore her Heroism aspect penalty if you control Kylo Ren. On Attack: You may heal 2 damag
  - SHD_047 The Armorer: When Played: Give a Shield token to each of up to 3 Mandalorian units.
  - SHD_049 The Mandalorian: Sentinel  When Played: You may heal all damage from a unit that costs 2 or less and give 2 Shield tokens to it.
- [x] **Batch 4.3 — SHD_057, SHD_066, SHD_081, SHD_082** ✅ 2498 passing. SHD_057 OnAttack may-reveal-top → if non-unit give-Exp-to-another (leaves card on top). SHD_066 Shielded + WhenPlayed heal-4-base if another Vigilance unit (⚠ Shielded+WhenPlayed = dual entry trigger → tests answer EffectStack-0). SHD_081 MZMULTICHOOSE Exp up-to-3 Troopers. SHD_082 may-Exp another ≤3-cost unit.
  - SHD_057 Rickety Quadjumper: On Attack: You may reveal the top card of your deck. If it's not a unit, give an Experience token to another unit. 
  - SHD_066 Cargo Juggernaut: Shielded (When you play this unit, give a Shield token to it.) When Played: If you control another Vigilance unit, 
  - SHD_081 General Tagge: When Played: Give an Experience token to each of up to 3 Trooper units.
  - SHD_082 Outland TIE Vanguard: When Played: You may give an Experience token to another unit that costs 3 or less.
- [x] **Batch 4.4 — SHD_099, SHD_103, SHD_140, SHD_141** ✅ 2507 passing. SHD_099 Restore 2 + may-discard-hand-card → 2 Exp to a same-NAME in-play unit (MZMove to discard). SHD_103 whenPlayed/onAttack choose friendly: Sentinel→Exp else grant Sentinel-this-phase (registry row). SHD_140 Overwhelm + whenPlayed Exp-self if enemy has Bounty. SHD_141 Kylo: Villainy-penalty waiver if control Rey (SWUAspectPenalty, mirror of SHD_046) + OnAttack +2/+0-this-phase & Exp-if-non-Villainy.
  - SHD_099 Echo: Restore 2 When Played: You may discard a card from your hand. Give 2 Experience tokens to a unit in play with the s
  - SHD_103 General Rieekan: When Played/On Attack: Choose a friendly unit. If it has Sentinel, give an Experience token to it. Otherwise, it ga
  - SHD_140 Trandoshan Hunters: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) When Played: If an enemy unit 
  - SHD_141 Kylo Ren: While playing this unit, ignore his Villainy aspect penalty if you control Rey. On Attack: Give a unit +2/+0 for th
- [x] **Batch 4.5 — SHD_186, SHD_212, SHD_232, SHD_258** ✅ 2513 passing. SHD_186 (Shielded-while-enemy-Bounty) & SHD_212 (Shielded-while-another-Cunning) verified already-done in KeywordEffects (guard tests added). SHD_232 event capture: choose friendly captor → capture enemy non-leader costing ≤ captor's cost (SEC_106 pattern, DoCaptureUnit) + shield captor if Bounty Hunter. SHD_258 whenPlayed may-Exp-another-Mandalorian.
  - SHD_186 Hunter of the Haxion Brood: While an enemy unit has a Bounty, this unit gains Shielded. (When you play this unit, give a Shield token to it.)
  - SHD_212 Privateer Scyk: While you control another Cunning unit, this unit gains Shielded. (When you play this unit, give a Shield token to 
  - SHD_232 Relentless Pursuit: Choose a friendly unit. It captures an enemy non-leader unit that costs the same as or less than it. If the friendl
  - SHD_258 Mandalorian Warrior: When Played: You may give an Experience token to another Mandalorian unit.

## Phase 5 — Ready / exhaust effects (autonomous)
- [~] **Batch 5.1 — SHD_028, SHD_080, SHD_118 done; ⚠ SHD_087 DEFERRED** ✅ 2517 passing. SHD_028 verified already-done (unit Action, 2 existing tests). SHD_080 whenPlayed heal-base + unit Action [Exhaust, return-self-to-hand] deal-1-ground (bounce self in closure). SHD_118 Overwhelm + OnAttack may-exhaust-another-friendly → +3/+0 this attack. **SHD_087 Crosshair DEFERRED** — has TWO distinct-cost unit Actions ([2 resources]:buff and [Exhaust]:deal-power); SWUUnitAction supports only ONE action per unit → needs a multi-action-per-unit menu seam. Revisit at retro.
  - SHD_028 Doctor Pershing: Action [Exhaust, deal 1 damage to a friendly unit]: Draw a card. ⚠VERIFY-PARTIAL
  - SHD_080 Salacious Crumb: When Played: Heal 1 damage from your base. Action [Exhaust, return this unit to his owner's hand]: Deal 1 damage to
  - SHD_087 Crosshair: Action [2 resources]: This unit gets +1/+0 for this phase. Action [Exhaust]: This unit deals damage equal to his po
  - SHD_118 Kihraxz Heavy Fighter: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) On Attack: You may exhaust ano
- [x] **Batch 5.2 — SHD_139, SHD_182, SHD_183, SHD_188** ✅ 2521 passing. SHD_139 whenPlayed may-ready-if-enemy-Bounty + OnAttack deal-N-to-ground (N=own damage). SHD_182 Bravado verified already-done (cost modifier + ready). SHD_183 OnAttack exhaust-the-defender (SWU_CURRENT_DEFENDER). SHD_188 4-LOM field passive: friendly Zuckuss +1/+1 (ObjectCurrentPower/HP) + Ambush (HasConditionalKeyword_Ambush) — reciprocal of SHD_190.
  - SHD_139 Krrsantan: When Played: If an enemy unit has a Bounty, you may ready this unit. On Attack: Choose a ground unit. You may deal 
  - SHD_182 Bravado: If you've defeated an enemy unit this phase, this event costs 2 resources less to play. Ready a unit. ⚠VERIFY-PARTIAL
  - SHD_183 Kintan Intimidator: On Attack: Exhaust the defender.
  - SHD_188 4-LOM: Ambush (When you play this unit, it may ready and attack an enemy unit.) Each friendly unit named Zuckuss gets +1/+
- [x] **Batch 5.3 — SHD_189, SHD_191, SHD_196, SHD_199** ✅ 2525 passing. SHD_189 whenPlayed may-ready-another-unit (power ≤ enemy upgrade count). SHD_191 Raid 2 + whenPlayed/onAttack bounce-friendly-Underworld → exhaust-enemy-unit-or-resource (SHD_191#0/#1). SHD_196 Grogu unit Action [exhaust] → exhaust enemy unit. SHD_199 OnAttack may-ready-a-resource.
  - SHD_189 Slaver\'s Freighter: When Played: You may ready another unit with power equal to or less than the number of upgrades on enemy units.
  - SHD_191 Xanadu Blood: Raid 2 When Played/On Attack: You may return another friendly non-leader Underworld unit to its owner's hand. If yo
  - SHD_196 Grogu: Action [exhaust]: Exhaust an enemy unit.
  - SHD_199 Coruscant Dissident: On Attack: You may ready a resource.
- [x] **Batch 5.4 — SHD_216, SHD_219, SHD_220, SHD_223** ✅ 2530 passing. SHD_216 Ambush + OnAttack defender-Bounty→-4/-0 (synchronous SWU_DEF_DEBUFF_4 in ExecuteSWUAttack; onAttack stub is no-op). SHD_219 Enfys Nest field passive: while a friendly unit attacks using Ambush (SWU_AMBUSH_ATTACK) & controls SHD_219 → defender -3/-0. SHD_220 Fennec OnAttack deal-N-to-defender (N=distinct discard costs). SHD_223 Snapshot Reflexes = SOR_215 reprint (aliased handler).
  - SHD_216 Chain Code Collector: Ambush (When you play this unit, it may ready and attack an enemy unit.) On Attack: If the defender has a Bounty, i
  - SHD_219 Enfys Nest: Ambush (When you play this unit, it may ready and attack an enemy unit.) While a friendly unit (including this one)
  - SHD_220 Fennec Shand: Ambush (When you play this unit, it may ready and attack an enemy unit.) On Attack: Deal 1 damage to the defender (
  - SHD_223 Snapshot Reflexes: When Played: You may attack with attached unit. (It can only attack if it's ready.)
- [x] **Batch 5.5 — SHD_227, SHD_236** ✅ 2535 passing. SHD_227 event: exhaust a unit unless its controller pays 2 (cross-player YESNO from CUSTOM continuation under $playerID=controller; SHD_227#0/#1). SHD_236 whenPlayed may-attack-with-a-ready-unit + Imperial → +2/+0.
  - SHD_227 Look the Other Way: Exhaust a unit unless its controller pays 2 resources.
  - SHD_236 Snowtrooper Lieutenant: When Played: You may attack with a unit. If it's an Imperial unit, it gets +2/+0 for this attack. (You can only att

## Phase 6 — Capture / rescue effects (autonomous)
- [~] **Batch 6.1 — SHD_076, SHD_088, SHD_120 done; ⚠ SHD_092 DEFERRED** ✅ 2538 passing. SHD_120 whenPlayed self-captures enemy non-leader ground (DoCaptureUnit). SHD_076 event exhaust-a-unit + may-rescue-its-captive (TempZone staging like SHD_197 + DoRescueUnit). SHD_088 OnAttack capture-enemy-that-attacked-your-base-this-phase (SWU_DEALT_BASEDMG flag) via a same-arena friendly captor (SHD_088#0/#1). **SHD_092 Finalizer DEFERRED** — "choose any number of friendly units, each captures an enemy non-leader in the SAME arena" = chained per-unit arena-matched multi-capture (11-cost). Revisit at retro.
  - SHD_076 Unexpected Escape: Exhaust a unit. You may rescue a captured card guarded by that unit.
  - SHD_088 Ephant Mon: On Attack: Choose an enemy non-leader unit that attacked your base this phase. A friendly unit in the same arena ca
  - SHD_092 Finalizer: Overwhelm When Played: Choose any number of friendly units. Each of those units captures an enemy non-leader unit i
  - SHD_120 Discerning Veteran: When Played: This unit captures an enemy non-leader ground unit. (Put the captured card facedown under this unit un
- [x] **Batch 6.2 — SHD_131, SHD_170, SHD_180, SHD_187** ✅ 2543 passing. SHD_131 & SHD_187 verified already-done (Take Captive event; SHD_187 SWUAvoids* immunity — added a guard test). SHD_170 IG-11: OnAttack may-deal-3-to-damaged-ground + capture-REPLACEMENT in DoCaptureUnit (⚠ $capturedMZ is in the CAPTURER's frame → defeat + AOE both under $player, not the controller). SHD_180 event deal-3 (6 if the target guards a captive).
  - SHD_131 Take Captive: A friendly unit captures an enemy non-leader unit in the same arena. (Put the captured card facedown under that uni
  - SHD_170 IG-11: If this unit would be captured, defeat him and deal 3 damage to each enemy ground unit instead. On Attack: You may 
  - SHD_180 Detention Block Rescue: Deal 3 damage to a unit. If that unit is guarding any captured cards, deal 6 damage instead.
  - SHD_187 Lurking TIE Phantom: Raid 2 (This unit gets +2/+0 while attacking.) This unit can't be captured, damaged, or defeated by enemy card abil
- [x] **Batch 6.3 — SHD_192, SHD_243** ✅ 2545 passing. New `_SWUStageFriendlyCaptives`/`_SWUDetachCaptiveByEntry` helpers (TempZone staging of friendly-guarded captives). SHD_192 Dryden Vos: Shielded + whenPlayed play-a-friendly-captive-for-free under YOUR control (arena add, Controller=caster, owner unchanged). SHD_243 event discard-a-friendly-captive to its owner's discard.
  - SHD_192 Dryden Vos: Shielded When Played: Choose a captured card guarded by a unit you control. You may play it for free under your con
  - SHD_243 Altering the Deal: Discard a captured card guarded by a friendly unit.

## Phase 7 — Return-to-hand (bounce) effects (autonomous)
- [x] **Batch 7.1 — SHD_206, SHD_209, SHD_233, SHD_260** ✅ 2549 passing. SHD_206 event bounce-enemy + collect-its-Bounty (YESNO + SWUCollectBounty|cardID). SHD_209 whenPlayed return-a-non-unique-upgrade (TempZone stage `hostUID:cardID` → SWUReturnUpgradeToHand). SHD_233 event mass-bounce all non-leader units (UID-snapshot). SHD_260 whenPlayed return-an-Underworld-card-from-own-discard.
  - SHD_206 Spare the Target: Return an enemy non-leader unit to its owner's hand. Collect that unit's Bounties.
  - SHD_209 Criminal Muscle: When Played: You may return a non-unique upgrade to its owner's hand.
  - SHD_233 Evacuate: Return each non-leader unit to its owner's hand.
  - SHD_260 Street Gang Recruiter: When Played: You may return an Underworld card from your discard pile to your hand.

## Phase 8 — Field-presence stat passives & conditional keyword grants (autonomous)
- [x] **Batch 8.1 — SHD_037, SHD_042, SHD_056, SHD_067** ✅ 2553 passing. SHD_037 Snoke enemy -2/-2 verified already-done (guard test). SHD_042 +2/+0 while defending (combat counter-power, mirrors LOF_049). SHD_056 +1/+1 while upgraded (ObjectCurrentPower/HP). SHD_067 Fenn Rau: whenPlayed play-upgrade-from-hand-discounted (⚠ CleanupRemovedCards first; DISCOUNT_PLAY_FROM_HAND doesn't handle upgrades → SEC_003 host-pick + `_SWUFinalizeUpgradeAttach(prepaid:2)`) + reactive "when-upgrade-played-on-him give enemy -2/-2" (CollectWhenPlayedAsUpgradeTriggers + DispatchTrigger).
  - SHD_037 Supreme Leader Snoke: Each enemy non-leader unit gets -2/-2.
  - SHD_042 Concord Dawn Interceptors: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.) This unit gets +2/+0 while defend
  - SHD_056 Follower of The Way: While this unit is upgraded, it gets +1/+1.
  - SHD_067 Fenn Rau: When Played: You may play an upgrade from your hand. It costs 2 resources less. When you play an upgrade on this un
- [x] **Batch 8.2 — SHD_077, SHD_083, SHD_094, SHD_101** ✅ 2559 passing. SHD_077 event take-control-of-upgrade-cost≤3 + reattach (extended the `SWUQueueMoveUpgrade` filter with `cost:N`). SHD_083 +2/+0 while ≥6 resources (ObjectCurrentPower). SHD_094 event play-unit-from-discard -6 (-8 if Force; SWUPlayDiscardUnitDiscounted). SHD_101 whenPlayed may-attack + initiative → +2/+0.
  - SHD_077 Evidence of the Crime: Take control of an upgrade that costs 3 or less and attach it to an eligible unit of your choice.
  - SHD_083 Seasoned Shoretrooper: While you control 6 or more resources, this unit gets +2/+0.
  - SHD_094 Palpatine\'s Return: Play a unit from your discard pile.  It costs 6 resources less. If it's a Force unit,  it costs 8 resources less in
  - SHD_101 Adelphi Patrol Wing: When Played: You may attack with a unit. If you have the initiative, it gets +2/+0 for this attack.
- [~] **Batch 8.3 — SHD_112, SHD_117, SHD_168 done; ⚠ SHD_145 DEFERRED** ✅ 2563 passing. SHD_112 (Sentinel-while-another-Command) & SHD_168 (Raid 2-while-another-Aggression) verified already-done (guard tests; HASKEYWORD:Raid works for value keywords). SHD_117 cost -1 if enemy has a Bounty (`$playCostModifiers`). **SHD_145 Headhunting DEFERRED** — "attack with up to 3 units (one at a time), no bases, each Bounty Hunter +2/+0" = count-capped multi-attack needing the SWU_TRIGGER_RESUME re-offer loop (SEC_103 seam); build together with SHD_128 Outflank (Phase 11).
  - SHD_112 Gamorrean Retainer: While you control another Command unit, this unit gains Sentinel. (Units in this arena can't attack your non-Sentin
  - SHD_117 Reputable Hunter: If an enemy unit has a Bounty, this unit costs 1 resource less to play.
  - SHD_145 Headhunting: Attack with up to 3 units (one at a time). They can't attack bases for these attacks. Each Bounty Hunter that attac
  - SHD_168 Hunting Nexu: While you control another Aggression unit, this unit gains Raid 2. (It gets +2/+0 while attacking.)
- [~] **Batch 8.4 — SHD_179, SHD_207 done; ⚠ SHD_202, SHD_230 DEFERRED** ✅ 2565 passing. SHD_179 event attack-with-a-damaged-unit +2/+0. SHD_207 event bounce-≤6-cost-non-leader → owner-may-replay-free (LOF_185 pattern, reuses LOF_185#2). **SHD_202 Qi'ra DEFERRED** — look-at-hand + NAME a card + "each card with that name costs 3 more for opponents while this in play" needs a per-unit named-SURCHARGE hook in SWUComputePlayCost (SOR_062 name-block is a block, not a surcharge). **SHD_230 Swoop Down DEFERRED** — grant a chosen SPACE unit cross-arena-attack + Saboteur + conditional buff/debuff for one attack (granted SOR_212-style cross-arena; new marker in SWUGetValidAttackTargets).
  - SHD_179 Desperate Attack: Attack with a damaged unit. It gets +2/+0 for this attack.
  - SHD_202 Qi\'ra: When Played: Look at an opponent's hand, then name a card. While this unit is in play, each card with that name cos
  - SHD_207 A New Adventure: Return a non-leader unit that costs 6 or less to its owner's hand. Then, its owner may play it for free.
  - SHD_230 Swoop Down: Attack with a space unit. It gains Saboteur and can attack ground units for this attack. If it attacks a ground uni
- [x] **Batch 8.5 — SHD_231, SHD_244, SHD_247, SHD_249** ✅ 2570 passing. SHD_231 event attack-with-a-unit +3/+0. SHD_244 event opp-discards-1 + draw-1. SHD_247 (Sentinel-while-upgraded) verified already-done (guard test). SHD_249 Grit + whenPlayed draw-if-another-Wookiee.
  - SHD_231 Surprise Strike: Attack with a unit. It gets +3/+0 for this attack.
  - SHD_244 No Bargain: Each opponent discards a card from their hand. Draw a card.
  - SHD_247 Protector of the Throne: While this unit is upgraded, it gains Sentinel. (Units in this arena can't attack your non-Sentinel units or your b
  - SHD_249 Wookiee Warrior: Grit (This unit gets +1/+0 for each damage on it.) When Played: If you control another Wookiee unit, draw a card.

## Phase 9 — Deck search / draw / discard / resource-ramp (autonomous)
- [x] **Batch 9.1 — SHD_085, SHD_093, SHD_102, SHD_105** — done (7 tests; whenDefeated self→ready-resource, DoTopDeckSearch top-5, discard→resource name-match, defeated-this-phase→resource; reg 2577/0).
  - SHD_085 Superlaser Technician: When Defeated: You may put this unit into play as a resource and ready it.
  - SHD_093 Remnant Reserves: Search the top 5 cards of your deck for up to 3 units, reveal them, and draw them. (Put the other cards on the bott
  - SHD_102 The Marauder: Ambush When Played: Choose a card in your discard pile. Put it into play as a resource if it shares a name with a u
  - SHD_105 Spark of Hope: Choose a unit in your discard pile. If it was defeated this phase, put it into play as a resource.
- [~] **Batch 9.2 — SHD_115, SHD_122, SHD_156, SHD_194** — SHD_115 (whenDefeated top-10 search→discard-TPF free-play), SHD_156 (draw + opp-more-resources discard) DONE (4 tests; reg 2581/0). **DEFERRED:** SHD_122 (Ambush + "attacks & defeats a non-leader unit: put it into play as a resource under YOUR control" — cross-player resource-steal defeat-replacement, new control/owner seam), SHD_194 (search top 7 for a Vehicle, play it -5 & ready, **return to hand at end of phase** — no end-of-phase-return temp-play infra exists yet).
  - SHD_115 Cobb Vanth: When Defeated: Search the top 10 cards of your deck for a unit that costs 2 or less and discard it. For this phase,
  - SHD_122 Arquitens Assault Cruiser: Ambush When this unit attacks and defeats a non-leader unit: Put the defeated unit into play as a resource under yo
  - SHD_156 Cripple Authority: Draw a card. Each opponent who controls more resources than you discards a card from their hand.
  - SHD_194 Triple Dark Raid: Search the top 7 cards of your deck for a Vehicle and play it. (Put the other cards on the bottom of your deck in a
- [~] **Batch 9.3 — SHD_198, SHD_214, SHD_228, SHD_245** — SHD_198 (whenPlayed Clone search + "first Clone unit/round ignores aspect penalty" waiver in SWUAspectPenalty w/ per-round SWU_SHD198_USED charge), SHD_214 (whenPlayed optional return-resource→optional top-card ramp), SHD_245 (whenPlayed upgrade search) DONE (7 tests; reg 2588/0). **DEFERRED:** SHD_228 (search WHOLE deck for a Bounty upgrade, draw, shuffle, **then may play that upgrade paying its cost** — chained search→optional upgrade-play-at-cost-with-host seam).
  - SHD_198 Omega: Ignore the aspect penalty on the first Clone unit you play each round. When Played: Search the top 5 cards of your 
  - SHD_214 Frontier Trader: When Played: You may return a resource you control to its owner's hand. If you do, you may put the top card of your
  - SHD_228 Bounty Posting: Search your deck for a Bounty upgrade, reveal it, and draw it. (Shuffle your deck.)  You may play that upgrade (pay
  - SHD_245 Greef Karga: When Played: Search the top 5 cards of your deck for an upgrade, reveal it, and draw it. (Put the other cards on th
- [x] **Batch 9.4 — SHD_253** — done (top-8 search up to 2 Mandalorian/upgrade; 1 test; reg 2589/0).
  - SHD_253 This Is The Way: Search the top 8 cards of your deck for up to 2 Mandalorian and/or upgrade cards, reveal them, and draw them. (Put 

## Phase 10 — Upgrades granting abilities (autonomous)
- [x] **Batch 10.1 — SHD_053, SHD_069, SHD_072, SHD_073** — done (4 new tests; reg 2593/0). SHD_053 (TPF-on-defeat) + SHD_072 (LostAbilities) already implemented → verify. SHD_069 Mandalorian trait grant in _SWUUnitHasTrait; SHD_073 whenPlayed-as-upgrade shield-if-Mandalorian.
  - SHD_053 Second Chance: Attach to a non-leader unit. Attached unit gains: "When Defeated: For this phase, this unit's owner may play it fro
  - SHD_069 Foundling: Attached unit gains the Mandalorian trait.
  - SHD_072 Imprisoned: Attach to a non-leader unit. Attached unit loses its current abilities and can't gain abilities.
  - SHD_073 Mandalorian Armor: Attach to a non-Vehicle unit. When Played: If attached unit is a Mandalorian, give a Shield token to it.
- [~] **Batch 10.2 — SHD_074, SHD_104, SHD_124, SHD_126** — SHD_074 (granted On Attack: exhaust defender), SHD_104 (granted On Attack + When Defeated give-exp; whenDefeated-from-upgrade via CollectWhenDefeatedTriggers Subcards scan + DispatchTrigger case), SHD_124 (whenPlayed-as-upgrade capture weaker-power enemy) DONE (5 tests; reg 2598/0). **DEFERRED:** SHD_126 The Darksaber ("while playing this upgrade on a Mandalorian unit, ignore its aspect penalty" — host-conditional aspect-waiver at upgrade-play time is a new seam; the granted On-Attack multi-Mando-exp half is trivial once that lands).
  - SHD_074 Vambrace Grappleshot: Attach to a non-Vehicle unit. Attached unit gains: "On Attack: Exhaust the defender."
  - SHD_104 Inspiring Mentor: Attach to a non-Vehicle unit. Attached unit gains, "On Attack/When Defeated: Give an Experience token to another fr
  - SHD_124 Legal Authority: Attach to a friendly unit. When Played: Attached unit captures an enemy non-leader unit with less power than it. (P
  - SHD_126 The Darksaber: Attach to a non-Vehicle unit. While playing this upgrade on a Mandalorian unit, ignore its aspect penalty. Attached
- [~] **Batch 10.3 — SHD_143, SHD_155, SHD_177, SHD_193** — SHD_143 (granted attacks-and-defeats→2 to defending base; combat-trigger upgrade scan), SHD_177 (granted On Attack: may split 3 among enemy ground; MZSPLITASSIGN from CUSTOM continuation), SHD_193 (attached can't-ready via LAW_077 sites + whenPlayed exhaust) DONE (5 tests; reg 2603/0). **DEFERRED:** SHD_155 Heroic Resolve (granted "Action [2 resources, defeat a Heroic Resolve on this unit]: attack +4/+0 & Overwhelm" — granted unit-action with a self-defeat-the-upgrade cost is a new seam).
  - SHD_143 Ruthlessness: Attached unit gains: "When this unit attacks and defeats a unit: Deal 2 damage to the defending player's base."
  - SHD_155 Heroic Resolve: Attached unit gains: "Action [2 resources, defeat a Heroic Resolve on this unit]: Attack with this unit. It gets +4
  - SHD_177 Vambrace Flamethrower: Attach to a non-Vehicle unit. Attached unit gains: "On Attack: You may deal 3 damage divided as you choose among en
  - SHD_193 Frozen in Carbonite: Attach to a non-leader unit. Attached unit can't ready. When Played: Exhaust attached unit.
- [x] **Batch 10.4 — SHD_224, SHD_251** — done (5 tests; reg 2608/0). SHD_224 (Boba-Fett-only continuous prevent-2 in _SWUApplyDamagePrevention), SHD_251 (whenPlayed-as-upgrade: The-Mandalorian captures an exhausted enemy non-leader).
  - SHD_224 Boba Fett\'s Armor: Attach to a non-Vehicle unit. If attached unit is Boba Fett and damage would be dealt to him, prevent 2 of that dam
  - SHD_251 The Mandalorian\'s Rifle: Attach to a friendly non-VEHICLE unit.  When Played: If attached unit is The Mandalorian, he captures an exhausted 

## Phase 11 — Misc WhenPlayed / OnAttack / WhenDefeated triggers (autonomous)
- [~] **Batch 11.1 — SHD_051, SHD_064, SHD_109, SHD_128** — SHD_051 (event -2/-0 or -2/-2-if-Force debuff), SHD_064 (whenPlayed/onAttack move-upgrade w/ new 'sameController' dest scope), SHD_128 Outflank (attack with 2 units via SWU_CHAINED_ATTACK — unblocks deferred SHD_145) DONE (5 tests; reg 2613/0). **DEFERRED:** SHD_109 Endless Legions (14-cost finisher: reveal any number of your resources, play each unit revealed for free one at a time — multi-select-resources + chained free-plays seam).
  - SHD_051 Mystic Reflection: Give an enemy unit -2/-0 for this phase. If you control a Force unit, give the enemy unit -2/-2 for this phase inst
  - SHD_064 Survivors\' Gauntlet: When Played/On Attack: You may attach an upgrade on a unit to another eligible unit controlled by the same player.
  - SHD_109 Endless Legions: Reveal any number of resources you control. Play each unit revealed this way for free (one at a time).
  - SHD_128 Outflank: Attack with 2 units (one at a time).
- [x] **Batch 11.2 — SHD_130, SHD_135, SHD_157, SHD_181** — done (5 tests; reg 2616/0). SHD_130 (event +4/+4 buff), SHD_157 (whenDefeated draw per 15+-damaged base) implemented; SHD_135 (Kylo's TIE play-from-discard) + SHD_181 (Pillage) already implemented → verified.
  - SHD_130 Moment of Glory: Give a unit +4/+4 for this phase.
  - SHD_135 Kylo\'s TIE Silencer: Action: If this unit was discarded from your hand or deck this phase, play it from your discard pile (paying its co ⚠VERIFY-PARTIAL
  - SHD_157 Bo-Katan Kryze: When Defeated: For each player with 15 or more damage on their base, draw a card.
  - SHD_181 Pillage: Choose a player. They discard 2 cards from their hand.

## Phase 12 — Leaders (front passive/action + Epic deploy + deployed side) (autonomous)
- [~] **Batch 12.1 — SHD_001, SHD_002, SHD_003, SHD_004** — SHD_002 Qi'ra (front deal-2+shield / deployed Grit + WhenDeployed heal-all-then-half-HP), SHD_003 Finn (front + deployed defeat-friendly-upgrade→shield via DefeatUpgThen chain), SHD_004 Rey (front + deployed On-Attack exp-to-≤2-power + Restore 3) fully DONE both sides (7 tests; reg 2623/0). Epic deploy is generic (threshold = leader cost). **SHD_001 Gar Saxon:** front + deployed **passive** "each friendly upgraded unit +1/+0" DONE + tested; **deployed grant** "upgraded units gain When Defeated: return an attached upgrade to hand" **DEFERRED** (needs a snapshot-the-upgrades-at-defeat seam) — SHD_001 not fully done.
  - SHD_001 Gar Saxon: Each friendly upgraded unit gets +1/+0. Epic Action: If you control 6 or more resources, deploy this leader.
  - SHD_002 Qi\'ra: Action [1 resource, Exhaust]: Deal 2 damage to a friendly unit. Then, give a Shield token to it. Epic Action: If yo
  - SHD_003 Finn: Action [Exhaust]: Defeat a friendly upgrade on a unit. If you do, give a Shield token to that unit. Epic Action: If
  - SHD_004 Rey: Action [1 resource, Exhaust]: Give an Experience token to a unit with 2 or less power. Epic Action: If you control 
- [~] **Batch 12.2 — SHD_005, SHD_006, SHD_007, SHD_008** — SHD_006 Jabba already fully implemented (5 tests) → verified; SHD_007 Moff Gideon DONE both sides (front attack-≤3 +1-vs-unit / deployed Overwhelm + ≤3-units +1/+0 & Overwhelm-vs-unit; combat-conditional in CombatLogic; 3 tests; reg 2626/0). **DEFERRED to Phase 13:** SHD_005 Hondo + SHD_008 Boba Fett — their front sides are undeployed-leader "When you play a card using Smuggle / a unit with keywords" reactions (SWUCollectOwnPlayReactions only scans deployed unit observers, not undeployed leaders — the exact Phase 13 reactive-trigger subsystem).
  - SHD_005 Hondo Ohnaka: When you play a card using Smuggle: You may exhaust this leader. If you do, give an Experience token to a unit. Epi
  - SHD_006 Jabba the Hutt: Action [Exhaust]: Choose a unit. For this phase, it gains: "Bounty - The next unit you play this phase costs 1 reso
  - SHD_007 Moff Gideon: Action [exhaust]: Attack with a unit that costs 3 or less. If it's attacking a unit, it gets +1/+0 for this attack.
  - SHD_008 Boba Fett: When you play a unit that has 1 or more keywords: You may exhaust this leader. If you do, give a friendly unit +1/+
- [~] **Batch 12.3 — SHD_009, SHD_010, SHD_011, SHD_012** — SHD_009 Hunter (front + deployed On-Attack reveal-resource→name-match→return+ramp; Overwhelm auto), SHD_011 Kylo Ren (front discard-cost→+2/+0 / deployed -1/-0-per-hand-card passive) DONE both sides; SHD_012 Bo-Katan already fully implemented (6 tests) → verified. (5 new tests; reg 2631/0). **DEFERRED to Phase 13:** SHD_010 Bossk — deployed "When you collect a bounty: you may collect it again (once/round)" is a reactive trigger (front deal-1-to-Bounty is trivial once we tackle it).
  - SHD_009 Hunter: Action [1 resource, Exhaust]: Reveal a resource you control. If it shares a name with a friendly unique unit, retur
  - SHD_010 Bossk: Action [Exhaust]: Deal 1 damage to a unit with a Bounty. You may give it +1/+0 for this phase. Epic Action: If you 
  - SHD_011 Kylo Ren: Action [Exhaust, discard a card from your hand]: Give a unit +2/+0 for this phase. Epic Action: If you control 4 or
  - SHD_012 Bo-Katan Kryze: Action [Exhaust]: If you attacked with a Mandalorian unit this phase, deal 1 damage to a unit. Epic Action: If you  ⚠VERIFY-PARTIAL
- [~] **Batch 12.4 — SHD_013, SHD_014, SHD_015, SHD_016** — SHD_013 Han Solo (front+deployed play-a-unit -1 & deal-2-to-it, via SEC_018 findable-marker pattern), SHD_016 Fennec Shand (front+deployed play-≤4 + Ambush grant; deployed Saboteur auto) DONE both sides (4 tests). ⚠ leader-front play-from-hand tests need on-aspect fixtures or padded resources — an undeployed leader's aspects do NOT reduce the played unit's aspect penalty (player aspect = base only). **DEFERRED to Phase 13:** SHD_014 Cad Bane (both sides "When you play an Underworld card" reactive). **DEFERRED (complex):** SHD_015 Doctor Aphra (deployed WhenDeployed "choose 3 different-named discard cards, return 1 at random" + "5+ different costs in discard → +3/+0" passive + front regroup-mill — multi-select-distinct + random-return seam).
  - SHD_013 Han Solo: Action [Exhaust]: Play a unit from your hand. It costs 1 resource less. Deal 2 damage to it. Epic Action: If you co
  - SHD_014 Cad Bane: When you play an Underworld card: You may exhaust this leader. If you do, an opponent chooses a unit they control. 
  - SHD_015 Doctor Aphra: When the regroup phase starts: Discard a card from your deck. Epic Action: If you control 5 or more resources, depl
  - SHD_016 Fennec Shand: Action [1 resource, Exhaust]: Play a unit that costs 4 or less from your hand (paying its cost). Give it Ambush for
- [~] **Batch 12.5 — SHD_017, SHD_018** — both DEFERRED. SHD_017 Lando ("Play a card using Smuggle. It costs 2 less. Defeat a resource you own." — needs a **discounted-smuggle-play** seam: SWUSmuggleResource has no discount param, plus the resource-defeat cost). SHD_018 The Mandalorian (both sides "When you play an upgrade: … exhaust an enemy unit with ≤N HP" — reactive → **Phase 13**).
  - SHD_017 Lando Calrissian: Action [Exhaust]: Play a card using Smuggle. It costs 2 resources less. Defeat a resource you own and control. Epic
  - SHD_018 The Mandalorian: When you play an upgrade: You may exhaust this leader. If you do, exhaust an enemy unit with 4 or less remaining HP

## Phase 13 — Reactive triggers — NEW subsystem ("when you play/discard a card", "when X attacks/deals damage") (pair-programmed)
- [x] **Batch 13.1 — SHD_084, SHD_096, SHD_133, SHD_137** — done (4 tests; reg 2639/0). SHD_084 (combat-damage-to-self→exp; added `$isCombat` flag to `_SWUOnUnitDamaged`), SHD_096 (own-play observer→exp-to-self in SWUCollectOwnPlayReactions), SHD_133 (upgrade-play field observer→may deal 1 to host, in CollectWhenPlayedAsUpgradeTriggers + DispatchTrigger), SHD_137 (upgraded-enemy-defeated→ready-self; added `upgraded` flag to defeat entries + observer in SWUCollectLeavePlayReactions). ⚠ **Subsystem note:** an interactive (YESNO) reaction queued in the leave-play flush does NOT drain on the defender-defeat/opponent-observer path — used the SOR_015 benefit-only auto-resolve precedent (auto-ready when exhausted) instead.
  - SHD_084 Phase-III Dark Trooper: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.)  When combat damage is dealt to t
  - SHD_096 Maz Kanata: When you play another unit: Give an Experience token to this unit.
  - SHD_133 Dengar: When you play an upgrade on a unit: You may deal 1 damage to that unit.
  - SHD_137 Punishing One: When an upgraded enemy unit is defeated: You may ready this unit. Use this ability only once each round.
- [~] **Batch 13.2 — SHD_147, SHD_163, SHD_172, SHD_217** — SHD_147 (combat-hit: damage-to-base→may defeat upgrade ≤2), SHD_163 (any hand-discard→may deal 2; observer in both DoDiscardCard AND SWUAddToDiscard[from='HAND'] to cover self-chosen + forced discards; once/round guard in dispatch for multi-discard), SHD_217 (own-play non-unit→may exhaust ≤cost unit, once/round consumed on use) DONE (3 tests; reg 2642/0). **DEFERRED:** SHD_172 Krayt Dragon — the interactive **cross-player target-choose** (P1 reacting to P2's play, picking among P2's board) does NOT drain in the reactive flush (not the queuing frame nor MZMAYCHOOSE-vs-MZCHOOSE; TWI_210 works only because it targets P1's OWN units). Left unwired. ⚠ **Subsystem gap #2** (after the 13.1 leave-play YESNO drain).
  - SHD_147 Ketsu Onyo: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) When this unit deals combat d
  - SHD_163 Migs Mayfeld: When a player discards a card from their hand: You may deal 2 damage to a unit or base. Use this ability only once 
  - SHD_172 Krayt Dragon: Overwhelm When an opponent plays a card: You may deal damage equal to that card's cost to their base or a ground un
  - SHD_217 Tobias Beckett: When you play a non-unit card: You may exhaust a unit that costs the same as or less than the card you played. Use 
- [x] **Batch 13.3 — SHD_239, SHD_241, SHD_250, SHD_255** — done (4 tests; reg 2646/0). SHD_239 (own-play BH-unit→may deal-1-to-it+ready-self, once/round), SHD_241 (enemy-attacks-your-base→shield a friendly unit in the attacker's arena; inline next to ASH_160), SHD_250 (friendly Wookiee dealt combat damage & survives→deals that much to an enemy ground unit; via the _SWUOnUnitDamaged $isCombat hook), SHD_255 (own-play Underworld card→may deal 1 to a base). These cross-player reactions work because they target OWN units (SHD_241) or fire on the OWN turn (SHD_250-as-attacker).
  - SHD_239 Toro Calican: When you play another Bounty Hunter unit: You may deal 1 damage to it. If you do, ready this unit. Use this ability
  - SHD_241 Kragan Gorr: When an enemy unit attacks your base: Give a Shield token to a friendly unit in the same arena as the attacker.
  - SHD_250 Tarfful: Restore 2 When a friendly Wookiee unit is dealt combat damage and isn't defeated: That unit deals that much damage 
  - SHD_255 Lady Proxima: When you play another Underworld card: You may deal 1 damage to a base.

## Phase 14 — Control exchange, forced actions, modal opponent choice & alt win-condition (pair-programmed)
- [x] **Batch 14.1 — SHD_036, SHD_106, SHD_132, SHD_144** — SHD_036 First Light (self-Grit + grant Grit to other friendly non-leader units; mirrors SEC_088 in HasConditionalKeyword_Grit), SHD_106 Rule with Respect (a friendly unit captures each enemy that dealt base damage this phase, via SWU_DEALT_BASEDMG markers), SHD_132 Choose Sides (control EXCHANGE via SWUTakeControlOfUnit; mirrors LAW_170 minus the Credit-token half) DONE (3 tests). **DEFERRED:** SHD_144 Give In to Your Anger — "its controller's NEXT action this phase must be an attack with that unit" is a **forced-action seam** (no existing infra to constrain the opponent's action options). SHD_036's Smuggle-with-a-deal-4-cost is also unimplemented (like SHD_017's discounted-smuggle) — the card is fully functional played normally. **SHD_144 Give In to Your Anger DONE** — first forced-action ("must attack with that unit") card. OnPlayEvent chooses the enemy unit → `SHD_144#0` deals 1 + arms `SWU_SHD144_FORCE|{uid}` on that unit's controller; new `_SWUCheckForcedAttack()` runs right after `SWUSwapTurnPlayer()` in `SWUAfterAction` — if the compelled unit can attack a unit it force-attacks (units only, `BeginSWUAttack(..., noBases:true)`); if only a base is attackable it hits the base; if it can't attack (exhausted/gone/"can't attack") the compulsion lapses. **Enforcement model:** auto-execute the compelled attack (the engine has no "restrict the action menu" layer), mirroring ASH_155's programmatic bonus attack; multi-unit-target still queues the choose so the controller picks WHICH unit. 5 tests (forces-unit-attack-not-base, multi-target-choose, no-unit→base fallback, exhausted→lapses, 1dmg-defeats-target→no-attack); SHD 338/338, ASH+SOR 847/847 (turn-swap/combat unaffected).
  - SHD_036 First Light: Grit Each other friendly non-leader unit gains Grit. Smuggle [7 resources Vigilance Villainy, deal 4 damage to a fr
  - SHD_106 Rule with Respect: A friendly unit captures each enemy non-leader unit that attacked your base this phase.
  - SHD_132 Choose Sides: Choose a friendly non-leader unit and an enemy non-leader unit. Exchange control of those units.
  - SHD_144 Give In to Your Anger: Deal 1 damage to an enemy unit. Its controller's next action this phase must be an attack action with that unit, if
- [~] **Batch 14.2 — SHD_205, SHD_208, SHD_226, SHD_248** — SHD_205 Let the Wookiee Win (modal opponent-choose: ready-6-resources OR ready-a-unit-&-Wookiee-attacks-+2/+0; mirrors LAW_080's opponent-OPTIONCHOOSE) DONE (2 tests). **SHD_248 Tech DONE** — was already fully implemented (`PlayerHasTechInPlay` + the "Tech path" `CardCost+2+aspectPenalty` in `GetEffectiveSmuggleCost` + `ResourceHasSmuggle`; `SWUSmuggleResource` uses `min(native, Tech)`). Verified: 2 existing tests + 1 new (grants Smuggle to a card with NO printed Smuggle; reg 2652/0). **SHD_226 Unrefusable Offer DONE** — Bounty reward that plays the bountied host under the collector's control (ready, tagged `SWU_SNEAK_DEFEAT` for regroup-defeat). Added `SHD_226` to `SWUBountyGrantUpgrades()`; threaded the defeated-host `cardID~owner` into the reward `param`; new `case 'SHD_226'` in `SWUCollectBounty` pulls the host from the owner's discard, re-adds it under the collector with `SWU_SNEAK_DEFEAT`, runs entry triggers. Reward-build special-cases SHD_226 so the param carries the host identity (not the CardUnique flag). 2 tests (play-under-control + regroup-defeat-to-owner's-discard); reg 328/328. **SHD_208 Final Showdown DONE** — "Ready each unit you control. At the start of the regroup phase, you lose the game." Reused the SWUDeclareGameWinner primitive: OnPlayEvent readies all friendly units (`OnReadyCard`) + arms a `SWU_SHD208_LOSE` global effect; new `_SWUCheckFinalShowdownLose()` drains it in `RegroupPhaseStart` (right after `_SWUCheckConfidenceWin`, BEFORE the draw step) and declares the OTHER player the winner. Mirrors SEC_145's regroup-start win check, inverted. 3 tests (readies-your-units, lose-at-regroup→P2WIN, and the edge case: P2 empty deck + 5HP base → P1 loses before the draw so P2 wins with base intact). Note: CR §6.1 deck-out base damage was subsequently implemented (in DoDrawCard), so the ordering guard is now load-bearing — DoDrawCard no-ops once a winner is set (checking the in-process `$gWinner` too, since the serialized GAMEOVER_WINNER doesn't survive the intra-request RGS→DRAW advance), so the SHD_208 loss preempts the regroup draw's deck-out damage. reg 333/333 SHD.
  - SHD_205 Let the Wookiee Win: An opponent chooses one: <bullet>You ready up to 6 resources. You ready a friendly unit. If it's a Wookiee unit, at
  - SHD_208 Final Showdown: Ready each unit you control. At the start of the regroup phase, you lose the game.
  - SHD_226 Unrefusable Offer: Attach to a non-leader unit. Attached unit gains: "Bounty - Play this unit for free (under your control). It enters
  - SHD_248 Tech: Each friendly resource gains Smuggle. The gained Smuggle cost is that card's cost plus 2 resources and its aspect i
- [x] **Batch 14.3 — SHD_256** — **DONE.** SHD_256 Mercenary Gunship ("Action [4 resources]: Take control of this unit. **Any player may use this ability**") was NOT a new seam — the any-player unit-action infra already exists from LAW_156 Hunter For Hire (`$anyPlayerUnitActions`, surfaced on the opponent's units as `their{Arena}-N` in SWUComputeActionsData; SWUUnitAction has no controller gate). Mirrored LAW_156 exactly, only swapping the Credit-token cost for `costKind 'none' + $unitActionResourceCosts 4` (paid generically by SWUUnitAction). 2 tests (opponent-pays-4-and-takes-control + unaffordable-3-resources no-op); reg 330/330.
  - SHD_256 Mercenary Gunship: Action [4 resources]: Take control of this unit. Any player may use this ability.

