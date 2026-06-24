# TWI — Card Implementation Plan

259 cards total: 150 Unit, 58 Event, 19 Upgrade, 18 Leader, 12 Base, 2 Token Unit. 220 needs-work, 39 auto-wired (vanilla/keyword-only/base).

Twilight of the Republic (Eternal-only set). Both defining keywords — **Coordinate** (CR §15) and **Exploit** (CR §16) — are already built in the engine, so no foundational mechanic blocks this plan. ⟳ = card already has a *partial* handler from earlier incidental work; `swusim-implement-card` should verify-then-skip rather than rebuild.

### Already Done
TWI_020, TWI_021, TWI_023, TWI_024, TWI_026, TWI_027, TWI_029, TWI_030, TWI_037, TWI_057, TWI_065, TWI_087, TWI_093, TWI_108, TWI_111, TWI_113, TWI_117, TWI_118, TWI_133, TWI_136, TWI_141, TWI_159, TWI_161, TWI_182, TWI_207, TWI_209, TWI_214, TWI_228, TWI_230, TWI_231, TWI_232, TWI_233, TWI_241, TWI_242, TWI_244, TWI_245, TWI_253, TWI_T01, TWI_T02

## Phase 1 — Token generators (Battle Droid / Clone Trooper) (autonomous)
- [ ] **Batch 1.1 — TWI_076, TWI_084, TWI_088, TWI_097**
  - TWI_076 Death by Droids: Defeat a unit that costs 3 or less. Create 2 Battle Droid tokens.
  - TWI_084 Kraken: When Played: Create 2 Battle Droid tokens. On Attack: Give each friendly token unit +1/+1 for this phase.
  - TWI_088 Reprocess: Choose up to 4 units in your discard pile. Put them on the bottom of your deck in a random order and create that ma
  - TWI_097 Captain Rex: When Played: Create 2 Clone Trooper tokens.
- [ ] **Batch 1.2 — TWI_102, TWI_125, TWI_145, TWI_190**
  - TWI_102 Manufactured Soldiers: Choose one: <bullet>Create 2 Clone Trooper tokens. Create 3 Battle Droid tokens.</bullet>
  - TWI_125 The Clone Wars: Pay any number of resources. Create that many Clone Trooper tokens. Each opponent creates that many Battle Droid to
  - TWI_145 Jesse: Raid 1 (This unit gets +1/+0 while attacking.) When Played: An opponent creates 2 Battle Droid tokens.
  - TWI_190 On the Doorstep: Create 3 Battle Droid tokens and ready them.
- [ ] **Batch 1.3 — TWI_222, TWI_227, TWI_234, TWI_235**
  - TWI_222 Political Pressure: Choose an opponent. They may discard a random card from their hand. If they don't, create 2 Battle Droid tokens.
  - TWI_227 Prisoner of War: A friendly unit captures an enemy non-leader, non-Vehicle unit. If the enemy unit costs less than the friendly unit
  - TWI_234 The Invisible Hand: When Played: Create 4 Battle Droid tokens. On Attack: Exhaust any number of friendly Separatist units. Deal 1 damag
  - TWI_235 Battle Droid Legion: Exploit 2 (While playing this card, defeat up to 2 units you control. This card costs 2 resources less for each uni
- [ ] **Batch 1.4 — TWI_237, TWI_247, TWI_251**
  - TWI_237 Droid Deployment: Create 2 Battle Droid tokens.
  - TWI_247 AT-TE Vanguard: Restore 3 (When this unit attacks, heal 3 damage from your base.) When Defeated: Create 2 Clone Trooper tokens.
  - TWI_251 Drop In: Create 2 Clone Trooper tokens.

## Phase 2 — Coordinate abilities (autonomous)
- [ ] **Batch 2.1 — TWI_045, TWI_050, TWI_051, TWI_061**
  - TWI_045 41st Elite Corps: Coordinate - This unit gets +0/+3. (Gain this ability while you control 3 or more units.)
  - TWI_050 Luminara Unduli: ⟳ Coordinate - Grit (Gain this keyword while you control 3 or more units. This unit gets +1/+0 for each damage on her
  - TWI_051 For The Republic: ⟳ If you control 3 or more Republic units, this upgrade costs 2 resources less to play. Attached unit gains: "Coordin
  - TWI_061 Infantry of the 212th: ⟳ Coordinate - Sentinel (Gain this keyword while you control 3 or more units. Units in this arena can't attack your n
- [ ] **Batch 2.2 — TWI_064, TWI_090, TWI_095, TWI_096**
  - TWI_064 Ki-Adi-Mundi: Coordinate - When an opponent plays their second card each phase: You may draw 2 cards.
  - TWI_090 Echo: Coordinate - This unit gets +2/+2. (Gain this ability while you control 3 or more units.)
  - TWI_095 Pelta Supply Frigate: Coordinate - When Played: Create a Clone Trooper token. (Gain this ability while you control 3 or more units, inclu
  - TWI_096 Aayla Secura: Coordinate - On Attack: Prevent all combat damage that would be dealt to this unit for this attack.
- [ ] **Batch 2.3 — TWI_106, TWI_114, TWI_147, TWI_158**
  - TWI_106 Coruscant Guard: ⟳ Coordinate - Ambush (Gain this keyword while you control 3 or more units, including this one. When you play this un
  - TWI_114 Clone Commander Cody: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) Coordinate - Each other friend
  - TWI_147 Anakin Skywalker: Coordinate - On Attack: Draw a card. (Gain this ability while you control 3 or more units.)
  - TWI_158 Clone Heavy Gunner: Coordinate - This unit gets +2/+0. (Gain this ability while you control 3 or more units.)
- [ ] **Batch 2.4 — TWI_162, TWI_164, TWI_165, TWI_192**
  - TWI_162 Reckless Torrent: Coordinate - When Played: You may deal 2 damage to a friendly unit and 2 damage to an enemy unit in the same arena.
  - TWI_164 Hevy: Coordinate - Raid 2 (Gain this keyword while you control 3 or more units. This unit gets +2/+0 while attacking.) Wh
  - TWI_165 Kit Fisto: Saboteur Coordinate - On Attack: You may deal 3 damage to a ground unit. (Gain this ability while you control 3 or 
  - TWI_192 Padmé Amidala: Coordinate - On Attack: Give an enemy unit -3/-0 for this phase. (Gain this ability while you control 3 or more uni
- [ ] **Batch 2.5 — TWI_196, TWI_205, TWI_213, TWI_240**
  - TWI_196 Plo Koon: Ambush (When you play this unit, it may ready and attack an enemy unit.) Coordinate - Raid 3 (Gain this keyword whi
  - TWI_205 Clone Dive Trooper: Coordinate - While this unit is attacking, the defender gets -2/-0. (Gain this ability while you control 3 or more 
  - TWI_213 Sanctioner's Shuttle: Coordinate - When Played: This unit captures an enemy non-leader unit that costs 3 or less. (Gain this ability whil
  - TWI_240 332nd Stalwart: Coordinate - This unit gets +1/+1. (Gain this ability while you control 3 or more units.)
- [ ] **Batch 2.6 — TWI_243**
  - TWI_243 Republic Commando: ⟳ Coordinate - Saboteur (Gain this keyword while you control 3 or more units. When this unit attacks, ignore Sentinel

## Phase 3 — Exploit payloads (autonomous)
- [ ] **Batch 3.1 — TWI_038, TWI_039, TWI_066, TWI_078**
  - TWI_038 Providence Destroyer: Exploit 2 (While playing this card, defeat up to 2 units you control. This card costs 2 resources less for each uni
  - TWI_039 Malevolence: Exploit 4 Restore 2 When Played: Give an enemy unit -4/-0 for this phase. It can't attack for this phase.
  - TWI_066 Multi-Troop Transport: Exploit 2 (While playing this card, defeat up to 2 units you control. This card costs 2 resources less for each uni
  - TWI_078 The Invasion of Christophsis: Exploit 4 Choose an opponent. Defeat each unit that player controls.
- [ ] **Batch 3.2 — TWI_086, TWI_115, TWI_134, TWI_138**
  - TWI_086 Admiral Trench: Exploit 1 When Played: Return up to 3 units that were defeated this phase from your discard pile to your hand.
  - TWI_115 Osi Sobeck: ⟳ Exploit 3 When Played: This unit captures an enemy non-leader ground unit with cost equal to or less than the numbe
  - TWI_134 Asajj Ventress: Exploit 2 (While playing this card, defeat up to 2 units you control. This card costs 2 resources less for each uni
  - TWI_138 Count Dooku: Exploit 2 Overwhelm When Played: For each unit you exploited while playing this card, you may deal damage to an ene
- [ ] **Batch 3.3 — TWI_167, TWI_178, TWI_184, TWI_186**
  - TWI_167 Heavy Persuader Tank: Exploit 2 (While playing this card, defeat up to 2 units you control. This card costs 2 resources less for each uni
  - TWI_178 Planetary Invasion: Exploit 3 Ready up to 3 units. Each of those units gets +1/+0 and gains Overwhelm for this phase.
  - TWI_184 Tactical Droid Commander: Exploit 2 When you play another Separatist unit: You may exhaust a unit that costs the same as or less than the pla
  - TWI_186 San Hill: Exploit 3 (While playing this card, defeat up to 3 units you control. This card costs 2 resources less for each uni
- [ ] **Batch 3.4 — TWI_215, TWI_217**
  - TWI_215 Geonosis Patrol Fighter: Exploit 2 (While playing this card, defeat up to 2 units you control. This card costs 2 resources less for each uni
  - TWI_217 Tri-Droid Suppressor: Exploit 2 (While playing this card, defeat up to 2 units you control. This card costs 2 resources less for each uni

## Phase 4 — Conditional keyword grants (while you control X) (autonomous)
- [ ] **Batch 4.1 — TWI_043, TWI_054, TWI_062, TWI_081**
  - TWI_043 Outspoken Representative: ⟳ While you control another Republic unit, this unit gains Sentinel. (Units in this arena can't attack your non-Senti
  - TWI_054 Duchess's Champion: ⟳ While an opponent controls 3 or more units, this unit gains Sentinel. (Units in this arena can't attack your non-Se
  - TWI_062 Daughter of Dathomir: While this unit is undamaged, it gains Restore 2. (When this unit attacks, heal 2 damage from your base.)
  - TWI_081 Droid Commando: ⟳ While you control another Separatist unit, this unit gains Ambush. (When you play this unit, it may ready and attac
- [ ] **Batch 4.2 — TWI_130, TWI_143, TWI_180, TWI_194**
  - TWI_130 Bo-Katan Kryze: ⟳ While you control another Mandalorian unit, this unit gains Overwhelm and Saboteur. While you control another Troop
  - TWI_143 Jyn Erso: While an enemy unit has been defeated this phase, this unit gets +1/+0 and gains Saboteur.
  - TWI_180 Separatist Commando: While you control another Separatist unit, this unit gains Raid 2. (It gets +2/+0 while attacking.)
  - TWI_194 Ahsoka Tano: ⟳ While you control fewer units than an opponent (including this unit), this unit gains Ambush. Action [2 resources]:
- [ ] **Batch 4.3 — TWI_195, TWI_236**
  - TWI_195 Sabine Wren: While this unit is exhausted, she can't be attacked (unless she gains Sentinel). On Attack: You may discard a card 
  - TWI_236 Grievous's Wheel Bike: While playing this upgrade on General Grievous, it costs 2 resources less to play. Attach to a non-Vehicle unit. At

## Phase 5 — Stat buffs (give +X/+X for this phase) (autonomous)
- [ ] **Batch 5.1 — TWI_042, TWI_044, TWI_058, TWI_074**
  - TWI_042 Barriss Offee: Each friendly unit that was healed this phase gets +1/+0.
  - TWI_044 Kashyyyk Defender: Grit (This unit gets +1/+0 for each damage on it.) When Played: Heal up to 2 damage from another unit and deal that
  - TWI_058 Padawan Starfighter: While you control a Force unit or a Force upgrade, this unit gets +1/+1.
  - TWI_074 Guarding the Way: Give a unit Sentinel for this phase. (Units in its arena can't attack your non-Sentinel units or your base.) If you
- [ ] **Batch 5.2 — TWI_085, TWI_091, TWI_092, TWI_094**
  - TWI_085 Kalani: On Attack: You may choose another unit. If you have the initiative, you may choose up to 2 other units instead. Giv
  - TWI_091 Republic Tactical Officer: When Played: You may attack with a Republic unit. It gets +2/+0 for this attack.
  - TWI_092 Admiral Yularen: Restore 1 Each other friendly Heroism unit gets +0/+1.
  - TWI_094 Shaak Ti: Each friendly token unit gets +1/+0. On Attack: Create a Clone Trooper token.
- [ ] **Batch 5.3 — TWI_104, TWI_105, TWI_110, TWI_122**
  - TWI_104 Obedient Vanguard: Raid 1 (This unit gets +1/+0 while attacking.) When Defeated: You may give a Trooper unit +2/+2 for this phase.
  - TWI_105 Steadfast Senator: Action [2 resources, Exhaust]: Attack with a unit. It gets +2/+0 for this attack.
  - TWI_110 Huyang: When Played: Choose another friendly unit. While this unit is in play, the chosen unit gets +2/+2.
  - TWI_122 Squad Support: Attach to a non-leader unit. Attached unit gains: "This unit gets +1/+1 for each Trooper unit you control."
- [ ] **Batch 5.4 — TWI_124, TWI_126, TWI_139, TWI_142**
  - TWI_124 Tactical Advantage: ⟳ Give a unit +2/+2 for this phase.
  - TWI_126 Encouraging Leadership: Give each friendly unit +1/+1 for this phase.
  - TWI_139 Corner the Prey: Attack with a unit. It gets +1/+0 for this attack for each damage on the defender at the start of this attack.
  - TWI_142 Anakin's Interceptor: While your base has 15 or more damage on it, this unit gets +2/+0.
- [ ] **Batch 5.5 — TWI_153, TWI_163, TWI_172, TWI_179**
  - TWI_153 Bold Resistance: Choose up to 3 units that share the same Trait. Each of those units gets +2/+0 for this phase.
  - TWI_163 Relentless Rocket Droid: While you control another Trooper unit, this unit gets +2/+0.
  - TWI_172 Grim Resolve: Attack with a non-leader unit. It gains Grit for this attack. (It gets +1/+0 for each damage on it.)
  - TWI_179 Soulless One: On Attack: You may exhaust a friendly Droid unit or General Grievous (leader or unit). If you do, this unit gets +2
- [ ] **Batch 5.6 — TWI_224, TWI_254**
  - TWI_224 Breaking In: Attack with a unit. It gets +2/+0 and gains Saboteur for this attack. (When this unit attacks, ignore Sentinel and 
  - TWI_254 Volunteer Soldier: Raid 1 (This unit gets +1/+0 while attacking.) If you control a Trooper unit, this unit costs 1 resource less to pl

## Phase 6 — Stat debuffs (give -X/-X for this phase) (autonomous)
- [ ] **Batch 6.1 — TWI_031, TWI_052, TWI_055, TWI_063**
  - TWI_031 Rune Haako: When Played: If a friendly unit was defeated this phase, you may give a unit -1/-1 for this phase.
  - TWI_052 Hello There: Choose a unit that entered play this phase. It gets -4/-4 for this phase.
  - TWI_055 Equalize: Give a unit -2/-2 for this phase. Then, if you control fewer units than that unit's controller, give another unit -
  - TWI_063 Vulture Interceptor Wing: On Attack: Give an enemy unit -1/-1 for this phase.
- [ ] **Batch 6.2 — TWI_067, TWI_072, TWI_075**
  - TWI_067 The Zillo Beast: When Played: Give each enemy ground unit -5/-0 for this phase. When the regroup phase starts: Heal 5 damage from th
  - TWI_072 I Have the High Ground: Choose a friendly unit. Each enemy unit gets -4/-0 while attacking that unit this phase.
  - TWI_075 Disruptive Burst: Give each enemy unit -1/-1 for this phase.

## Phase 7 — Direct damage (autonomous)
- [ ] **Batch 7.1 — TWI_048, TWI_059, TWI_099, TWI_103**
  - TWI_048 Obi-Wan's Aethersprite: When Played/On Attack: You may deal 1 damage to this unit and 2 damage to another space unit.
  - TWI_059 Royal Guard Attaché: When Played: Deal 2 damage to this unit.
  - TWI_099 Synchronized Strike: Deal damage to an enemy unit equal to the number of units you control in its arena.
  - TWI_103 Pyrrhic Assault: For this phase, each friendly unit gains: "When Defeated: Deal 2 damage to an enemy unit."
- [ ] **Batch 7.2 — TWI_131, TWI_146, TWI_149, TWI_150**
  - TWI_131 OOM-Series Officer: When Defeated: Deal 2 damage to a base.
  - TWI_146 Steela Gerrera: When Played/When Defeated: You may deal 2 damage to your base. If you do, search the top 8 cards of your deck for a
  - TWI_149 Low Altitude Gunship: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) When Played: Choose an enemy u
  - TWI_150 Saw Gerrera: Raid 2 On Attack: If your base has 15 or more damage on it, deal 1 damage to each enemy ground unit.
- [ ] **Batch 7.3 — TWI_151, TWI_154, TWI_155, TWI_156**
  - TWI_151 Resolute: This unit costs 1 resource less to play for every 5 damage on your base. When Played/On Attack: Deal 2 damage to an
  - TWI_154 Mister Bones: On Attack: If you have no cards in your hand, you may deal 3 damage to a ground unit.
  - TWI_155 Twice the Pride: When Played: Deal 2 damage to attached unit.
  - TWI_156 Unlimited Power: Deal 4 damage to a unit, 3 damage to a second unit, 2 damage to a third unit, and 1 damage to a fourth unit. (All d
- [ ] **Batch 7.4 — TWI_157, TWI_160, TWI_170, TWI_171**
  - TWI_157 Disaffected Senator: Action [2 resources, Exhaust]: Deal 2 damage to a base.
  - TWI_160 Vanguard Droid Bomber: When Played: If you control another Separatist unit, deal 2 damage to an enemy base.
  - TWI_170 Daring Raid: Deal 2 damage to a unit or base.
  - TWI_171 Grenade Strike: Deal 2 damage to a unit. You may deal 1 damage to another unit in the same arena.
- [ ] **Batch 7.5 — TWI_173, TWI_174, TWI_177, TWI_181**
  - TWI_173 Blood Sport: Deal 2 damage to each ground unit.
  - TWI_174 Open Fire: Deal 4 damage to a unit.
  - TWI_177 Guerilla Insurgency: Each player defeats a resource they control and discards 2 cards from their hand. Deal 4 damage to each ground unit
  - TWI_181 Elite P-38 Starfighter: When Played/When Defeated: You may deal 1 damage to a unit.
- [ ] **Batch 7.6 — TWI_202, TWI_212, TWI_239, TWI_256**
  - TWI_202 Jar Jar Binks: On Attack: Deal 2 damage to a random unit or base.
  - TWI_212 Freelance Assassin: When Played: You may pay 2 resources. If you do, deal 2 damage to a unit.
  - TWI_239 Execute Order 66: Deal 6 damage to each Jedi unit. For each unit defeated this way, its controller creates a Clone Trooper token.
  - TWI_256 Hold-Out Blaster: Attach to a non-Vehicle unit. When Played: You may have attached unit deal 1 damage to a ground unit.

## Phase 8 — Heal / Restore payloads (autonomous)
- [ ] **Batch 8.1 — TWI_056, TWI_073, TWI_109, TWI_129**
  - TWI_056 Compassionate Senator: Action [2 resources, Exhaust]: Heal 2 damage from a unit or base.
  - TWI_073 Grievous Reassembly: Heal 3 damage from a unit. Create a Battle Droid token.
  - TWI_109 501st Liberator: When Played: If you control another Republic unit, you may heal 3 damage from a base.
  - TWI_129 In Defense of Kamino: For this phase, each friendly Republic unit gains Restore 2 and: "When Defeated: Create a Clone Trooper token."

## Phase 9 — Targeted defeat (autonomous)
- [ ] **Batch 9.1 — TWI_035, TWI_036, TWI_041, TWI_077**
  - TWI_035 Morgan Elsbeth: Restore 1 On Attack: You may defeat another friendly unit. If you do, draw a card.
  - TWI_036 Devastating Gunship: Grit (This unit gets +1/+0 for each damage on it.) When Played: Defeat an enemy unit with 2 or less remaining HP.
  - TWI_041 Lethal Crackdown: Defeat a non-leader unit. Deal damage to your base equal to that unit's power.
  - TWI_077 Vanquish: Defeat a non-leader unit.
- [ ] **Batch 9.2 — TWI_140, TWI_238**
  - TWI_140 Self-Destruct: Defeat a friendly unit. If you do, deal 4 damage to a unit.
  - TWI_238 Merciless Contest: Each player chooses a non-leader unit they control. Defeat those units.

## Phase 10 — Capture / steal (autonomous)
- [ ] **Batch 10.1 — TWI_128, TWI_187**
  - TWI_128 Take Captive: ⟳ A friendly unit captures an enemy non-leader unit in the same arena. (Put the captured card facedown under that uni
  - TWI_187 Cad Bane: When Played: This unit captures up to 3 enemy non-leader units with a total of 8 or less remaining HP. On Attack: T

## Phase 11 — Return-to-hand (bounce) (autonomous)
- [ ] **Batch 11.1 — TWI_191, TWI_198, TWI_220, TWI_226**
  - TWI_191 Wolf Pack Escort: When Played: You may return a friendly non-leader, non-Vehicle unit to its owner's hand.
  - TWI_198 Enfys Nest: Saboteur When Played/On Attack: You may return an enemy non-leader unit with less power than this unit to its owner
  - TWI_220 Shadowed Intentions: ⟳ Attached unit gains: "This unit can't be captured, defeated, or returned to its owner's hand by enemy card abilitie
  - TWI_226 Waylay: ⟳ Return a non-leader unit to its owner's hand.

## Phase 12 — Draw / deck search / scry (autonomous)
- [ ] **Batch 12.1 — TWI_068, TWI_100, TWI_107, TWI_152**
  - TWI_068 Foresight: Attached unit gains: "When the regroup phase starts (before drawing cards): Name a card, then look at the top card 
  - TWI_100 Petition the Senate: If you control 3 or more Official units, draw 3 cards.
  - TWI_107 Patrolling V-Wing: When Played: Draw a card.
  - TWI_152 Mace Windu's Lightsaber: Attach to a non-Vehicle unit. When Played: If attached unit is Mace Windu, draw 2 cards.
- [ ] **Batch 12.2 — TWI_168, TWI_175, TWI_188, TWI_193**
  - TWI_168 Old Access Codes: When Played: If an opponent controls more units than you, draw a card.
  - TWI_175 Strategic Analysis: Draw 3 cards.
  - TWI_188 Wartime Profiteering: Look at cards from the top of your deck equal to the number of units that were defeated this phase. Draw 1 and put 
  - TWI_193 R2-D2: When Played: You may discard a card from your hand. If you do, search the top 3 cards of your deck for a card and d
- [ ] **Batch 12.3 — TWI_201, TWI_208, TWI_257**
  - TWI_201 Aid from the Innocent: Search the top 10 cards of your deck for 2 Heroism non-unit cards and discard them. (Put the other cards on the bot
  - TWI_208 Favorable Delegate: When Played: Draw a card. When Defeated: Discard a card from your hand.
  - TWI_257 Private Manufacturing: Draw 2 cards. If you control no token units, put 2 cards from your hand on the bottom of your deck in any order.

## Phase 13 — Reactive on-play triggers (autonomous)
- [ ] **Batch 13.1 — TWI_080, TWI_101, TWI_121, TWI_210**
  - TWI_080 Poggle the Lesser: When you play another unit: You may exhaust this unit. If you do, create a Battle Droid token.
  - TWI_101 Mas Amedda: When you play another unit: You may exhaust this unit. If you do, search the top 4 cards of your deck for a unit, r
  - TWI_121 General's Blade: Attach to a non-Vehicle unit. If attached unit is a Jedi, it gains: "On Attack: The next unit you play this phase c
  - TWI_210 Lux Bonteri: ⟳ When an opponent plays a card: If that opponent paid less than the card's cost to play it, ready or exhaust a unit.
- [ ] **Batch 13.2 — TWI_216, TWI_246**
  - TWI_216 Fives: Saboteur When you play an event: You may put a Clone unit from your discard pile on the bottom of your deck. If you
  - TWI_246 Tranquility: When Played: You may return a Republic unit from your discard pile to your hand. On Attack: Each of the next 3 Repu

## Phase 14 — When-Defeated payloads (autonomous)
- [ ] **Batch 14.1 — TWI_032, TWI_069, TWI_079, TWI_148**
  - TWI_032 Wartime Trade Official: When Defeated: Create a Battle Droid token.
  - TWI_069 Roger Roger: When Defeated: Attach this upgrade to a friendly Battle Droid token.
  - TWI_079 Confederate Courier: When Defeated: Create a Battle Droid token.
  - TWI_148 Senatorial Corvette: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) When Defeated: Each opponent 
- [ ] **Batch 14.2 — TWI_169, TWI_218, TWI_229**
  - TWI_169 Clone Cohort: Attached unit gains Raid 2 and: "When Defeated: Create a Clone Trooper token."
  - TWI_218 Droid Cohort: Attached unit gains, "When Defeated: Create a Battle Droid token."
  - TWI_229 Battle Droid Escort: When Played/When Defeated: Create a Battle Droid token.

## Phase 15 — When-attacked triggers (autonomous)
- [ ] **Batch 15.1 — TWI_049, TWI_083, TWI_166**
  - TWI_049 Knight of the Republic: When this unit is attacked: Create a Clone Trooper token.
  - TWI_083 General's Guardian: When this unit is attacked: Create a Battle Droid token.
  - TWI_166 Aurra Sing: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) When an enemy ground unit atta

## Phase 16 — Activated [Exhaust] unit abilities (autonomous)
- [ ] **Batch 16.1 — TWI_120, TWI_206**
  - TWI_120 Strategic Acumen: ⟳ Attached unit gains: "Action [Exhaust]: Play a unit from your hand. It costs 1 resource less."
  - TWI_206 Independent Senator: Action [2 resources, Exhaust]: Exhaust a unit with 4 or less power.

## Phase 17 — Ready / exhaust manipulation (autonomous)
- [ ] **Batch 17.1 — TWI_112, TWI_137, TWI_183, TWI_185**
  - TWI_112 Subjugating Starfighter: Ambush (When you play this unit, it may ready and attack an enemy unit.) When Played: If you have the initiative, c
  - TWI_137 Savage Opress: ⟳ When Played: If you control fewer units (including this one) than an opponent, ready this unit.
  - TWI_183 Rush Clovis: Raid 2 On Attack: If the defending player controls no ready resources, create a Battle Droid token.
  - TWI_185 Ziro the Hutt: When Played: For each opponent, you may exhaust a unit that player controls. On Attack: For each opponent, you may 
- [ ] **Batch 17.2 — TWI_200, TWI_211, TWI_221**
  - TWI_200 Creative Thinking: Exhaust a non-unique unit. Create a Clone Trooper token.
  - TWI_211 Sly Moore: When Played: Take contol of an enemy token unit and ready it. At the start of the regroup phase, that token unit's 
  - TWI_221 In Pursuit: Exhaust a friendly unit. If you do, exhaust an enemy unit.

## Phase 18 — Cost modifiers (autonomous)
- [ ] **Batch 18.1 — TWI_098, TWI_189, TWI_197, TWI_225**
  - TWI_098 Republic Defense Carrier: This unit costs 1 resource less to play for each unit controlled by the opponent who controls the most units. Senti
  - TWI_189 Unnatural Life: Play a unit that was defeated this phase from your discard pile. It costs 2 resources less and enters play ready. A
  - TWI_197 Republic Attack Pod: If you control 3 or more units, this unit costs 1 resource less to play.
  - TWI_225 Now There Are Two of Them: If you control exactly one unit, play a non-Vehicle unit from your hand that shares a Trait with the unit you contr

## Phase 19 — Upgrade abilities (autonomous)
- [ ] **Batch 19.1 — TWI_070, TWI_071, TWI_119, TWI_219**
  - TWI_070 Perilous Position: When Played: Exhaust attached unit.
  - TWI_071 Unshakeable Will: Attached unit gains Sentinel. (Units in this arena can't attack your non-Sentinel units or your base.)
  - TWI_119 Nameless Valor: Attach to a token unit. Attached unit gains Overwhelm. (When attacking an enemy unit, deal excess damage to the opp
  - TWI_219 On Top of Things: When Played: Attached unit can't be attacked this phase (unless it has Sentinel).
- [ ] **Batch 19.2 — TWI_248**
  - TWI_248 Ahsoka's Padawan Lightsaber: Attach to a non-Vehicle unit. When Played: If attached unit is Ahsoka Tano, you may attack with a unit.

## Phase 20 — Ability bases (autonomous)
- [ ] **Batch 20.1 — TWI_019, TWI_022, TWI_025, TWI_028**
  - TWI_019 Pau City: Each leader unit you control gets +0/+1.
  - TWI_022 Droid Manufactory: When you deploy a leader: Create 2 Battle Droid tokens.
  - TWI_025 Shadow Collective Camp: When you deploy a leader: Draw a card.
  - TWI_028 Petranaki Arena: Each leader unit you control gets +1/+0.

## Phase 21 — Misc / one-off events (autonomous)
- [ ] **Batch 21.1 — TWI_033, TWI_034, TWI_040, TWI_046**
  - TWI_033 Calculating MagnaGuard: When Played/When a friendly unit is defeated: This unit gains Sentinel for this phase. (Units in this arena can't a
  - TWI_034 General Grievous: Ignore the aspect penalty on each Lightsaber upgrade you play on this unit. On Attack: If this unit has 4 or more L
  - TWI_040 A Fine Addition: If an enemy unit was defeated this phase, play an upgrade from your hand or from any player's discard pile, ignorin
  - TWI_046 Captain Typho: When Played/On Attack: Give a unit Sentinel for this phase.
- [ ] **Batch 21.2 — TWI_053, TWI_060, TWI_082, TWI_089**
  - TWI_053 Finn: When this unit completes an attack: Choose a unique unit. For this phase, if damage would be dealt to that unit, pr
  - TWI_060 Trade Federation Shuttle: When Played: If you control a damaged unit, create a Battle Droid token.
  - TWI_082 MagnaGuard Wing Leader: Action: Attack with a Droid unit. Then, attack with another Droid unit. Use this ability only once each round.
  - TWI_089 Consolidation of Power: Choose any number of friendly units. You may play a unit from your hand if its cost is less than or equal to the co
- [ ] **Batch 21.3 — TWI_123, TWI_127, TWI_132, TWI_144**
  - TWI_123 Outflank: Attack with 2 units (one at a time).
  - TWI_127 Resupply: Put this event into play as a resource.
  - TWI_132 Confederate Tri-Fighter: Bases can't be healed.
  - TWI_144 Batch Brothers: When Played: Create a Clone Trooper token.
- [ ] **Batch 21.4 — TWI_176, TWI_199, TWI_203, TWI_223**
  - TWI_176 Caught in the Crossfire: Choose 2 enemy units in the same arena. Each of those units deals damage equal to its power to the other.
  - TWI_199 Clear the Field: Choose a non-leader unit that costs 3 or less. Return it and each enemy non-leader unit with the same name as it to
  - TWI_203 Chancellor Palpatine: Each token unit you create enters play ready. On Attack: If a unit left play this phase, create a Clone Trooper tok
  - TWI_223 Unmasking the Conspiracy: Discard a card from your hand. If you do, look at an opponent's hand and discard a card from it.
- [ ] **Batch 21.5 — TWI_249, TWI_250, TWI_252**
  - TWI_249 Heroes on Both Sides: Choose up to 1 Republic unit and up to 1 Separatist unit. Give each chosen unit +2/+2 and Saboteur for this phase. 
  - TWI_250 Sword and Shield Maneuver: Give each friendly Trooper unit Raid 1 for this phase. Give each friendly Jedi unit Sentinel for this phase.
  - TWI_252 Aggrieved Parliamentarian: When Played: Choose an opponent. They shuffle their discard pile and put it on the bottom of their deck.

## Phase 22 — Leaders (autonomous)
- [ ] **Batch 22.1 — TWI_001, TWI_002, TWI_003, TWI_004**
  - TWI_001 Nala Se: Ignore the aspect penalty on Clone units you play. Epic Action: If you || dep: Ignore the aspect penalty on Clone units you play. Each
  - TWI_002 Nute Gunray: Action [Exhaust]: If 2 or more friendly units were defeated this phase || dep: On Attack: Create a Battle Droid token.
  - TWI_003 Obi-Wan Kenobi: Action [Exhaust]: Heal 1 damage from a unit. Epic Action: If you contr || dep: Sentinel (Units in this arena can't attack your non-Sen
  - TWI_004 Yoda: Action [Exhaust]: If a unit left play this phase, draw a card, then pu || dep: Restore 2 When Deployed: You may discard a card from yo
- [ ] **Batch 22.2 — TWI_005, TWI_006, TWI_007, TWI_008**
  - TWI_005 Count Dooku: ⟳ Action [Exhaust]: Play a Separatist card from your hand. It gains Expl || dep: Overwhelm (When attacking an enemy unit, deal excess da
  - TWI_006 Wat Tambor: Action [Exhaust]: If a friendly unit was defeated this phase, give a u || dep: On Attack: If a friendly unit was defeated this phase, 
  - TWI_007 Captain Rex: Action [2 resources, Exhaust]: If a friendly unit attacked this phase, || dep: When Deployed: Create a Clone Trooper token. Each other
  - TWI_008 Padmé Amidala: Coordinate - Action [1 resource, Exhaust]: Search the top 3 cards of y || dep: Restore 1 (When this unit attacks, heal 1 damage from y
- [ ] **Batch 22.3 — TWI_009, TWI_010, TWI_011, TWI_012**
  - TWI_009 Maul: Action [Exhaust]: Attack with a unit. It gains Overwhelm for this atta || dep: Overwhelm Each other friendly unit gains Overwhelm.
  - TWI_010 Pre Vizsla: Action [1 resource, Exhaust]: Deal damage to a unit equal to the numbe || dep: While you have 3 or more cards in your hand, this unit 
  - TWI_011 Ahsoka Tano: Coordinate - Action [Exhaust]: Attack with a unit. It gets +1/+0 for t || dep: Coordinate - This unit gets +2/+0.
  - TWI_012 Anakin Skywalker: Action [Exhaust, deal 2 damage to your base]: Attack with a unit. If i || dep: Overwhelm (When attacking an enemy unit, deal excess da
- [ ] **Batch 22.4 — TWI_013, TWI_014, TWI_015, TWI_016**
  - TWI_013 Mace Windu: Action [1 resource, Exhaust]: Deal 1 damage to a damaged enemy unit. T || dep: When Deployed: Deal 2 damage to each damaged enemy unit
  - TWI_014 Asajj Ventress: Action [Exhaust]: Attack with a unit. If you played an event this phas || dep: On Attack: If you played an event this phase, this unit
  - TWI_015 General Grievous: Action [Exhaust]: Give a Droid unit Sentinel for this phase. (Units in || dep: On Attack: You may give a Droid unit +1/+0 and Sentinel
  - TWI_016 Jango Fett: When a friendly unit deals damage to an enemy unit: You may exhaust th || dep: When a friendly unit deals damage to an enemy unit: You
- [ ] **Batch 22.5 — TWI_017, TWI_018**
  - TWI_017 Chancellor Palpatine: This leader starts the game with this side faceup. Action [Exhaust]: I || dep: Action [Exhaust]: If you played a Villainy card this ph
  - TWI_018 Quinlan Vos: When you play a unit: You may exhaust this leader. If you do, deal 1 d || dep: When you play a unit: You may deal 1 damage to an enemy

## Phase 23 — Multi-defender attack (pair-programmed)
- [ ] **Batch 23.1 — TWI_135**
  - TWI_135 Darth Maul: This unit can attack 2 units instead of 1. (This unit deals its combat damage to both defenders and they both deal their combat damage to th
  - ⚠ new subsystem: attack 2 units instead of 1 — new simultaneous multi-defender combat path

## Phase 24 — Copy a unit (pair-programmed)
- [ ] **Batch 24.1 — TWI_116**
  - TWI_116 Clone: You may have this unit enter play as a copy of a non-leader, non-Vehicle unit in play, except it gains the Clone trait and is not unique. (O
  - ⚠ new subsystem: enter play as a copy of another unit (printed attrs + Clone trait, non-unique) — new copy subsystem

## Phase 25 — Grant ability to enemy units (pair-programmed)
- [ ] **Batch 25.1 — TWI_047**
  - TWI_047 Satine Kryze: Each unit (including enemy units) gains: "Action [Exhaust]: Discard cards from an opponent's deck equal to half this unit's remaining HP, ro
  - ⚠ new subsystem: grants an Action ability to EVERY unit incl. enemy — new enemy-unit activated-ability grant path/UI

## Phase 26 — Control redistribution (pair-programmed)
- [ ] **Batch 26.1 — TWI_204**
  - TWI_204 Impropriety Among Thieves: Choose a ready non-leader unit controlled by each player. If you do, each player takes control of the chosen unit controlled by the player t
  - ⚠ new subsystem: 2P control-swap of one unit each until regroup + opponent choice ('player to their right')

## Phase 27 — Partial leader-ability loss (pair-programmed)
- [ ] **Batch 27.1 — TWI_255**
  - TWI_255 Brain Invaders: Each leader loses all abilities except for epic actions and can't gain abilities.
  - ⚠ new subsystem: leaders lose all abilities EXCEPT epic actions & can't gain — override with carve-out at leader dispatch

