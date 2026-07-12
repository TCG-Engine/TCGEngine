# TWI — Card Implementation Plan

259 cards total: 150 Unit, 58 Event, 19 Upgrade, 18 Leader, 12 Base, 2 Token Unit. 220 needs-work, 39 auto-wired (vanilla/keyword-only/base).

Twilight of the Republic (Eternal-only set). Both defining keywords — **Coordinate** (CR §15) and **Exploit** (CR §16) — are already built in the engine, so no foundational mechanic blocks this plan. ⟳ = card already has a *partial* handler from earlier incidental work; `swusim-implement-card` should verify-then-skip rather than rebuild.

### Already Done
TWI_020, TWI_021, TWI_023, TWI_024, TWI_026, TWI_027, TWI_029, TWI_030, TWI_037, TWI_057, TWI_065, TWI_087, TWI_093, TWI_108, TWI_111, TWI_113, TWI_117, TWI_118, TWI_133, TWI_136, TWI_141, TWI_159, TWI_161, TWI_182, TWI_207, TWI_209, TWI_214, TWI_228, TWI_230, TWI_231, TWI_232, TWI_233, TWI_241, TWI_242, TWI_244, TWI_245, TWI_253, TWI_T01, TWI_T02, TWI_076, TWI_084, TWI_088, TWI_097, TWI_102, TWI_125, TWI_145, TWI_190, TWI_222, TWI_227, TWI_234, TWI_235, TWI_237, TWI_247, TWI_251, TWI_045, TWI_050, TWI_051, TWI_061, TWI_064, TWI_090, TWI_095, TWI_096, TWI_106, TWI_114, TWI_147, TWI_158, TWI_162, TWI_164, TWI_165, TWI_192, TWI_196, TWI_205, TWI_213, TWI_240, TWI_243, TWI_038, TWI_039, TWI_066, TWI_078, TWI_086, TWI_115, TWI_134, TWI_167, TWI_178, TWI_184, TWI_186, TWI_215, TWI_217, TWI_043, TWI_054, TWI_062, TWI_081, TWI_130, TWI_143, TWI_180, TWI_194, TWI_195, TWI_236, TWI_042, TWI_044, TWI_058, TWI_074, TWI_085, TWI_091, TWI_092, TWI_094, TWI_104, TWI_105, TWI_110, TWI_122, TWI_126, TWI_139, TWI_142, TWI_124, TWI_153, TWI_163, TWI_172, TWI_179, TWI_224, TWI_254, TWI_031, TWI_052, TWI_055, TWI_063, TWI_067, TWI_072, TWI_075, TWI_048, TWI_059, TWI_099, TWI_103, TWI_131, TWI_146, TWI_149, TWI_150, TWI_151, TWI_154, TWI_155, TWI_156, TWI_157, TWI_160, TWI_170, TWI_171, TWI_173, TWI_174, TWI_177, TWI_181, TWI_202, TWI_212, TWI_239, TWI_256, TWI_056, TWI_073, TWI_109, TWI_129, TWI_035, TWI_036, TWI_041, TWI_077, TWI_140, TWI_238, TWI_128, TWI_187, TWI_191, TWI_198, TWI_220, TWI_226, TWI_100, TWI_107, TWI_152, TWI_168, TWI_175, TWI_188, TWI_193, TWI_208, TWI_257, TWI_080, TWI_101, TWI_121, TWI_210, TWI_216, TWI_246, TWI_032, TWI_079, TWI_148, TWI_169, TWI_218, TWI_229, TWI_049, TWI_083, TWI_166, TWI_120, TWI_206, TWI_112, TWI_137, TWI_183, TWI_185, TWI_200, TWI_211, TWI_221, TWI_098, TWI_189, TWI_197, TWI_225, TWI_070, TWI_071, TWI_119, TWI_219, TWI_248, TWI_019, TWI_022, TWI_025, TWI_028, TWI_033, TWI_046, TWI_060, TWI_082, TWI_123, TWI_127, TWI_132, TWI_144, TWI_176, TWI_199, TWI_203, TWI_223, TWI_249, TWI_250, TWI_252, TWI_001, TWI_002, TWI_003, TWI_004, TWI_006, TWI_007, TWI_008, TWI_009, TWI_010, TWI_011, TWI_012, TWI_013, TWI_014, TWI_015, TWI_018, TWI_135, TWI_116, TWI_047, TWI_204, TWI_255

## Phase 1 — Token generators (Battle Droid / Clone Trooper) (autonomous)
- [x] **Batch 1.1 — TWI_076, TWI_084, TWI_088, TWI_097** — done, 2753/0. Token generators via `SWUCreateUnitTokens` (TWI_T01 Battle Droid / TWI_T02 Clone Trooper); TWI_084 On-Attack token buff = new `TWI_084` STAT_BUFF registry row; TWI_088 discard→deck-bottom via `_topDeckPutRemainingToBottom`. ⚠ Watch stacked fall-through event labels (`case 'SOR_124': case 'TWI_124':`).
  - TWI_076 Death by Droids: Defeat a unit that costs 3 or less. Create 2 Battle Droid tokens.
  - TWI_084 Kraken: When Played: Create 2 Battle Droid tokens. On Attack: Give each friendly token unit +1/+1 for this phase.
  - TWI_088 Reprocess: Choose up to 4 units in your discard pile. Put them on the bottom of your deck in a random order and create that ma
  - TWI_097 Captain Rex: When Played: Create 2 Clone Trooper tokens.
- [x] **Batch 1.2 — TWI_102, TWI_125, TWI_145, TWI_190** — done, 2759/0. OPTIONCHOOSE modal (TWI_102 Clones/Droids), NUMBERCHOOSE pay-any-number (TWI_125, caster Clones + opponent Droids), opponent-creates-tokens WhenPlayed (TWI_145), create-ready-tokens (TWI_190 `ready:true`).
  - TWI_102 Manufactured Soldiers: Choose one: <bullet>Create 2 Clone Trooper tokens. Create 3 Battle Droid tokens.</bullet>
  - TWI_125 The Clone Wars: Pay any number of resources. Create that many Clone Trooper tokens. Each opponent creates that many Battle Droid to
  - TWI_145 Jesse: Raid 1 (This unit gets +1/+0 while attacking.) When Played: An opponent creates 2 Battle Droid tokens.
  - TWI_190 On the Doorstep: Create 3 Battle Droid tokens and ready them.
- [x] **Batch 1.3 — TWI_222, TWI_227, TWI_234, TWI_235** — done, 2767/0. Cross-player YESNO (TWI_222 opponent-declines→droids), conditional non-Vehicle capture (TWI_227, `DoCaptureUnit` + cost gate), OnAttack exhaust-any-number Separatists → base dmg (TWI_234 MZMULTICHOOSE + WhenPlayed), WhenDefeated tokens (TWI_235).
  - TWI_222 Political Pressure: Choose an opponent. They may discard a random card from their hand. If they don't, create 2 Battle Droid tokens.
  - TWI_227 Prisoner of War: A friendly unit captures an enemy non-leader, non-Vehicle unit. If the enemy unit costs less than the friendly unit
  - TWI_234 The Invisible Hand: When Played: Create 4 Battle Droid tokens. On Attack: Exhaust any number of friendly Separatist units. Deal 1 damag
  - TWI_235 Battle Droid Legion: Exploit 2 (While playing this card, defeat up to 2 units you control. This card costs 2 resources less for each uni
- [x] **Batch 1.4 — TWI_237, TWI_247, TWI_251** — done, 2770/0. Vanilla token events (TWI_237/251) + WhenDefeated tokens (TWI_247, Restore keyword generic).
  - TWI_237 Droid Deployment: Create 2 Battle Droid tokens.
  - TWI_247 AT-TE Vanguard: Restore 3 (When this unit attacks, heal 3 damage from your base.) When Defeated: Create 2 Clone Trooper tokens.
  - TWI_251 Drop In: Create 2 Clone Trooper tokens.

## Phase 2 — Coordinate abilities (autonomous)
- [x] **Batch 2.1 — TWI_045, TWI_050, TWI_051, TWI_061** — done, 2776/0. TWI_045 Coordinate +0/+3 (new `ObjectCurrentHP` self-buff gated on `IsCoordinateActive`); TWI_050 Grit / TWI_061 Sentinel already-wired (guard tests); TWI_051 grants Coordinate-Restore 2 (new `GetConditionalKeyword_Restore_Value` line) + costs -2 with 3+ Republic units (new `$playCostModifiers`).
  - TWI_045 41st Elite Corps: Coordinate - This unit gets +0/+3. (Gain this ability while you control 3 or more units.)
  - TWI_050 Luminara Unduli: ⟳ Coordinate - Grit (Gain this keyword while you control 3 or more units. This unit gets +1/+0 for each damage on her
  - TWI_051 For The Republic: ⟳ If you control 3 or more Republic units, this upgrade costs 2 resources less to play. Attached unit gains: "Coordin
  - TWI_061 Infantry of the 212th: ⟳ Coordinate - Sentinel (Gain this keyword while you control 3 or more units. Units in this arena can't attack your n
- [x] **Batch 2.2 — TWI_064, TWI_090, TWI_095, TWI_096** — done, 2785/0. TWI_090 self +2/+2 (ObjectCurrentPower+HP); TWI_095 Coordinate WhenPlayed token (incl. self); TWI_096 self combat-damage prevent (`TWI_096` attack-marker → `$preventAttackerDmg`, mirrors JTL_193); TWI_064 reactive "opponent's 2nd card → may draw 2" via `SWUCollectOpponentPlayReactions` (gate `SWU_CARDS_PLAYED===2`) + DispatchTrigger. ⚠ reactive test needs `EffectStack-0` orchestration answer before the YESNO.
  - TWI_064 Ki-Adi-Mundi: Coordinate - When an opponent plays their second card each phase: You may draw 2 cards.
  - TWI_090 Echo: Coordinate - This unit gets +2/+2. (Gain this ability while you control 3 or more units.)
  - TWI_095 Pelta Supply Frigate: Coordinate - When Played: Create a Clone Trooper token. (Gain this ability while you control 3 or more units, inclu
  - TWI_096 Aayla Secura: Coordinate - On Attack: Prevent all combat damage that would be dealt to this unit for this attack.
- [x] **Batch 2.3 — TWI_106, TWI_114, TWI_147, TWI_158** — done, 2791/0. TWI_106 Ambush already-wired (guard); TWI_114 Cody "each OTHER friendly +1/+1 + Overwhelm" (`_SWUTwi114Bonus` in stat fns + Overwhelm conditional, Coordinate-gated); TWI_147 OnAttack draw; TWI_158 self +2/+0.
  - TWI_106 Coruscant Guard: ⟳ Coordinate - Ambush (Gain this keyword while you control 3 or more units, including this one. When you play this un
  - TWI_114 Clone Commander Cody: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) Coordinate - Each other friend
  - TWI_147 Anakin Skywalker: Coordinate - On Attack: Draw a card. (Gain this ability while you control 3 or more units.)
  - TWI_158 Clone Heavy Gunner: Coordinate - This unit gets +2/+0. (Gain this ability while you control 3 or more units.)
- [x] **Batch 2.4 — TWI_162, TWI_164, TWI_165, TWI_192** — done, 2796/0. TWI_162 WhenPlayed 2-friendly+2-enemy-same-arena; TWI_164 Coordinate Raid 2 (`GetConditionalKeyword_Raid_Value`) + WhenDefeated AoE ground; TWI_165 OnAttack may-deal-3 ground; TWI_192 OnAttack enemy -3/-0 (new STAT_DEBUFF row). OnAttack picks use MZMAYCHOOSE per the mandatory-MZCHOOSE limitation.
  - TWI_162 Reckless Torrent: Coordinate - When Played: You may deal 2 damage to a friendly unit and 2 damage to an enemy unit in the same arena.
  - TWI_164 Hevy: Coordinate - Raid 2 (Gain this keyword while you control 3 or more units. This unit gets +2/+0 while attacking.) Wh
  - TWI_165 Kit Fisto: Saboteur Coordinate - On Attack: You may deal 3 damage to a ground unit. (Gain this ability while you control 3 or 
  - TWI_192 Padmé Amidala: Coordinate - On Attack: Give an enemy unit -3/-0 for this phase. (Gain this ability while you control 3 or more uni
- [x] **Batch 2.5 — TWI_196, TWI_205, TWI_213, TWI_240** — done, 2801/0. TWI_196 Coordinate Raid 3; TWI_205 "defender -2/-0 while attacking" (`SWU_DEF_DEBUFF_2` synchronous in ExecuteSWUAttack, SOR_212 pattern, Coordinate-gated); TWI_213 Coordinate WhenPlayed capture ≤3-cost; TWI_240 self +1/+1.
  - TWI_196 Plo Koon: Ambush (When you play this unit, it may ready and attack an enemy unit.) Coordinate - Raid 3 (Gain this keyword whi
  - TWI_205 Clone Dive Trooper: Coordinate - While this unit is attacking, the defender gets -2/-0. (Gain this ability while you control 3 or more 
  - TWI_213 Sanctioner's Shuttle: Coordinate - When Played: This unit captures an enemy non-leader unit that costs 3 or less. (Gain this ability whil
  - TWI_240 332nd Stalwart: Coordinate - This unit gets +1/+1. (Gain this ability while you control 3 or more units.)
- [x] **Batch 2.6 — TWI_243** — done, 2802/0. Coordinate-Saboteur already-wired (guard test).
  - TWI_243 Republic Commando: ⟳ Coordinate - Saboteur (Gain this keyword while you control 3 or more units. When this unit attacks, ignore Sentinel

## Phase 3 — Exploit payloads (autonomous)
- [x] **Batch 3.1 — TWI_038, TWI_039, TWI_066, TWI_078** — done, 2806/0. Exploit is generic; riders: TWI_038 OnAttack enemy-space -2/-2; TWI_066 OnAttack create droid; TWI_039 WhenPlayed -4/-0 + new `CANT_ATTACK` phase marker (BeginSWUAttack no-op + SWUGetValidAttackTargets glow-off); TWI_078 mass-defeat opponent's units (event).
  - TWI_038 Providence Destroyer: Exploit 2 (While playing this card, defeat up to 2 units you control. This card costs 2 resources less for each uni
  - TWI_039 Malevolence: Exploit 4 Restore 2 When Played: Give an enemy unit -4/-0 for this phase. It can't attack for this phase.
  - TWI_066 Multi-Troop Transport: Exploit 2 (While playing this card, defeat up to 2 units you control. This card costs 2 resources less for each uni
  - TWI_078 The Invasion of Christophsis: Exploit 4 Choose an opponent. Defeat each unit that player controls.
- [~] **Batch 3.2 — TWI_086, TWI_115, TWI_134, TWI_138** — 3/4 done, 2809/0. TWI_086 WhenPlayed return-up-to-3-defeated-this-phase (SOR_091 multiset); TWI_115 already-done (3 existing tests, verify-only); TWI_134 OnAttack +3/+0 after another Separatist attacked (new `SWU_ATTACKED_SEPARATIST` count flag). **⚠ TWI_138 DEFERRED** — "for each unit you exploited, deal damage = its power" needs EXPLOIT_RESOLVE to record exploited-unit powers (Exploit-internals coupling); backlog.
  - TWI_086 Admiral Trench: Exploit 1 When Played: Return up to 3 units that were defeated this phase from your discard pile to your hand.
  - TWI_115 Osi Sobeck: ⟳ Exploit 3 When Played: This unit captures an enemy non-leader ground unit with cost equal to or less than the numbe
  - TWI_134 Asajj Ventress: Exploit 2 (While playing this card, defeat up to 2 units you control. This card costs 2 resources less for each uni
  - TWI_138 Count Dooku: Exploit 2 Overwhelm When Played: For each unit you exploited while playing this card, you may deal damage to an ene
- [x] **Batch 3.3 — TWI_167, TWI_178, TWI_184, TWI_186** — done, 2813/0. TWI_167 WhenPlayed may-deal-2-ground; TWI_178 event ready-up-to-3 + +1/+0 + Overwhelm-this-phase (new `TWI_178` GRANT_KEYWORD token; decline Exploit then ready); TWI_184 reactive own-play-Separatist → exhaust ≤cost (SWUCollectOwnPlayReactions, cost in trigger slot); TWI_186 OnAttack ready N resources per friendly defeated (`SWU_FRIENDLY_DEFEATED`).
  - TWI_167 Heavy Persuader Tank: Exploit 2 (While playing this card, defeat up to 2 units you control. This card costs 2 resources less for each uni
  - TWI_178 Planetary Invasion: Exploit 3 Ready up to 3 units. Each of those units gets +1/+0 and gains Overwhelm for this phase.
  - TWI_184 Tactical Droid Commander: Exploit 2 When you play another Separatist unit: You may exhaust a unit that costs the same as or less than the pla
  - TWI_186 San Hill: Exploit 3 (While playing this card, defeat up to 3 units you control. This card costs 2 resources less for each uni
- [x] **Batch 3.4 — TWI_215, TWI_217** — done, 2815/0. WhenPlayed riders: TWI_215 may-return ≤3-cost non-leader (BOUNCE_UNIT); TWI_217 exhaust enemy ground unit.
  - TWI_215 Geonosis Patrol Fighter: Exploit 2 (While playing this card, defeat up to 2 units you control. This card costs 2 resources less for each uni
  - TWI_217 Tri-Droid Suppressor: Exploit 2 (While playing this card, defeat up to 2 units you control. This card costs 2 resources less for each uni

## Phase 4 — Conditional keyword grants (while you control X) (autonomous)
- [x] **Batch 4.1 — TWI_043, TWI_054, TWI_062, TWI_081** — done, 2821/0. TWI_043 WhenDefeated create-clone (Sentinel already-wired) + TWI_054 Sentinel / TWI_081 Ambush guards; TWI_062 Restore-2-while-undamaged (`GetConditionalKeyword_Restore_Value`, Damage===0).
  - TWI_043 Outspoken Representative: ⟳ While you control another Republic unit, this unit gains Sentinel. (Units in this arena can't attack your non-Senti
  - TWI_054 Duchess's Champion: ⟳ While an opponent controls 3 or more units, this unit gains Sentinel. (Units in this arena can't attack your non-Se
  - TWI_062 Daughter of Dathomir: While this unit is undamaged, it gains Restore 2. (When this unit attacks, heal 2 damage from your base.)
  - TWI_081 Droid Commando: ⟳ While you control another Separatist unit, this unit gains Ambush. (When you play this unit, it may ready and attac
- [x] **Batch 4.2 — TWI_130, TWI_143, TWI_180, TWI_194** — done, 2826/0. TWI_130 +1/+0-while-Trooper (Overwhelm/Saboteur-while-Mandalorian already wired); TWI_143 +1/+0 + Saboteur while enemy defeated this phase (`SWU_ENEMY_DEFEATED`); TWI_180 Raid 2-while-Separatist; TWI_194 Ambush already-wired + new Action [2 res] return-self-and-upgrades-to-hand (`SWUReturnUpgradeToHand` + `SWUBounceUnit`).
  - TWI_130 Bo-Katan Kryze: ⟳ While you control another Mandalorian unit, this unit gains Overwhelm and Saboteur. While you control another Troop
  - TWI_143 Jyn Erso: While an enemy unit has been defeated this phase, this unit gets +1/+0 and gains Saboteur.
  - TWI_180 Separatist Commando: While you control another Separatist unit, this unit gains Raid 2. (It gets +2/+0 while attacking.)
  - TWI_194 Ahsoka Tano: ⟳ While you control fewer units than an opponent (including this unit), this unit gains Ambush. Action [2 resources]:
- [x] **Batch 4.3 — TWI_195, TWI_236** — done, 2831/0. TWI_195 can't-be-attacked-while-exhausted (SWUGetValidAttackTargets, SOR_142 pattern) + OnAttack discard-top / off-aspect-from-base → deal 2 (`SWUCardAspectIcons` intersect); TWI_236 host-dependent cost -2 on General Grievous (`SWUComputePlayCost` $host param, SHD_126 pattern) + non-Vehicle attach + Overwhelm grant.
  - TWI_195 Sabine Wren: While this unit is exhausted, she can't be attacked (unless she gains Sentinel). On Attack: You may discard a card 
  - TWI_236 Grievous's Wheel Bike: While playing this upgrade on General Grievous, it costs 2 resources less to play. Attach to a non-Vehicle unit. At

## Phase 5 — Stat buffs (give +X/+X for this phase) (autonomous)
- [x] **Batch 5.1 — TWI_042, TWI_044, TWI_058, TWI_074** — done, 2837/0. TWI_042 healed-this-phase +1/+0 (new `SWU_HEALED_PHASE` per-unit marker set in OnHealUnit; test via real heal — ⚠ GIVEN 4th-field marker doesn't feed ObjectCurrentPower in the harness); TWI_044 heal-up-to-2 + self-damage (SOR_075 pattern); TWI_058 +1/+1 while Force unit/upgrade (`_SWUControlsForceUnitOrUpgrade`); TWI_074 event give-Sentinel + initiative→+2/+2.
  - TWI_042 Barriss Offee: Each friendly unit that was healed this phase gets +1/+0.
  - TWI_044 Kashyyyk Defender: Grit (This unit gets +1/+0 for each damage on it.) When Played: Heal up to 2 damage from another unit and deal that
  - TWI_058 Padawan Starfighter: While you control a Force unit or a Force upgrade, this unit gets +1/+1.
  - TWI_074 Guarding the Way: Give a unit Sentinel for this phase. (Units in its arena can't attack your non-Sentinel units or your base.) If you
- [x] **Batch 5.2 — TWI_085, TWI_091, TWI_092, TWI_094** — done, 2843/0. TWI_085 OnAttack buff-up-to-1/2-others (initiative-gated MZMULTICHOOSE); TWI_091 WhenPlayed may-attack-Republic +2/+0 (LOF_111 pattern, `SWUAddAttackPowerBonus`); TWI_092 +0/+1 to other Heroism units (aspect field-passive); TWI_094 +1/+0 to tokens field-passive + OnAttack create Clone.
  - TWI_085 Kalani: On Attack: You may choose another unit. If you have the initiative, you may choose up to 2 other units instead. Giv
  - TWI_091 Republic Tactical Officer: When Played: You may attack with a Republic unit. It gets +2/+0 for this attack.
  - TWI_092 Admiral Yularen: Restore 1 Each other friendly Heroism unit gets +0/+1.
  - TWI_094 Shaak Ti: Each friendly token unit gets +1/+0. On Attack: Create a Clone Trooper token.
- [x] **Batch 5.3 — TWI_104, TWI_105, TWI_110, TWI_122** — done, 2847/0. TWI_104 WhenDefeated may-buff-Trooper; TWI_105 unit-Action [2 res, exhaust] attack-with-unit +2/+0; TWI_110 Huyang aura +2/+2 (LOF_191 `SWU_TWI110_{src}_{tgt}` link); TWI_122 upgrade +1/+1-per-Trooper. ⚠ **`$unitAbilities`/`$unitActionCostKind`/`$unitActionResourceCosts` are RESET (`= []`) mid-CardDQHandlers.php (~L12798) — register unit-Actions AFTER that line or the entry is silently wiped** (cost a debug cycle; `SWUGetUnitActionProvider` returned '').
  - TWI_104 Obedient Vanguard: Raid 1 (This unit gets +1/+0 while attacking.) When Defeated: You may give a Trooper unit +2/+2 for this phase.
  - TWI_105 Steadfast Senator: Action [2 resources, Exhaust]: Attack with a unit. It gets +2/+0 for this attack.
  - TWI_110 Huyang: When Played: Choose another friendly unit. While this unit is in play, the chosen unit gets +2/+2.
  - TWI_122 Squad Support: Attach to a non-leader unit. Attached unit gains: "This unit gets +1/+1 for each Trooper unit you control."
- [x] **Batch 5.4 — TWI_124, TWI_126, TWI_139, TWI_142** — done, 2851/0. TWI_124 already-done (SOR_124 reprint); TWI_126 event each-friendly +1/+1; TWI_139 attack-with-unit +1/+0-per-defender-damage (`TWI_139` attack-marker read in ExecuteSWUAttack); TWI_142 self +2/+0 while own base ≥15 dmg. ⚠ **A `Pass`→regroup with an EMPTY deck triggers deck-out damage (3/undrawn card, ~+6 to base) — seed `WithP1Deck` for any base-damage-threshold test that passes to regroup.**
  - TWI_124 Tactical Advantage: ⟳ Give a unit +2/+2 for this phase.
  - TWI_126 Encouraging Leadership: Give each friendly unit +1/+1 for this phase.
  - TWI_139 Corner the Prey: Attack with a unit. It gets +1/+0 for this attack for each damage on the defender at the start of this attack.
  - TWI_142 Anakin's Interceptor: While your base has 15 or more damage on it, this unit gets +2/+0.
- [x] **Batch 5.5 — TWI_153, TWI_163, TWI_172, TWI_179** — done, 2855/0. TWI_153 up-to-3 same-Trait +2/+0 (trait-intersection validated in handler); TWI_163 +2/+0 while another Trooper; TWI_172 attack-with-non-leader gains Grit-this-attack (`SWUMakeTurnEffect('GRIT',...,ATTACK)`); TWI_179 OnAttack may-exhaust Droid/Grievous → +2/+0.
  - TWI_153 Bold Resistance: Choose up to 3 units that share the same Trait. Each of those units gets +2/+0 for this phase.
  - TWI_163 Relentless Rocket Droid: While you control another Trooper unit, this unit gets +2/+0.
  - TWI_172 Grim Resolve: Attack with a non-leader unit. It gains Grit for this attack. (It gets +1/+0 for each damage on it.)
  - TWI_179 Soulless One: On Attack: You may exhaust a friendly Droid unit or General Grievous (leader or unit). If you do, this unit gets +2
- [x] **Batch 5.6 — TWI_224, TWI_254** — done, 2857/0. TWI_224 event attack-with-unit +2/+0 + Saboteur-this-attack; TWI_254 cost -1 while control a Trooper (`$playCostModifiers`).
  - TWI_224 Breaking In: Attack with a unit. It gets +2/+0 and gains Saboteur for this attack. (When this unit attacks, ignore Sentinel and 
  - TWI_254 Volunteer Soldier: Raid 1 (This unit gets +1/+0 while attacking.) If you control a Trooper unit, this unit costs 1 resource less to pl

## Phase 6 — Stat debuffs (give -X/-X for this phase) (autonomous)
- [x] **Batch 6.1 — TWI_031, TWI_052, TWI_055, TWI_063** — done, 2861/0. TWI_031 WhenPlayed may -1/-1 if friendly defeated; TWI_052 -4/-4 to a unit that entered this phase (`SWU_PLAYED_UNIT_{uid}`); TWI_055 -2/-2 + conditional second (fewer-units gate); TWI_063 OnAttack enemy -1/-1. (All new STAT_DEBUFF rows.)
  - TWI_031 Rune Haako: When Played: If a friendly unit was defeated this phase, you may give a unit -1/-1 for this phase.
  - TWI_052 Hello There: Choose a unit that entered play this phase. It gets -4/-4 for this phase.
  - TWI_055 Equalize: Give a unit -2/-2 for this phase. Then, if you control fewer units than that unit's controller, give another unit -
  - TWI_063 Vulture Interceptor Wing: On Attack: Give an enemy unit -1/-1 for this phase.
- [x] **Batch 6.2 — TWI_067, TWI_072, TWI_075** — done, 2865/0. TWI_067 WhenPlayed AoE enemy-ground -5/-0 + RegroupPhaseStart self-heal 5; TWI_072 mark-friendly → enemies attacking it -4/-0 (SWUCombatDamage defender-marker, SOR_071 pattern); TWI_075 AoE all-enemy -1/-1.
  - TWI_067 The Zillo Beast: When Played: Give each enemy ground unit -5/-0 for this phase. When the regroup phase starts: Heal 5 damage from th
  - TWI_072 I Have the High Ground: Choose a friendly unit. Each enemy unit gets -4/-0 while attacking that unit this phase.
  - TWI_075 Disruptive Burst: Give each enemy unit -1/-1 for this phase.

## Phase 7 — Direct damage (autonomous)
- [x] **Batch 7.1 — TWI_048, TWI_059, TWI_099, TWI_103** — done, 2873/0. TWI_059 WhenPlayed self-damage 2 (JTL_248 pattern); TWI_048 both windows share a bundled MZMAYCHOOSE (1 to self + 2 to another space unit); TWI_099 event deals to enemy = friendly units in ITS arena (per-arena count, continuation); TWI_103 first **phase-marker-granted When Defeated** — new `TWI_103` MARKER row, detected in `CollectWhenDefeatedTriggers` via the removed unit's TurnEffects, new DispatchTrigger case → deal 2 to an enemy unit.
  - TWI_048 Obi-Wan's Aethersprite: When Played/On Attack: You may deal 1 damage to this unit and 2 damage to another space unit.
  - TWI_059 Royal Guard Attaché: When Played: Deal 2 damage to this unit.
  - TWI_099 Synchronized Strike: Deal damage to an enemy unit equal to the number of units you control in its arena.
  - TWI_103 Pyrrhic Assault: For this phase, each friendly unit gains: "When Defeated: Deal 2 damage to an enemy unit."
- [x] **Batch 7.2 — TWI_131, TWI_146, TWI_149, TWI_150** — done, 2880/0. TWI_131 WhenDefeated choose-a-base (DEAL_BASE_DAMAGE|2); TWI_146 shared WhenPlayed/WhenDefeated YESNO → deal 2 to own base + `DoTopDeckSearch` top-8 Tactic; TWI_149 WhenPlayed deal 1/friendly-Republic (incl. self) to chosen enemy; TWI_150 OnAttack base≥15 → AoE 1 to each enemy ground (UID-snapshot).
  - TWI_131 OOM-Series Officer: When Defeated: Deal 2 damage to a base.
  - TWI_146 Steela Gerrera: When Played/When Defeated: You may deal 2 damage to your base. If you do, search the top 8 cards of your deck for a
  - TWI_149 Low Altitude Gunship: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) When Played: Choose an enemy u
  - TWI_150 Saw Gerrera: Raid 2 On Attack: If your base has 15 or more damage on it, deal 1 damage to each enemy ground unit.
- [x] **Batch 7.3 — TWI_151, TWI_154, TWI_155, TWI_156** — done, 2888/0. TWI_151 base-damage cost mod (`-floor(dmg/5)`) + WhenPlayed/OnAttack deal 2 to chosen + each same-name enemy (CardTitle match, UID snapshot); TWI_154 OnAttack empty-hand may-deal-3-ground; TWI_155 upgrade WhenPlayed deal 2 to host; TWI_156 Unlimited Power = 4 chained ordered picks (4/3/2/1) accumulated by UID → simultaneous `SWUDealSplitDamage` (4-pick chain drives cleanly in regression).
  - TWI_151 Resolute: This unit costs 1 resource less to play for every 5 damage on your base. When Played/On Attack: Deal 2 damage to an
  - TWI_154 Mister Bones: On Attack: If you have no cards in your hand, you may deal 3 damage to a ground unit.
  - TWI_155 Twice the Pride: When Played: Deal 2 damage to attached unit.
  - TWI_156 Unlimited Power: Deal 4 damage to a unit, 3 damage to a second unit, 2 damage to a third unit, and 1 damage to a fourth unit. (All d
- [x] **Batch 7.4 — TWI_157, TWI_160, TWI_170, TWI_171** — done, 2895/0. TWI_157 unit-Action [2 res, Exhaust] deal 2 to a base (registered after the `$unitAbilities=[]` reset); TWI_160 WhenPlayed another-Separatist gate → 2 to enemy base; TWI_170 event `DEAL_TARGET|2` unit-or-base; TWI_171 event deal 2 then may-deal-1 to another unit in the SAME arena (continuation).
  - TWI_157 Disaffected Senator: Action [2 resources, Exhaust]: Deal 2 damage to a base.
  - TWI_160 Vanguard Droid Bomber: When Played: If you control another Separatist unit, deal 2 damage to an enemy base.
  - TWI_170 Daring Raid: Deal 2 damage to a unit or base.
  - TWI_171 Grenade Strike: Deal 2 damage to a unit. You may deal 1 damage to another unit in the same arena.
- [x] **Batch 7.5 — TWI_173, TWI_174, TWI_177, TWI_181** — done, 2901/0. TWI_173 AoE 2 to each ground; TWI_174 Open Fire deal 4 to a unit; TWI_177 symmetric attrition (each player defeats a resource + discards 2 via `SWUDiscardCards`/inline caster-discard excluding the event) then AoE 4 to each ground (AoE independent of discards → order immaterial); TWI_181 shared WhenPlayed/WhenDefeated may-deal-1.
  - TWI_173 Blood Sport: Deal 2 damage to each ground unit.
  - TWI_174 Open Fire: Deal 4 damage to a unit.
  - TWI_177 Guerilla Insurgency: Each player defeats a resource they control and discards 2 cards from their hand. Deal 4 damage to each ground unit
  - TWI_181 Elite P-38 Starfighter: When Played/When Defeated: You may deal 1 damage to a unit.
- [x] **Batch 7.6 — TWI_202, TWI_212, TWI_239, TWI_256** — done, 2908/0. TWI_202 OnAttack random 2 to a unit-or-base (both bases always in pool → not scriptable; `TWI202_HIT` log tag + smoke-verified a real target took 2); TWI_212 WhenPlayed may-pay-2 → deal 2; TWI_239 Execute Order 66 = 6 to each Jedi + defeated-Jedi's controller makes a Clone (UID snapshot of controller); TWI_256 upgrade WhenPlayed may-deal-1-ground + non-Vehicle attach.
  - TWI_202 Jar Jar Binks: On Attack: Deal 2 damage to a random unit or base.
  - TWI_212 Freelance Assassin: When Played: You may pay 2 resources. If you do, deal 2 damage to a unit.
  - TWI_239 Execute Order 66: Deal 6 damage to each Jedi unit. For each unit defeated this way, its controller creates a Clone Trooper token.
  - TWI_256 Hold-Out Blaster: Attach to a non-Vehicle unit. When Played: You may have attached unit deal 1 damage to a ground unit.

## Phase 8 — Heal / Restore payloads (autonomous)
- [x] **Batch 8.1 — TWI_056, TWI_073, TWI_109, TWI_129** — done, 2914/0. TWI_056 unit-Action heal 2 unit-or-base; TWI_073 event heal 3 + create Battle Droid (collect targets before token); TWI_109 WhenPlayed another-Republic gate → may heal 3 from a base; TWI_129 grants Restore 2 (GRANT_KEYWORD_VALUE row) AND a When-Defeated create-Clone via ONE `TWI_129` marker read by both the Restore reader and CollectWhenDefeatedTriggers.
  - TWI_056 Compassionate Senator: Action [2 resources, Exhaust]: Heal 2 damage from a unit or base.
  - TWI_073 Grievous Reassembly: Heal 3 damage from a unit. Create a Battle Droid token.
  - TWI_109 501st Liberator: When Played: If you control another Republic unit, you may heal 3 damage from a base.
  - TWI_129 In Defense of Kamino: For this phase, each friendly Republic unit gains Restore 2 and: "When Defeated: Create a Clone Trooper token."

## Phase 9 — Targeted defeat (autonomous)
- [x] **Batch 9.1 — TWI_035, TWI_036, TWI_041, TWI_077** — done, 2923/0. TWI_035 OnAttack may-defeat-another-friendly → draw; TWI_036 WhenPlayed defeat enemy with ≤2 remaining HP (ObjectCurrentHP−Damage); TWI_041 defeat non-leader → deal its power to your own base (power snapshot before defeat); TWI_077 Vanquish defeat non-leader (`DEFEAT_UNIT`). ⚠ `SWUDefeatUnit($player, $mzID)` — 2 args.
  - TWI_035 Morgan Elsbeth: Restore 1 On Attack: You may defeat another friendly unit. If you do, draw a card.
  - TWI_036 Devastating Gunship: Grit (This unit gets +1/+0 for each damage on it.) When Played: Defeat an enemy unit with 2 or less remaining HP.
  - TWI_041 Lethal Crackdown: Defeat a non-leader unit. Deal damage to your base equal to that unit's power.
  - TWI_077 Vanquish: Defeat a non-leader unit.
- [x] **Batch 9.2 — TWI_140, TWI_238** — done, 2923/0. TWI_140 defeat a friendly → deal 4 to a unit (continuation); TWI_238 Merciless Contest each-player-defeats-own-non-leader (caster `DEFEAT_UNIT` + opponent `OPP_DEFEAT_OWN_UNIT|1`).
  - TWI_140 Self-Destruct: Defeat a friendly unit. If you do, deal 4 damage to a unit.
  - TWI_238 Merciless Contest: Each player chooses a non-leader unit they control. Defeat those units.

## Phase 10 — Capture / steal (autonomous)
- [x] **Batch 10.1 — TWI_128, TWI_187** — done, 2925/0. TWI_128 already implemented (SHD_131 reprint, has test). TWI_187 Cad Bane: WhenPlayed capture-budget loop (≤3 units, total remaining HP ≤8, self-re-queuing MZMAYCHOOSE); OnAttack cross-player rescue-offer → defending player's captive rescued + controller draws 2 (smoke-verified end-to-end since captives can't be GIVEN-seeded).
  - TWI_128 Take Captive: ⟳ A friendly unit captures an enemy non-leader unit in the same arena. (Put the captured card facedown under that uni
  - TWI_187 Cad Bane: When Played: This unit captures up to 3 enemy non-leader units with a total of 8 or less remaining HP. On Attack: T

## Phase 11 — Return-to-hand (bounce) (autonomous)
- [x] **Batch 11.1 — TWI_191, TWI_198, TWI_220, TWI_226** — done, 2930/0. TWI_191 WhenPlayed may-bounce friendly non-leader non-Vehicle; TWI_198 shared WhenPlayed/OnAttack may-bounce enemy non-leader with power < self; TWI_220 already-wired (added an enemy-bounce-immunity guard test vs Waylay); TWI_226 already-done (SOR_222 reprint, has test).
  - TWI_191 Wolf Pack Escort: When Played: You may return a friendly non-leader, non-Vehicle unit to its owner's hand.
  - TWI_198 Enfys Nest: Saboteur When Played/On Attack: You may return an enemy non-leader unit with less power than this unit to its owner
  - TWI_220 Shadowed Intentions: ⟳ Attached unit gains: "This unit can't be captured, defeated, or returned to its owner's hand by enemy card abilitie
  - TWI_226 Waylay: ⟳ Return a non-leader unit to its owner's hand.

## Phase 12 — Draw / deck search / scry (autonomous)
- [~] **Batch 12.1 — TWI_068, TWI_100, TWI_107, TWI_152** — 3/4 done, 2941/0. TWI_100 conditional draw 3 (≥3 Official); TWI_107 WhenPlayed draw 1; TWI_152 upgrade WhenPlayed if-Mace-Windu draw 2 (non-Vehicle attach). **⚠ TWI_068 DEFERRED** — Foresight grants an upgrade-attached regroup-phase-start "name a card, peek top, may draw if match" (NAMECARD + new regroup-grant hook + multi-phase test); intricate, buildable, parked.
  - TWI_068 Foresight: Attached unit gains: "When the regroup phase starts (before drawing cards): Name a card, then look at the top card 
  - TWI_100 Petition the Senate: If you control 3 or more Official units, draw 3 cards.
  - TWI_107 Patrolling V-Wing: When Played: Draw a card.
  - TWI_152 Mace Windu's Lightsaber: Attach to a non-Vehicle unit. When Played: If attached unit is Mace Windu, draw 2 cards.
- [x] **Batch 12.2 — TWI_168, TWI_175, TWI_188, TWI_193** — done, 2941/0. TWI_168 upgrade WhenPlayed opp-more-units → draw 1; TWI_175 draw 3; TWI_188 peek N-defeated (N = both players' `SWU_FRIENDLY_DEFEATED`), draw 1, rest bottom (`DoTopDeckSearch`); TWI_193 R2-D2 may-discard → search top 3 draw 1.
  - TWI_168 Old Access Codes: When Played: If an opponent controls more units than you, draw a card.
  - TWI_175 Strategic Analysis: Draw 3 cards.
  - TWI_188 Wartime Profiteering: Look at cards from the top of your deck equal to the number of units that were defeated this phase. Draw 1 and put 
  - TWI_193 R2-D2: When Played: You may discard a card from your hand. If you do, search the top 3 cards of your deck for a card and d
- [~] **Batch 12.3 — TWI_201, TWI_208, TWI_257** — 2/3 done, 2945/0. TWI_208 WhenPlayed draw 1 / WhenDefeated discard-a-hand-card; TWI_257 draw 2 + (no token units) put 2 hand cards on deck bottom (`_topDeckPutRemainingToBottom`). **⚠ TWI_201 DEFERRED** — Aid from the Innocent needs a search-top-10-and-DISCARD-2 variant + a new discounted play-from-discard modifier ("play the discarded cards this phase, −2 each"); the −2 discount can't ride a custom discard-entry field (doesn't survive the request boundary), so it needs a new `TPP`-family modifier. Parked.
  - TWI_201 Aid from the Innocent: Search the top 10 cards of your deck for 2 Heroism non-unit cards and discard them. (Put the other cards on the bot
  - TWI_208 Favorable Delegate: When Played: Draw a card. When Defeated: Discard a card from your hand.
  - TWI_257 Private Manufacturing: Draw 2 cards. If you control no token units, put 2 cards from your hand on the bottom of your deck in any order.

## Phase 13 — Reactive on-play triggers (autonomous)
- [x] **Batch 13.1 — TWI_080, TWI_101, TWI_121, TWI_210** — done, 2951/0. TWI_080/101 own-play "another unit" reactions (`SWUCollectOwnPlayReactions` observer → may exhaust self → droid / search-top-4-unit); TWI_121 granted-if-Jedi OnAttack "next unit -2" armed discount (`SWU_TWI121_DISCOUNT_NEXT`, OnAttackFromUpgrade seam, non-Vehicle attach) — **smoke-verified** (the combat-armed-flag-then-play chain isn't drivable in the in-process regression runner); TWI_210 already-done (has tests). **⚠ PRE-EXISTING ENGINE BUG found:** own-play reactions (`SWUCollectOwnPlayReactions`) do NOT fire when the played unit enters the **SPACE** arena — reproduced with the existing LOF_087 Eighth Brother too, so it's engine-wide (affects SOR_182/LOF_087/TWI_080/101/etc.). Flag at retro.
  - TWI_080 Poggle the Lesser: When you play another unit: You may exhaust this unit. If you do, create a Battle Droid token.
  - TWI_101 Mas Amedda: When you play another unit: You may exhaust this unit. If you do, search the top 4 cards of your deck for a unit, r
  - TWI_121 General's Blade: Attach to a non-Vehicle unit. If attached unit is a Jedi, it gains: "On Attack: The next unit you play this phase c
  - TWI_210 Lux Bonteri: ⟳ When an opponent plays a card: If that opponent paid less than the card's cost to play it, ready or exhaust a unit.
- [x] **Batch 13.2 — TWI_216, TWI_246** — done, 2951/0. TWI_216 Fives own-play-event reaction → may recycle a Clone from discard to deck bottom → draw; TWI_246 Tranquility WhenPlayed may-return-Republic-from-discard + OnAttack arm "next 3 Republic cards -1" (`SWU_TWI246_DISCOUNT` count-based, consumed per Republic play, cleared at RGS).
  - TWI_216 Fives: Saboteur When you play an event: You may put a Clone unit from your discard pile on the bottom of your deck. If you
  - TWI_246 Tranquility: When Played: You may return a Republic unit from your discard pile to your hand. On Attack: Each of the next 3 Repu

## Phase 14 — When-Defeated payloads (autonomous)
- [~] **Batch 14.1 — TWI_032, TWI_069, TWI_079, TWI_148** — 3/4 done, 2958/0. TWI_032/079 WhenDefeated create Battle Droid; TWI_148 WhenDefeated each-opponent-discards (`SWUDiscardCards`; Saboteur keyword). **⚠ TWI_069 DEFERRED** — Roger Roger "When Defeated: Attach this upgrade to a friendly Battle Droid token" needs an upgrade-relocate-on-host-defeat hook (intercept `SWUDiscardHostSubcards` to re-attach instead of discard); parked.
  - TWI_032 Wartime Trade Official: When Defeated: Create a Battle Droid token.
  - TWI_069 Roger Roger: When Defeated: Attach this upgrade to a friendly Battle Droid token.
  - TWI_079 Confederate Courier: When Defeated: Create a Battle Droid token.
  - TWI_148 Senatorial Corvette: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) When Defeated: Each opponent 
- [x] **Batch 14.2 — TWI_169, TWI_218, TWI_229** — done, 2958/0. TWI_169 upgrade grants Raid 2 (`GetConditionalKeyword_Raid_Value` + `_SWUUnitHasUpgrade`) + granted WhenDefeated create Clone (subcard scan); TWI_218 granted WhenDefeated create Battle Droid (subcard scan, +1/+1 upgrade); TWI_229 shared WhenPlayed/WhenDefeated create Battle Droid.
  - TWI_169 Clone Cohort: Attached unit gains Raid 2 and: "When Defeated: Create a Clone Trooper token."
  - TWI_218 Droid Cohort: Attached unit gains, "When Defeated: Create a Battle Droid token."
  - TWI_229 Battle Droid Escort: When Played/When Defeated: Create a Battle Droid token.

## Phase 15 — When-attacked triggers (autonomous)
- [x] **Batch 15.1 — TWI_049, TWI_083, TWI_166** — done, 2961/0. TWI_049/083 On-Defense ("when this unit is attacked") create Clone/Battle Droid; TWI_166 Aurra Sing ready-on-enemy-ground-base-attack (`_SWUTwi166ReadyOnBaseAttack`, mirrors ASH_160 without once-per-round) + Overwhelm keyword.
  - TWI_049 Knight of the Republic: When this unit is attacked: Create a Clone Trooper token.
  - TWI_083 General's Guardian: When this unit is attacked: Create a Battle Droid token.
  - TWI_166 Aurra Sing: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) When an enemy ground unit atta

## Phase 16 — Activated [Exhaust] unit abilities (autonomous)
- [x] **Batch 16.1 — TWI_120, TWI_206** — done, 2962/0. TWI_120 already-done (Strategic Acumen upgrade-granted play-a-unit-discounted, reuses SOR_093, has test); TWI_206 unit-Action [2 res, Exhaust] exhaust a ready unit with power ≤4 (`SWUUnitActionAffordable` valid-target gate).
  - TWI_120 Strategic Acumen: ⟳ Attached unit gains: "Action [Exhaust]: Play a unit from your hand. It costs 1 resource less."
  - TWI_206 Independent Senator: Action [2 resources, Exhaust]: Exhaust a unit with 4 or less power.

## Phase 17 — Ready / exhaust manipulation (autonomous)
- [x] **Batch 17.1 — TWI_112, TWI_137, TWI_183, TWI_185** — done, 2968/0. TWI_112 WhenPlayed initiative→droid (Ambush kw); TWI_137 already-done (Savage Opress); TWI_183 OnAttack defender-no-ready-resources→droid (Raid 2 kw); TWI_185 WhenPlayed may-exhaust-enemy-unit + OnAttack may-exhaust-enemy-resource.
  - TWI_112 Subjugating Starfighter: Ambush (When you play this unit, it may ready and attack an enemy unit.) When Played: If you have the initiative, c
  - TWI_137 Savage Opress: ⟳ When Played: If you control fewer units (including this one) than an opponent, ready this unit.
  - TWI_183 Rush Clovis: Raid 2 On Attack: If the defending player controls no ready resources, create a Battle Droid token.
  - TWI_185 Ziro the Hutt: When Played: For each opponent, you may exhaust a unit that player controls. On Attack: For each opponent, you may 
- [x] **Batch 17.2 — TWI_200, TWI_211, TWI_221** — done, 2968/0. TWI_200 event exhaust non-unique unit + create Clone; TWI_211 Sly Moore take-control enemy token + ready + `TEMPORARY_STEAL` (regroup returns it); TWI_221 event exhaust-friendly → exhaust-enemy chain.
  - TWI_200 Creative Thinking: Exhaust a non-unique unit. Create a Clone Trooper token.
  - TWI_211 Sly Moore: When Played: Take contol of an enemy token unit and ready it. At the start of the regroup phase, that token unit's 
  - TWI_221 In Pursuit: Exhaust a friendly unit. If you do, exhaust an enemy unit.

## Phase 18 — Cost modifiers (autonomous)
- [x] **Batch 18.1 — TWI_098, TWI_189, TWI_197, TWI_225** — done, 2973/0. TWI_098 cost −1/opponent-unit (`$playCostModifiers`, Sentinel kw); TWI_197 cost −1 if control 3+ units; TWI_189 play a defeated-this-phase discard unit at −2, ready, `SWU_SNEAK_DEFEAT` (regroup-defeat) via `SWUPlayDiscardUnitDiscounted`; TWI_225 (control exactly 1 unit) play a trait-sharing non-Vehicle hand unit at −5 (nested `ActivateCard` with turn/PASS save-restore).
  - TWI_098 Republic Defense Carrier: This unit costs 1 resource less to play for each unit controlled by the opponent who controls the most units. Senti
  - TWI_189 Unnatural Life: Play a unit that was defeated this phase from your discard pile. It costs 2 resources less and enters play ready. A
  - TWI_197 Republic Attack Pod: If you control 3 or more units, this unit costs 1 resource less to play.
  - TWI_225 Now There Are Two of Them: If you control exactly one unit, play a non-Vehicle unit from your hand that shares a Trait with the unit you contr

## Phase 19 — Upgrade abilities (autonomous)
- [x] **Batch 19.1 — TWI_070, TWI_071, TWI_119, TWI_219** — done, 2978/0. TWI_070 WhenPlayed exhaust host (-2/-2); TWI_071 grant Sentinel (`HasConditionalKeyword_Sentinel`); TWI_119 grant Overwhelm + token-only attach; TWI_219 WhenPlayed `CANT_BE_ATTACKED` this phase (unless Sentinel).
  - TWI_070 Perilous Position: When Played: Exhaust attached unit.
  - TWI_071 Unshakeable Will: Attached unit gains Sentinel. (Units in this arena can't attack your non-Sentinel units or your base.)
  - TWI_119 Nameless Valor: Attach to a token unit. Attached unit gains Overwhelm. (When attacking an enemy unit, deal excess damage to the opp
  - TWI_219 On Top of Things: When Played: Attached unit can't be attacked this phase (unless it has Sentinel).
- [x] **Batch 19.2 — TWI_248** — done, 2978/0. Ahsoka's Padawan Lightsaber: non-Vehicle attach + WhenPlayed if-host-is-Ahsoka-Tano may-attack-with-a-unit (LAW_157 pattern).
  - TWI_248 Ahsoka's Padawan Lightsaber: Attach to a non-Vehicle unit. When Played: If attached unit is Ahsoka Tano, you may attack with a unit.

## Phase 20 — Ability bases (autonomous)
- [x] **Batch 20.1 — TWI_019, TWI_022, TWI_025, TWI_028** — done, 2982/0. TWI_019/028 base passives (leader units +0/+1 / +1/+0 via `ObjectCurrentHP`/`Power` base-presence checks); TWI_022/025 when-deploy-leader base triggers (create 2 Battle Droids / draw a card, inline in `SWUDeployLeader`).
  - TWI_019 Pau City: Each leader unit you control gets +0/+1.
  - TWI_022 Droid Manufactory: When you deploy a leader: Create 2 Battle Droid tokens.
  - TWI_025 Shadow Collective Camp: When you deploy a leader: Draw a card.
  - TWI_028 Petranaki Arena: Each leader unit you control gets +1/+0.

## Phase 21 — Misc / one-off events (autonomous)
- [~] **Batch 21.1 — TWI_033, TWI_034, TWI_040, TWI_046** — 2/4 done, 2998/0. TWI_033 WhenPlayed + friendly-defeated observer → self Sentinel; TWI_046 WhenPlayed/OnAttack give a unit Sentinel. **⚠ TWI_034 DEFERRED** (Grievous: host-aware aspect-penalty-ignore on Lightsaber attach + 4-Lightsaber mass-defeat). **⚠ TWI_040 DEFERRED** (play an upgrade from hand OR any discard ignoring aspect — cross-zone upgrade picker + host choice).
  - TWI_033 Calculating MagnaGuard: When Played/When a friendly unit is defeated: This unit gains Sentinel for this phase. (Units in this arena can't a
  - TWI_034 General Grievous: Ignore the aspect penalty on each Lightsaber upgrade you play on this unit. On Attack: If this unit has 4 or more L
  - TWI_040 A Fine Addition: If an enemy unit was defeated this phase, play an upgrade from your hand or from any player's discard pile, ignorin
  - TWI_046 Captain Typho: When Played/On Attack: Give a unit Sentinel for this phase.
- [~] **Batch 21.2 — TWI_053, TWI_060, TWI_082, TWI_089** — 2/4 done, 2998/0. TWI_060 WhenPlayed damaged-unit→droid; TWI_082 Action attack-Droid-then-another-Droid once/round (chained-attack `trait` constraint, `SWU_TWI082_USED`). **⚠ TWI_053 DEFERRED** (continuous 'prevent 1 of each damage this phase' marker across all damage funnels). **⚠ TWI_089 DEFERRED** (sacrifice friendly units → free-play a unit ≤ combined power → defeat chosen).
  - TWI_053 Finn: When this unit completes an attack: Choose a unique unit. For this phase, if damage would be dealt to that unit, pr
  - TWI_060 Trade Federation Shuttle: When Played: If you control a damaged unit, create a Battle Droid token.
  - TWI_082 MagnaGuard Wing Leader: Action: Attack with a Droid unit. Then, attack with another Droid unit. Use this ability only once each round.
  - TWI_089 Consolidation of Power: Choose any number of friendly units. You may play a unit from your hand if its cost is less than or equal to the co
- [x] **Batch 21.3 — TWI_123, TWI_127, TWI_132, TWI_144** — done, 2998/0. TWI_123 Outflank attack-2-units (reuses SHD_128#0); TWI_127 event→resource; TWI_132 continuous bases-can't-be-healed (OnHealBase guard); TWI_144 WhenPlayed Clone.
  - TWI_123 Outflank: Attack with 2 units (one at a time).
  - TWI_127 Resupply: Put this event into play as a resource.
  - TWI_132 Confederate Tri-Fighter: Bases can't be healed.
  - TWI_144 Batch Brothers: When Played: Create a Clone Trooper token.
- [x] **Batch 21.4 — TWI_176, TWI_199, TWI_203, TWI_223** — done, 2998/0. TWI_176 2-enemies-deal-power-to-each-other (SplitDamage, same-arena picks); TWI_199 return chosen ≤3-cost + same-name enemies (bounce by CardTitle); TWI_203 tokens-enter-ready passive (SWUCreateUnitToken hook) + OnAttack unit-left-play→Clone; TWI_223 discard→look-opp-hand→discard.
  - TWI_176 Caught in the Crossfire: Choose 2 enemy units in the same arena. Each of those units deals damage equal to its power to the other.
  - TWI_199 Clear the Field: Choose a non-leader unit that costs 3 or less. Return it and each enemy non-leader unit with the same name as it to
  - TWI_203 Chancellor Palpatine: Each token unit you create enters play ready. On Attack: If a unit left play this phase, create a Clone Trooper tok
  - TWI_223 Unmasking the Conspiracy: Discard a card from your hand. If you do, look at an opponent's hand and discard a card from it.
- [x] **Batch 21.5 — TWI_249, TWI_250, TWI_252** — done, 2998/0. TWI_249 choose ≤1 Republic + ≤1 Separatist → +2/+2 + Saboteur; TWI_250 friendly Troopers Raid 1 + Jedi Sentinel this phase; TWI_252 opponent shuffles discard to deck bottom.
  - TWI_249 Heroes on Both Sides: Choose up to 1 Republic unit and up to 1 Separatist unit. Give each chosen unit +2/+2 and Saboteur for this phase. 
  - TWI_250 Sword and Shield Maneuver: Give each friendly Trooper unit Raid 1 for this phase. Give each friendly Jedi unit Sentinel for this phase.
  - TWI_252 Aggrieved Parliamentarian: When Played: Choose an opponent. They shuffle their discard pile and put it on the bottom of their deck.

## Phase 22 — Leaders (autonomous)
- [x] **Batch 22.1 — TWI_001, TWI_002, TWI_003, TWI_004** — done, 3003/0. TWI_001 Clone aspect-ignore (both sides) + deployed Clone-WhenDefeated heal-2; TWI_002 front 2+defeated→droid / deployed OnAttack droid; TWI_003 front heal-1 / deployed Sentinel + OnAttack heal-then-deal; TWI_004 front draw+put-top/bottom / deployed Restore 2 + WhenDeployed deck-discard→defeat.
  - TWI_001 Nala Se: Ignore the aspect penalty on Clone units you play. Epic Action: If you || dep: Ignore the aspect penalty on Clone units you play. Each
  - TWI_002 Nute Gunray: Action [Exhaust]: If 2 or more friendly units were defeated this phase || dep: On Attack: Create a Battle Droid token.
  - TWI_003 Obi-Wan Kenobi: Action [Exhaust]: Heal 1 damage from a unit. Epic Action: If you contr || dep: Sentinel (Units in this arena can't attack your non-Sen
  - TWI_004 Yoda: Action [Exhaust]: If a unit left play this phase, draw a card, then pu || dep: Restore 2 When Deployed: You may discard a card from yo
- [x] **Batch 22.2 — TWI_005, TWI_006, TWI_007, TWI_008** — done, 3007/0. TWI_005 already-done (Dooku). TWI_006 front/deployed friendly-defeated→+2/+2; TWI_007 front friendly-attacked→Clone (new `SWU_FRIENDLY_ATTACKED` flag) / deployed WhenDeployed Clone + Trooper +0/+1 passive; TWI_008 Coordinate search-top-3-Republic (front + deployed OnAttack) + Restore 1.
  - TWI_005 Count Dooku: ⟳ Action [Exhaust]: Play a Separatist card from your hand. It gains Expl || dep: Overwhelm (When attacking an enemy unit, deal excess da
  - TWI_006 Wat Tambor: Action [Exhaust]: If a friendly unit was defeated this phase, give a u || dep: On Attack: If a friendly unit was defeated this phase, 
  - TWI_007 Captain Rex: Action [2 resources, Exhaust]: If a friendly unit attacked this phase, || dep: When Deployed: Create a Clone Trooper token. Each other
  - TWI_008 Padmé Amidala: Coordinate - Action [1 resource, Exhaust]: Search the top 3 cards of y || dep: Restore 1 (When this unit attacks, heal 1 damage from y
- [x] **Batch 22.3 — TWI_009, TWI_010, TWI_011, TWI_012** — done, 3013/0. TWI_009 front attack+Overwhelm / deployed grants-Overwhelm-to-others; TWI_010 front deal=cards-drawn (`SWU_DREW_PHASE`) / deployed hand-size Saboteur + +2/+0; TWI_011 Coordinate front attack+1/+0 / deployed +2/+0; TWI_012 front deal-2-to-base + attack +2/+0-vs-unit (`TWI_012_ATK` marker) / deployed Overwhelm + +1/+0-per-5-base-damage.
  - TWI_009 Maul: Action [Exhaust]: Attack with a unit. It gains Overwhelm for this atta || dep: Overwhelm Each other friendly unit gains Overwhelm.
  - TWI_010 Pre Vizsla: Action [1 resource, Exhaust]: Deal damage to a unit equal to the numbe || dep: While you have 3 or more cards in your hand, this unit 
  - TWI_011 Ahsoka Tano: Coordinate - Action [Exhaust]: Attack with a unit. It gets +1/+0 for t || dep: Coordinate - This unit gets +2/+0.
  - TWI_012 Anakin Skywalker: Action [Exhaust, deal 2 damage to your base]: Attack with a unit. If i || dep: Overwhelm (When attacking an enemy unit, deal excess da
- [~] **Batch 22.4 — TWI_013, TWI_014, TWI_015, TWI_016** — 3/4 done, 3018/0. TWI_013 front deal-1-damaged-enemy-then-1-if-5+ / deployed WhenDeployed AoE-2-damaged-enemies; TWI_014 front/deployed event-played→+1/+0 (new `SWU_PLAYED_EVENT` flag in OnPlayEvent; deployed adds SHOOT_FIRST); TWI_015 front/deployed give-Droid-Sentinel(+1/+0). **⚠ TWI_016 DEFERRED** — Jango Fett 'when a friendly unit deals damage to an enemy: may exhaust it' spans BOTH combat + ability damage on both sides (new deals-damage-to-enemy observer).
  - TWI_013 Mace Windu: Action [1 resource, Exhaust]: Deal 1 damage to a damaged enemy unit. T || dep: When Deployed: Deal 2 damage to each damaged enemy unit
  - TWI_014 Asajj Ventress: Action [Exhaust]: Attack with a unit. If you played an event this phas || dep: On Attack: If you played an event this phase, this unit
  - TWI_015 General Grievous: Action [Exhaust]: Give a Droid unit Sentinel for this phase. (Units in || dep: On Attack: You may give a Droid unit +1/+0 and Sentinel
  - TWI_016 Jango Fett: When a friendly unit deals damage to an enemy unit: You may exhaust th || dep: When a friendly unit deals damage to an enemy unit: You
- [~] **Batch 22.5 — TWI_017, TWI_018** — 1/2 done, 3020/0. TWI_018 Quinlan Vos own-play-unit reaction (front: exhaust-leader→deal-1-to-=cost; deployed: deal-1-to-≤cost). **⚠ TWI_017 DEFERRED** — Chancellor Palpatine is a two-Action FLIP leader (both sides are undeployed Actions that toggle via 'flip this leader'), not a deploy leader — needs a new flip-side-toggle mechanism.
  - TWI_017 Chancellor Palpatine: This leader starts the game with this side faceup. Action [Exhaust]: I || dep: Action [Exhaust]: If you played a Villainy card this ph
  - TWI_018 Quinlan Vos: When you play a unit: You may exhaust this leader. If you do, deal 1 d || dep: When you play a unit: You may deal 1 damage to an enemy

## Phase 23 — Multi-defender attack (pair-programmed)
- [x] **Batch 23.1 — TWI_135** — 9 tests pass (full-power-to-each, all-die simultaneity, single-unit-via-multiselect, Sentinel single-attack-only + fill-both-Sentinels, Overwhelm combined-excess, base-not-doubled, single-enemy attack-unit + attack-base). New 2-defender simultaneous-combat subsystem in CombatLogic.php: `_SWUMaulDoubleCombat` (+`_SWUMaulDealCombat`/`_SWUMaulCombatDefeat`). Fidelity = **Pragmatic core** (power/HP, Shields, Amidala/Mandalorian prevention, simultaneity, defeats + batched When Defeated; skips mid-combat On Attack/On Defense pause windows). Wired to the **official rulings (2024-10-31)**: (1) never a base+unit pair; (2) if the defender controls ANY Sentinel, Maul may only pick Sentinels (unless Saboteur); (3) **Overwhelm** ⇒ COMBINED excess to the base; (4) one attack / triggers once / simultaneous. **UX (target selection, by legal-target count — Maul has no Saboteur so `SWUGetValidAttackTargets` is already Sentinel-restricted):** ≤1 legal unit → ordinary single-attack prompt (1 unit+base = normal 2-target choose; lone Sentinel = auto-resolve); ≥2 legal units **with** a legal base → `OPTIONCHOOSE Base&Units` (`TWI135_MODE`), Units → `MZMULTICHOOSE 1|2` (`TWI135_PICK`); ≥2 legal units **without** a legal base (2+ Sentinels) → the `MZMULTICHOOSE 1|2` directly. Verified decision shapes via `TestSchemaStep`.
  - TWI_135 Darth Maul: This unit can attack 2 units instead of 1. (This unit deals its combat damage to both defenders and they both deal their combat damage to th
  - ⚠ new subsystem: attack 2 units instead of 1 — new simultaneous multi-defender combat path

## Phase 24 — Copy a unit (pair-programmed)
- [x] **Batch 24.1 — TWI_116** — 8 tests pass (basic stats+traits, copied When Played fires, defeat reverts to Clone in discard, decline→0/0 dies, Vehicle-only→no copy, copy space unit→space arena, non-unique→no uniqueness defeat, UndoCycle serialization). New "enter play as a copy" subsystem: a persistent **IsClone** schema field (GroundArena/SpaceArena in GameSchema.txt + ZoneClasses.php); play-time interception in `SWUBeginPlayCard` (TWI_116 → MZMAYCHOOSE of non-leader/non-Vehicle units in either arena → `CLONE_COPY_CHOICE` stashes `$gCloneCopyCardID`); `ActivateCard` places the unit AS the copied CardID (arena, stats, traits, abilities, keywords all resolve from it) + copied When Played/entry triggers fire + sets IsClone. Clone trait via `_SWUUnitHasTrait` IsClone branch; non-unique via `SWUEnforceUniqueness` IsClone skip; CardID reverts to TWI_116 on leave-play (`SWUAddToDiscard` choke + `SWUDefeatUnit`/capture/bounce). CR §9.2 confirms "printed attributes" includes abilities. User rulings honored: copied When Played **Triggers**; identity via **CardID swap + IsClone flag**. **Deferred edges** (documented in code): capture→rescue re-choose (ruling 2 — capture reverts identity cleanly, but a rescued Clone currently re-enters as a plain 0/0 rather than re-choosing); return-to-deck revert; bare-CardID `HasTrait('Clone')` reads (object-aware only).

## Phase 25 — Grant ability to enemy units (pair-programmed)
- [x] **Batch 25.1 — TWI_047** — 5 tests pass (Satine self-grant mills 3, ENEMY unit's grant mills its controller's foe, friendly ordinary unit + round-up, remaining-HP not printed, no-Satine→no action). Field-wide granted Action via `SWUGetUnitActionProvider`'s new `_SWUSatineInPlay()` fallback (any unit without its own provider gets `'TWI_047'` while a Satine is in play, either player); handler `$unitAbilities["TWI_047"]` (`'exhaust'` cost kind) mills `ceil(remainingHP/2)` from the acting unit's controller's opponent via `SWUMillTopCard`. Each unit's own controller activates it (object-based provider → works for enemy units on their turn, no `$anyPlayerUnitActions` needed). **Documented edge**: a unit with its OWN activated Action keeps it (single-provider model surfaces only one), shadowing Satine's grant for that unit.

## Phase 26 — Control redistribution (pair-programmed)
- [x] **Batch 26.1 — TWI_204** — 3 tests pass (control swap both directions, fizzle when a player has no ready non-leader unit, revert-at-regroup). OnPlayEvent case gathers each player's ready non-leader units (both required — "if you do") → two sequential `SWUQueueChooseTarget` (caster picks one unit per player) → `TWI_204#0`/`TWI_204#1` continuations do the 2P swap via `SWUTakeControlOfUnit` + `TEMPORARY_STEAL` (auto-reverts to owner at RegroupPhaseStart). **Frame-bug fixed & noteworthy**: `SWUTakeControlOfUnit` returns the new mzID in the NEW CONTROLLER's frame (`GetMzID` runs while `$playerID = $newController`), so when the new controller ≠ the handler's `$playerID` (the opponent-side steal), `AddTurnEffect` must set `$playerID = $opp` first or the marker lands on the wrong unit — the caster-side steal (LOF_189 pattern) doesn't hit this because the frames match.

## Phase 27 — Partial leader-ability loss (pair-programmed)
- [x] **Batch 27.1 — TWI_255** — 5 tests pass (deployed leader loses Sentinel + control, front leader Action suppressed + control, Epic deploy still works). Field-presence passive, no handler: `_SWUBrainInvadersInPlay()` (scans both arenas for TWI_255) gates two points — (1) `LostAbilities()` returns true for any deployed leader UNIT (`IsLeaderUnit`) while a Brain Invaders is in play, which cascades through the ~26 existing LostAbilities gates (deployed On Attack/On Defense/combat-hit/attack-end triggers, keyword suppression via `SWUKeywordSuppressed`, deployed Action affordability, reactive observers, defeat-replacement); (2) `SWULeaderActionAffordable()` returns false so the front (undeployed) leader's activated Action is lost. Epic deploy is unaffected (separate path, "except epic actions"). Cheap leader-check-first short-circuit avoids the arena scan on the hot non-leader `LostAbilities` path. **Documented edges**: "can't gain abilities" and initiative-/defeat-triggered FRONT reactive abilities aren't centrally gated; deployed-leader field-presence passives that buff OTHER units (keyed on `_SWULeaderDeployed`, not on the leader's abilities) aren't suppressed.

