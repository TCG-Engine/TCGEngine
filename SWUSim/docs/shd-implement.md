# SHD — Card Implementation Plan

> ⚠ SHD is normally deferred to the Eternal format; this plan was generated at the user's explicit request (guard lifted). Confirm scope before running.

264 cards total: 160 Unit, 46 Event, 30 Upgrade, 18 Leader, 8 Base, 2 Token. 229 needs-work, 35 auto-wired (vanilla/keyword-only/base no-op/engine token + SHD_114 done).

**Core-mechanic gate: CLEAR** — Bounty, Smuggle, Capture, Experience/Shield tokens, Grit, Ambush all already built. The ONE genuinely new seam SHD needs is a **reactive play/discard trigger subsystem** (Phase in the pair-programmed section).

⚠ **Already partially implemented — verify, do NOT duplicate:** SHD_012, SHD_028, SHD_135, SHD_166, SHD_182. ⚠ **SHD_187** immunity halves likely already wired via `SWUAvoids*` helpers — only the Raid 2 (auto) + any remaining text needs checking.

### Already Done
SHD_019, SHD_020, SHD_021, SHD_022, SHD_023, SHD_024, SHD_025, SHD_026, SHD_029, SHD_043, SHD_055, SHD_060, SHD_061, SHD_062, SHD_063, SHD_070, SHD_098, SHD_100, SHD_110, SHD_114, SHD_121, SHD_136, SHD_146, SHD_152, SHD_162, SHD_200, SHD_210, SHD_218, SHD_237, SHD_238, SHD_240, SHD_257, SHD_259, SHD_T01, SHD_T02

## Phase 1 — Bounty payloads (reuse SWUCollectBounty dispatch) (autonomous)
- [ ] **Batch 1.1 — SHD_027, SHD_031, SHD_033, SHD_058**
  - SHD_027 Hylobon Enforcer: Grit (This unit gets +1/+0 for each damage on it.) Bounty - Draw a card. (When this unit is defeated or captured, y
  - SHD_031 The Client: Shielded Action [Exhaust]: Choose a unit. For this phase, it gains: "Bounty - Heal 5 damage from a base." (When tha
  - SHD_033 Synara San: Grit While this unit is exhausted, she gains, "Bounty - Deal 5 damage to a base." (When this unit is defeated or ca
  - SHD_058 Val: Bounty - Deal 3 damage to a unit. When Defeated: Give 2 Experience tokens to a friendly unit. (The active player ch
- [ ] **Batch 1.2 — SHD_068, SHD_071, SHD_095, SHD_116**
  - SHD_068 Public Enemy: Attached unit gains: "Bounty - Give a Shield token to a unit." (When this unit is defeated or captured, its opponen
  - SHD_071 Top Target: Attached unit gains: "Bounty - Heal 4 damage from a unit or base. If this unit is unique, heal 6 damage instead." (
  - SHD_095 Clone Deserter: Restore 1 (When this unit attacks, heal 1 damage from your base.) Bounty - Draw a card. (When this unit is defeated
  - SHD_116 Outlaw Corona: Bounty - Put the top card of your deck into play as a resource. (When this unit is defeated or captured, your oppon
- [ ] **Batch 1.3 — SHD_123, SHD_125, SHD_134, SHD_161**
  - SHD_123 Bounty Hunter\'s Quarry: Attached unit gains: "Bounty - Search the top 5 cards of your deck, or 10 cards instead if this unit is unique, for
  - SHD_125 Price on Your Head: Attached unit gains: "Bounty - Put the top card of your deck into play as a resource." (When this unit is defeated 
  - SHD_134 Guavian Antagonizer: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) Bounty - Draw a card. (When t
  - SHD_161 Stolen Landspeeder: When Played: If you played this unit from your hand, an opponent takes control of it. Bounty - If you own this unit
- [ ] **Batch 1.4 — SHD_165, SHD_167, SHD_173, SHD_176**
  - SHD_165 Unlicensed Headhunter: Saboteur While this unit is exhausted, it gains: "Bounty - Heal 5 damage from your base." (When this unit is defeat
  - SHD_167 Wanted Insurgents: Bounty - Deal 2 damage to a unit. (When this unit is defeated or captured, your opponent collects its bounty.)
  - SHD_173 Guild Target: Attached unit gains: "Bounty - Deal 2 damage to a base. If this unit is unique, deal 3 damage instead." (When this 
  - SHD_176 Death Mark: Attached unit gains: "Bounty - Draw 2 cards." (When this unit is defeated or captured, its opponent collects its bo
- [ ] **Batch 1.5 — SHD_185, SHD_195, SHD_211, SHD_221**
  - SHD_185 Doctor Evazan: Shielded (When you play this unit, give a Shield token to him.) Bounty - Ready up to 12 resources. (When this unit 
  - SHD_195 Cartel Turncoat: Bounty - Draw a card. (When this unit is defeated or captured, your opponent collects its bounty.)
  - SHD_211 Fugitive Wookiee: Bounty - Exhaust a unit. (When this unit is defeated or captured, your opponent collects its bounty.)
  - SHD_221 Wanted: Attached unit gains: "Bounty - Ready 2 friendly resources." (When this unit is defeated or captured, its opponent c
- [ ] **Batch 1.6 — SHD_222, SHD_261**
  - SHD_222 Enticing Reward: Attached unit gains: "Bounty - Search the top 10 cards of your deck for 2 non-unit cards, reveal them, and draw the
  - SHD_261 Rich Reward: Attached unit gains: "Bounty - Give an Experience token to each of up to 2 units." (When this unit is defeated or c

## Phase 2 — Smuggle alt-cost cards (Smuggle built — implement each card's own effect) (autonomous)
- [ ] **Batch 2.1 — SHD_032, SHD_050, SHD_052, SHD_065**
  - SHD_032 Lom Pyke: On Attack: You may give a Shield token to an enemy unit. If you do, give a Shield token to a friendly unit. Smuggle
  - SHD_050 Chewbacca: Grit When Played: You may defeat a unit with 5 or less remaining HP. Smuggle [9 resources Aggression Heroism]
  - SHD_052 Sugi: While an enemy unit is upgraded, this unit gains Sentinel. Smuggle [6 resources Vigilance] (If this card is a resou
  - SHD_065 Vigilant Pursuit Craft: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.)  Smuggle [7 resources, vigilance]
- [ ] **Batch 2.2 — SHD_075, SHD_086, SHD_089, SHD_097**
  - SHD_075 Covert Strength: Heal 2 damage from a unit and give an Experience token to it. Smuggle [3 resources Vigilance] (If this card is a re
  - SHD_086 Warbird Stowaway: While you have the initiative, this unit gets +2/+0. Smuggle [4 resources Command Villainy] (If this card is a reso
  - SHD_089 Pirate Battle Tank: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.) Smuggle [7 resources Command Vill
  - SHD_097 Freetown Backup: On Attack: Give another friendly unit +2/+2 for this phase. Smuggle [4 resources Command Heroism] (If this card is 
- [ ] **Batch 2.3 — SHD_107, SHD_111, SHD_113, SHD_119**
  - SHD_107 Enterprising Lackeys: When Defeated: You may defeat a friendly resource. If you do, put this unit into play as a resource. Smuggle [6 res
  - SHD_111 Collections Starhopper: Smuggle [3 resources Command] (If this card is a resource, you may play it for its smuggle cost. Replace it with th
  - SHD_113 Privateer Crew: Smuggle [6 resources, command] (If this card is a resource, you may play it for its smuggle cost. Replace it with t
  - SHD_119 Weequay Pirate Gang: Ambush (When you play this unit, it may ready and attack an enemy unit.) Smuggle [5 resources Command] (If this car
- [ ] **Batch 2.4 — SHD_127, SHD_129, SHD_148, SHD_149**
  - SHD_127 Commission: Search the top 10 cards of your deck for a Bounty Hunter, Item, or Transport card, reveal it, and draw it. (Put the
  - SHD_129 Timely Intervention: Play a unit from your hand. Give it Ambush for this phase. (When you play it, it may ready and attack an enemy unit
  - SHD_148 Cassian Andor: Smuggle [5 resources Aggression Heroism] (If this card is a resource, you may play him for his smuggle cost. Replac
  - SHD_149 Nite Owl Skirmisher: Smuggle [5 resources Aggression Heroism] (If this card is a resource, you may play it for its smuggle cost. Replace
- [ ] **Batch 2.5 — SHD_160, SHD_174, SHD_175, SHD_184**
  - SHD_160 Reckless Gunslinger: When Played: Deal 1 damage to each base. Smuggle [3 resources Aggression] (If this card is a resource, you may play
  - SHD_174 Hotshot DL-44 Blaster: Attach to a non-VEHICLE unit.  Smuggle [3 resources, cunning]  When played using Smuggle: Attack with attached unit
  - SHD_175 Armed to the Teeth: Attached unit gains: "On Attack: Give another friendly unit +2/+0 for this phase." Smuggle [4 resources Aggression]
  - SHD_184 Bazine Netal: When Played: Look at an opponent's hand. You may discard 1 of those cards. If you do, that player draws a card. Smu
- [ ] **Batch 2.6 — SHD_197, SHD_201, SHD_203, SHD_204**
  - SHD_197 L3-37: When Played: You may rescue a captured card. If you don't, give a Shield token to this unit. Smuggle [4 resources C
  - SHD_201 Principled Outlaw: On Attack: You may exhaust a ground unit. Smuggle [6 resources Cunning Heroism] (If this card is a resource, you ma
  - SHD_203 Zorii Bliss: On Attack: Draw a card. At the start of the regroup phase, discard a card from your hand. Smuggle [6 resources Cunn
  - SHD_204 Millennium Falcon: If you play this unit from your hand, it gains Ambush. Smuggle [6 resources Cunning Heroism] (If this card is a res
- [ ] **Batch 2.7 — SHD_213, SHD_215, SHD_225, SHD_252**
  - SHD_213 DJ: Smuggle [7 resources Cunning Cunning] When played using Smuggle: Take control of an enemy resource. When this unit 
  - SHD_215 Smuggler\'s Starfighter: When Played: If you control another Underworld unit, give an enemy unit -3/-0 for this phase. Smuggle [4 resources 
  - SHD_225 Jetpack: Attach to a non-Vehicle unit. When Played: Give a Shield token to attached unit. At the start of the regroup phase,
  - SHD_252 Smuggler\'s Aid: Heal 3 damage from your base. Smuggle [3 resources Heroism] (If this card is a resource, you may play it for its sm

## Phase 3 — Direct damage / heal / defeat effects (autonomous)
- [ ] **Batch 3.1 — SHD_030, SHD_038, SHD_041, SHD_044**
  - SHD_030 Death Trooper: When Played: Deal 2 damage to a friendly ground unit and 2 damage to an enemy ground unit.
  - SHD_038 Brutal Traditions: Action: If an enemy unit was defeated this phase, play this upgrade from your discard pile (paying its cost).
  - SHD_041 Kuiil: Restore 1 (When this unit attacks, heal 1 damage from your base.) On Attack: Discard a card from your deck. If it s
  - SHD_044 Razor Crest: Restore 2 (When this unit attacks, heal 2 damage from your base.)  When Played: You may return an upgrade from your
- [ ] **Batch 3.2 — SHD_048, SHD_054, SHD_059, SHD_078**
  - SHD_048 Gentle Giant: Grit (This unit gets +1/+0 for each damage on it.) On Attack: You may heal damage from another unit equal to the da
  - SHD_054 Midnight Repairs: Heal up to 8 total damage from any number of units.
  - SHD_059 Embo: When this unit completes an attack: If the defender was defeated, heal up to 2 damage from a unit.
  - SHD_078 Fell the Dragon: Defeat a non-leader unit with 5 or more power.
- [ ] **Batch 3.3 — SHD_079, SHD_090, SHD_091, SHD_108**
  - SHD_079 Rival\'s Fall: Defeat a unit.
  - SHD_090 Maul: Ambush, Overwhelm On Attack: You may choose another friendly Underworld unit. If you do, all combat damage that wou
  - SHD_091 Jabba\'s Rancor: If you control Jabba the Hutt (as a leader or unit), this unit costs 1 resource less to play. When Played/On Attack
  - SHD_108 Enforced Loyalty: Defeat a friendly unit. If you do, draw 2 cards.
- [ ] **Batch 3.4 — SHD_138, SHD_142, SHD_150, SHD_151**
  - SHD_138 Jango Fett: While attacking a unit with a Bounty, this unit gets +3/+0 and gains Overwhelm. When this unit attacks and defeats 
  - SHD_142 Pre Vizsla: When Played/On Attack: You may pay the cost of an upgrade attached to another non-Vehicle unit. If you do, take con
  - SHD_150 Koska Reeves: On Attack: If this unit is upgraded, you may deal 2 damage to a ground unit.
  - SHD_151 Valiant Assault Ship: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) On Attack: If the defending p
- [ ] **Batch 3.5 — SHD_153, SHD_154, SHD_158, SHD_159**
  - SHD_153 Poe Dameron: On Attack: Discard up to 3 cards from your hand. For each card discarded this way, choose a different option: <bull
  - SHD_154 Wrecker: Overwhelm When Played: You may defeat a friendly resource. If you do, deal 5 damage to a ground unit.
  - SHD_158 Wild Rancor: Overwhelm When Played: Deal 2 damage to each other ground unit.
  - SHD_159 The Chaos of War: Deal damage to each player's base equal to the number of cards in that player's hand.
- [ ] **Batch 3.6 — SHD_164, SHD_166, SHD_169, SHD_171**
  - SHD_164 Rhokai Gunship: When Defeated: Deal 1 damage to a unit or base.
  - SHD_166 Disabling Fang Fighter: When Played: You may defeat an upgrade. ⚠VERIFY-PARTIAL
  - SHD_169 Clan Challengers: Raid 3 (This unit gets +3/+0 while attacking.) While this unit is upgraded, it gains Overwhelm. (When attacking an 
  - SHD_171 Covetous Rivals: Grit (This unit gets +1/+0 for each damage on it.) When Played/On Attack: You may deal 2 damage to a unit with a Bo
- [ ] **Batch 3.7 — SHD_178, SHD_190, SHD_229, SHD_234**
  - SHD_178 Daring Raid: Deal 2 damage to a unit or base.
  - SHD_190 Zuckuss: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) Each friendly unit named 4-LO
  - SHD_229 Ma Klounkee: Return a friendly non-leader Underworld unit to its owner's hand. If you do, deal 3 damage to a unit.
  - SHD_234 Incinerator Trooper: While attacking, this unit deals combat damage before the defender. (If the defender is defeated, it deals no comba
- [ ] **Batch 3.8 — SHD_235, SHD_242, SHD_246, SHD_254**
  - SHD_235 Ruthless Assassin: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) When Played: Deal 2 damage to 
  - SHD_242 Gideon\'s Light Cruiser: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.)  When Played: If you control M
  - SHD_246 Grey Squadron Y-Wing: On Attack: An opponent chooses a unit or base they control. You may deal 2 damage to it.
  - SHD_254 Bounty Guild Initiate: When Played: If you control another Bounty Hunter unit, you may deal 2 damage to a ground unit.
- [ ] **Batch 3.9 — SHD_262**
  - SHD_262 Confiscate: Defeat an upgrade.

## Phase 4 — Experience / Shield token granters (autonomous)
- [ ] **Batch 4.1 — SHD_034, SHD_035, SHD_039, SHD_040**
  - SHD_034 Supercommando Squad: Shielded (When you play this unit, give a Shield token to it.) While this unit is upgraded, it gains Sentinel. (Uni
  - SHD_035 Clan Saxon Gauntlet: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.) When this unit is attacked: You m
  - SHD_039 Calculated Lethality: Defeat a non-leader unit that costs 3 or less. For each upgrade that was on that unit, give an Experience token to 
  - SHD_040 Clan Wren Rescuer: When Played: Give an Experience token to a unit.
- [ ] **Batch 4.2 — SHD_045, SHD_046, SHD_047, SHD_049**
  - SHD_045 Rose Tico: Shielded (When you play this unit, give a Shield token to her.) On Attack: You may defeat a Shield token on a frien
  - SHD_046 Rey: While playing this unit, ignore her Heroism aspect penalty if you control Kylo Ren. On Attack: You may heal 2 damag
  - SHD_047 The Armorer: When Played: Give a Shield token to each of up to 3 Mandalorian units.
  - SHD_049 The Mandalorian: Sentinel  When Played: You may heal all damage from a unit that costs 2 or less and give 2 Shield tokens to it.
- [ ] **Batch 4.3 — SHD_057, SHD_066, SHD_081, SHD_082**
  - SHD_057 Rickety Quadjumper: On Attack: You may reveal the top card of your deck. If it's not a unit, give an Experience token to another unit. 
  - SHD_066 Cargo Juggernaut: Shielded (When you play this unit, give a Shield token to it.) When Played: If you control another Vigilance unit, 
  - SHD_081 General Tagge: When Played: Give an Experience token to each of up to 3 Trooper units.
  - SHD_082 Outland TIE Vanguard: When Played: You may give an Experience token to another unit that costs 3 or less.
- [ ] **Batch 4.4 — SHD_099, SHD_103, SHD_140, SHD_141**
  - SHD_099 Echo: Restore 2 When Played: You may discard a card from your hand. Give 2 Experience tokens to a unit in play with the s
  - SHD_103 General Rieekan: When Played/On Attack: Choose a friendly unit. If it has Sentinel, give an Experience token to it. Otherwise, it ga
  - SHD_140 Trandoshan Hunters: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) When Played: If an enemy unit 
  - SHD_141 Kylo Ren: While playing this unit, ignore his Villainy aspect penalty if you control Rey. On Attack: Give a unit +2/+0 for th
- [ ] **Batch 4.5 — SHD_186, SHD_212, SHD_232, SHD_258**
  - SHD_186 Hunter of the Haxion Brood: While an enemy unit has a Bounty, this unit gains Shielded. (When you play this unit, give a Shield token to it.)
  - SHD_212 Privateer Scyk: While you control another Cunning unit, this unit gains Shielded. (When you play this unit, give a Shield token to 
  - SHD_232 Relentless Pursuit: Choose a friendly unit. It captures an enemy non-leader unit that costs the same as or less than it. If the friendl
  - SHD_258 Mandalorian Warrior: When Played: You may give an Experience token to another Mandalorian unit.

## Phase 5 — Ready / exhaust effects (autonomous)
- [ ] **Batch 5.1 — SHD_028, SHD_080, SHD_087, SHD_118**
  - SHD_028 Doctor Pershing: Action [Exhaust, deal 1 damage to a friendly unit]: Draw a card. ⚠VERIFY-PARTIAL
  - SHD_080 Salacious Crumb: When Played: Heal 1 damage from your base. Action [Exhaust, return this unit to his owner's hand]: Deal 1 damage to
  - SHD_087 Crosshair: Action [2 resources]: This unit gets +1/+0 for this phase. Action [Exhaust]: This unit deals damage equal to his po
  - SHD_118 Kihraxz Heavy Fighter: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) On Attack: You may exhaust ano
- [ ] **Batch 5.2 — SHD_139, SHD_182, SHD_183, SHD_188**
  - SHD_139 Krrsantan: When Played: If an enemy unit has a Bounty, you may ready this unit. On Attack: Choose a ground unit. You may deal 
  - SHD_182 Bravado: If you've defeated an enemy unit this phase, this event costs 2 resources less to play. Ready a unit. ⚠VERIFY-PARTIAL
  - SHD_183 Kintan Intimidator: On Attack: Exhaust the defender.
  - SHD_188 4-LOM: Ambush (When you play this unit, it may ready and attack an enemy unit.) Each friendly unit named Zuckuss gets +1/+
- [ ] **Batch 5.3 — SHD_189, SHD_191, SHD_196, SHD_199**
  - SHD_189 Slaver\'s Freighter: When Played: You may ready another unit with power equal to or less than the number of upgrades on enemy units.
  - SHD_191 Xanadu Blood: Raid 2 When Played/On Attack: You may return another friendly non-leader Underworld unit to its owner's hand. If yo
  - SHD_196 Grogu: Action [exhaust]: Exhaust an enemy unit.
  - SHD_199 Coruscant Dissident: On Attack: You may ready a resource.
- [ ] **Batch 5.4 — SHD_216, SHD_219, SHD_220, SHD_223**
  - SHD_216 Chain Code Collector: Ambush (When you play this unit, it may ready and attack an enemy unit.) On Attack: If the defender has a Bounty, i
  - SHD_219 Enfys Nest: Ambush (When you play this unit, it may ready and attack an enemy unit.) While a friendly unit (including this one)
  - SHD_220 Fennec Shand: Ambush (When you play this unit, it may ready and attack an enemy unit.) On Attack: Deal 1 damage to the defender (
  - SHD_223 Snapshot Reflexes: When Played: You may attack with attached unit. (It can only attack if it's ready.)
- [ ] **Batch 5.5 — SHD_227, SHD_236**
  - SHD_227 Look the Other Way: Exhaust a unit unless its controller pays 2 resources.
  - SHD_236 Snowtrooper Lieutenant: When Played: You may attack with a unit. If it's an Imperial unit, it gets +2/+0 for this attack. (You can only att

## Phase 6 — Capture / rescue effects (autonomous)
- [ ] **Batch 6.1 — SHD_076, SHD_088, SHD_092, SHD_120**
  - SHD_076 Unexpected Escape: Exhaust a unit. You may rescue a captured card guarded by that unit.
  - SHD_088 Ephant Mon: On Attack: Choose an enemy non-leader unit that attacked your base this phase. A friendly unit in the same arena ca
  - SHD_092 Finalizer: Overwhelm When Played: Choose any number of friendly units. Each of those units captures an enemy non-leader unit i
  - SHD_120 Discerning Veteran: When Played: This unit captures an enemy non-leader ground unit. (Put the captured card facedown under this unit un
- [ ] **Batch 6.2 — SHD_131, SHD_170, SHD_180, SHD_187**
  - SHD_131 Take Captive: A friendly unit captures an enemy non-leader unit in the same arena. (Put the captured card facedown under that uni
  - SHD_170 IG-11: If this unit would be captured, defeat him and deal 3 damage to each enemy ground unit instead. On Attack: You may 
  - SHD_180 Detention Block Rescue: Deal 3 damage to a unit. If that unit is guarding any captured cards, deal 6 damage instead.
  - SHD_187 Lurking TIE Phantom: Raid 2 (This unit gets +2/+0 while attacking.) This unit can't be captured, damaged, or defeated by enemy card abil
- [ ] **Batch 6.3 — SHD_192, SHD_243**
  - SHD_192 Dryden Vos: Shielded When Played: Choose a captured card guarded by a unit you control. You may play it for free under your con
  - SHD_243 Altering the Deal: Discard a captured card guarded by a friendly unit.

## Phase 7 — Return-to-hand (bounce) effects (autonomous)
- [ ] **Batch 7.1 — SHD_206, SHD_209, SHD_233, SHD_260**
  - SHD_206 Spare the Target: Return an enemy non-leader unit to its owner's hand. Collect that unit's Bounties.
  - SHD_209 Criminal Muscle: When Played: You may return a non-unique upgrade to its owner's hand.
  - SHD_233 Evacuate: Return each non-leader unit to its owner's hand.
  - SHD_260 Street Gang Recruiter: When Played: You may return an Underworld card from your discard pile to your hand.

## Phase 8 — Field-presence stat passives & conditional keyword grants (autonomous)
- [ ] **Batch 8.1 — SHD_037, SHD_042, SHD_056, SHD_067**
  - SHD_037 Supreme Leader Snoke: Each enemy non-leader unit gets -2/-2.
  - SHD_042 Concord Dawn Interceptors: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.) This unit gets +2/+0 while defend
  - SHD_056 Follower of The Way: While this unit is upgraded, it gets +1/+1.
  - SHD_067 Fenn Rau: When Played: You may play an upgrade from your hand. It costs 2 resources less. When you play an upgrade on this un
- [ ] **Batch 8.2 — SHD_077, SHD_083, SHD_094, SHD_101**
  - SHD_077 Evidence of the Crime: Take control of an upgrade that costs 3 or less and attach it to an eligible unit of your choice.
  - SHD_083 Seasoned Shoretrooper: While you control 6 or more resources, this unit gets +2/+0.
  - SHD_094 Palpatine\'s Return: Play a unit from your discard pile.  It costs 6 resources less. If it's a Force unit,  it costs 8 resources less in
  - SHD_101 Adelphi Patrol Wing: When Played: You may attack with a unit. If you have the initiative, it gets +2/+0 for this attack.
- [ ] **Batch 8.3 — SHD_112, SHD_117, SHD_145, SHD_168**
  - SHD_112 Gamorrean Retainer: While you control another Command unit, this unit gains Sentinel. (Units in this arena can't attack your non-Sentin
  - SHD_117 Reputable Hunter: If an enemy unit has a Bounty, this unit costs 1 resource less to play.
  - SHD_145 Headhunting: Attack with up to 3 units (one at a time). They can't attack bases for these attacks. Each Bounty Hunter that attac
  - SHD_168 Hunting Nexu: While you control another Aggression unit, this unit gains Raid 2. (It gets +2/+0 while attacking.)
- [ ] **Batch 8.4 — SHD_179, SHD_202, SHD_207, SHD_230**
  - SHD_179 Desperate Attack: Attack with a damaged unit. It gets +2/+0 for this attack.
  - SHD_202 Qi\'ra: When Played: Look at an opponent's hand, then name a card. While this unit is in play, each card with that name cos
  - SHD_207 A New Adventure: Return a non-leader unit that costs 6 or less to its owner's hand. Then, its owner may play it for free.
  - SHD_230 Swoop Down: Attack with a space unit. It gains Saboteur and can attack ground units for this attack. If it attacks a ground uni
- [ ] **Batch 8.5 — SHD_231, SHD_244, SHD_247, SHD_249**
  - SHD_231 Surprise Strike: Attack with a unit. It gets +3/+0 for this attack.
  - SHD_244 No Bargain: Each opponent discards a card from their hand. Draw a card.
  - SHD_247 Protector of the Throne: While this unit is upgraded, it gains Sentinel. (Units in this arena can't attack your non-Sentinel units or your b
  - SHD_249 Wookiee Warrior: Grit (This unit gets +1/+0 for each damage on it.) When Played: If you control another Wookiee unit, draw a card.

## Phase 9 — Deck search / draw / discard / resource-ramp (autonomous)
- [ ] **Batch 9.1 — SHD_085, SHD_093, SHD_102, SHD_105**
  - SHD_085 Superlaser Technician: When Defeated: You may put this unit into play as a resource and ready it.
  - SHD_093 Remnant Reserves: Search the top 5 cards of your deck for up to 3 units, reveal them, and draw them. (Put the other cards on the bott
  - SHD_102 The Marauder: Ambush When Played: Choose a card in your discard pile. Put it into play as a resource if it shares a name with a u
  - SHD_105 Spark of Hope: Choose a unit in your discard pile. If it was defeated this phase, put it into play as a resource.
- [ ] **Batch 9.2 — SHD_115, SHD_122, SHD_156, SHD_194**
  - SHD_115 Cobb Vanth: When Defeated: Search the top 10 cards of your deck for a unit that costs 2 or less and discard it. For this phase,
  - SHD_122 Arquitens Assault Cruiser: Ambush When this unit attacks and defeats a non-leader unit: Put the defeated unit into play as a resource under yo
  - SHD_156 Cripple Authority: Draw a card. Each opponent who controls more resources than you discards a card from their hand.
  - SHD_194 Triple Dark Raid: Search the top 7 cards of your deck for a Vehicle and play it. (Put the other cards on the bottom of your deck in a
- [ ] **Batch 9.3 — SHD_198, SHD_214, SHD_228, SHD_245**
  - SHD_198 Omega: Ignore the aspect penalty on the first Clone unit you play each round. When Played: Search the top 5 cards of your 
  - SHD_214 Frontier Trader: When Played: You may return a resource you control to its owner's hand. If you do, you may put the top card of your
  - SHD_228 Bounty Posting: Search your deck for a Bounty upgrade, reveal it, and draw it. (Shuffle your deck.)  You may play that upgrade (pay
  - SHD_245 Greef Karga: When Played: Search the top 5 cards of your deck for an upgrade, reveal it, and draw it. (Put the other cards on th
- [ ] **Batch 9.4 — SHD_253**
  - SHD_253 This Is The Way: Search the top 8 cards of your deck for up to 2 Mandalorian and/or upgrade cards, reveal them, and draw them. (Put 

## Phase 10 — Upgrades granting abilities (autonomous)
- [ ] **Batch 10.1 — SHD_053, SHD_069, SHD_072, SHD_073**
  - SHD_053 Second Chance: Attach to a non-leader unit. Attached unit gains: "When Defeated: For this phase, this unit's owner may play it fro
  - SHD_069 Foundling: Attached unit gains the Mandalorian trait.
  - SHD_072 Imprisoned: Attach to a non-leader unit. Attached unit loses its current abilities and can't gain abilities.
  - SHD_073 Mandalorian Armor: Attach to a non-Vehicle unit. When Played: If attached unit is a Mandalorian, give a Shield token to it.
- [ ] **Batch 10.2 — SHD_074, SHD_104, SHD_124, SHD_126**
  - SHD_074 Vambrace Grappleshot: Attach to a non-Vehicle unit. Attached unit gains: "On Attack: Exhaust the defender."
  - SHD_104 Inspiring Mentor: Attach to a non-Vehicle unit. Attached unit gains, "On Attack/When Defeated: Give an Experience token to another fr
  - SHD_124 Legal Authority: Attach to a friendly unit. When Played: Attached unit captures an enemy non-leader unit with less power than it. (P
  - SHD_126 The Darksaber: Attach to a non-Vehicle unit. While playing this upgrade on a Mandalorian unit, ignore its aspect penalty. Attached
- [ ] **Batch 10.3 — SHD_143, SHD_155, SHD_177, SHD_193**
  - SHD_143 Ruthlessness: Attached unit gains: "When this unit attacks and defeats a unit: Deal 2 damage to the defending player's base."
  - SHD_155 Heroic Resolve: Attached unit gains: "Action [2 resources, defeat a Heroic Resolve on this unit]: Attack with this unit. It gets +4
  - SHD_177 Vambrace Flamethrower: Attach to a non-Vehicle unit. Attached unit gains: "On Attack: You may deal 3 damage divided as you choose among en
  - SHD_193 Frozen in Carbonite: Attach to a non-leader unit. Attached unit can't ready. When Played: Exhaust attached unit.
- [ ] **Batch 10.4 — SHD_224, SHD_251**
  - SHD_224 Boba Fett\'s Armor: Attach to a non-Vehicle unit. If attached unit is Boba Fett and damage would be dealt to him, prevent 2 of that dam
  - SHD_251 The Mandalorian\'s Rifle: Attach to a friendly non-VEHICLE unit.  When Played: If attached unit is The Mandalorian, he captures an exhausted 

## Phase 11 — Misc WhenPlayed / OnAttack / WhenDefeated triggers (autonomous)
- [ ] **Batch 11.1 — SHD_051, SHD_064, SHD_109, SHD_128**
  - SHD_051 Mystic Reflection: Give an enemy unit -2/-0 for this phase. If you control a Force unit, give the enemy unit -2/-2 for this phase inst
  - SHD_064 Survivors\' Gauntlet: When Played/On Attack: You may attach an upgrade on a unit to another eligible unit controlled by the same player.
  - SHD_109 Endless Legions: Reveal any number of resources you control. Play each unit revealed this way for free (one at a time).
  - SHD_128 Outflank: Attack with 2 units (one at a time).
- [ ] **Batch 11.2 — SHD_130, SHD_135, SHD_157, SHD_181**
  - SHD_130 Moment of Glory: Give a unit +4/+4 for this phase.
  - SHD_135 Kylo\'s TIE Silencer: Action: If this unit was discarded from your hand or deck this phase, play it from your discard pile (paying its co ⚠VERIFY-PARTIAL
  - SHD_157 Bo-Katan Kryze: When Defeated: For each player with 15 or more damage on their base, draw a card.
  - SHD_181 Pillage: Choose a player. They discard 2 cards from their hand.

## Phase 12 — Leaders (front passive/action + Epic deploy + deployed side) (autonomous)
- [ ] **Batch 12.1 — SHD_001, SHD_002, SHD_003, SHD_004**
  - SHD_001 Gar Saxon: Each friendly upgraded unit gets +1/+0. Epic Action: If you control 6 or more resources, deploy this leader.
  - SHD_002 Qi\'ra: Action [1 resource, Exhaust]: Deal 2 damage to a friendly unit. Then, give a Shield token to it. Epic Action: If yo
  - SHD_003 Finn: Action [Exhaust]: Defeat a friendly upgrade on a unit. If you do, give a Shield token to that unit. Epic Action: If
  - SHD_004 Rey: Action [1 resource, Exhaust]: Give an Experience token to a unit with 2 or less power. Epic Action: If you control 
- [ ] **Batch 12.2 — SHD_005, SHD_006, SHD_007, SHD_008**
  - SHD_005 Hondo Ohnaka: When you play a card using Smuggle: You may exhaust this leader. If you do, give an Experience token to a unit. Epi
  - SHD_006 Jabba the Hutt: Action [Exhaust]: Choose a unit. For this phase, it gains: "Bounty - The next unit you play this phase costs 1 reso
  - SHD_007 Moff Gideon: Action [exhaust]: Attack with a unit that costs 3 or less. If it's attacking a unit, it gets +1/+0 for this attack.
  - SHD_008 Boba Fett: When you play a unit that has 1 or more keywords: You may exhaust this leader. If you do, give a friendly unit +1/+
- [ ] **Batch 12.3 — SHD_009, SHD_010, SHD_011, SHD_012**
  - SHD_009 Hunter: Action [1 resource, Exhaust]: Reveal a resource you control. If it shares a name with a friendly unique unit, retur
  - SHD_010 Bossk: Action [Exhaust]: Deal 1 damage to a unit with a Bounty. You may give it +1/+0 for this phase. Epic Action: If you 
  - SHD_011 Kylo Ren: Action [Exhaust, discard a card from your hand]: Give a unit +2/+0 for this phase. Epic Action: If you control 4 or
  - SHD_012 Bo-Katan Kryze: Action [Exhaust]: If you attacked with a Mandalorian unit this phase, deal 1 damage to a unit. Epic Action: If you  ⚠VERIFY-PARTIAL
- [ ] **Batch 12.4 — SHD_013, SHD_014, SHD_015, SHD_016**
  - SHD_013 Han Solo: Action [Exhaust]: Play a unit from your hand. It costs 1 resource less. Deal 2 damage to it. Epic Action: If you co
  - SHD_014 Cad Bane: When you play an Underworld card: You may exhaust this leader. If you do, an opponent chooses a unit they control. 
  - SHD_015 Doctor Aphra: When the regroup phase starts: Discard a card from your deck. Epic Action: If you control 5 or more resources, depl
  - SHD_016 Fennec Shand: Action [1 resource, Exhaust]: Play a unit that costs 4 or less from your hand (paying its cost). Give it Ambush for
- [ ] **Batch 12.5 — SHD_017, SHD_018**
  - SHD_017 Lando Calrissian: Action [Exhaust]: Play a card using Smuggle. It costs 2 resources less. Defeat a resource you own and control. Epic
  - SHD_018 The Mandalorian: When you play an upgrade: You may exhaust this leader. If you do, exhaust an enemy unit with 4 or less remaining HP

## Phase 13 — Reactive triggers — NEW subsystem ("when you play/discard a card", "when X attacks/deals damage") (pair-programmed)
- [ ] **Batch 13.1 — SHD_084, SHD_096, SHD_133, SHD_137**
  - SHD_084 Phase-III Dark Trooper: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.)  When combat damage is dealt to t
  - SHD_096 Maz Kanata: When you play another unit: Give an Experience token to this unit.
  - SHD_133 Dengar: When you play an upgrade on a unit: You may deal 1 damage to that unit.
  - SHD_137 Punishing One: When an upgraded enemy unit is defeated: You may ready this unit. Use this ability only once each round.
- [ ] **Batch 13.2 — SHD_147, SHD_163, SHD_172, SHD_217**
  - SHD_147 Ketsu Onyo: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) When this unit deals combat d
  - SHD_163 Migs Mayfeld: When a player discards a card from their hand: You may deal 2 damage to a unit or base. Use this ability only once 
  - SHD_172 Krayt Dragon: Overwhelm When an opponent plays a card: You may deal damage equal to that card's cost to their base or a ground un
  - SHD_217 Tobias Beckett: When you play a non-unit card: You may exhaust a unit that costs the same as or less than the card you played. Use 
- [ ] **Batch 13.3 — SHD_239, SHD_241, SHD_250, SHD_255**
  - SHD_239 Toro Calican: When you play another Bounty Hunter unit: You may deal 1 damage to it. If you do, ready this unit. Use this ability
  - SHD_241 Kragan Gorr: When an enemy unit attacks your base: Give a Shield token to a friendly unit in the same arena as the attacker.
  - SHD_250 Tarfful: Restore 2 When a friendly Wookiee unit is dealt combat damage and isn't defeated: That unit deals that much damage 
  - SHD_255 Lady Proxima: When you play another Underworld card: You may deal 1 damage to a base.

## Phase 14 — Control exchange, forced actions, modal opponent choice & alt win-condition (pair-programmed)
- [ ] **Batch 14.1 — SHD_036, SHD_106, SHD_132, SHD_144**
  - SHD_036 First Light: Grit Each other friendly non-leader unit gains Grit. Smuggle [7 resources Vigilance Villainy, deal 4 damage to a fr
  - SHD_106 Rule with Respect: A friendly unit captures each enemy non-leader unit that attacked your base this phase.
  - SHD_132 Choose Sides: Choose a friendly non-leader unit and an enemy non-leader unit. Exchange control of those units.
  - SHD_144 Give In to Your Anger: Deal 1 damage to an enemy unit. Its controller's next action this phase must be an attack action with that unit, if
- [ ] **Batch 14.2 — SHD_205, SHD_208, SHD_226, SHD_248**
  - SHD_205 Let the Wookiee Win: An opponent chooses one: <bullet>You ready up to 6 resources. You ready a friendly unit. If it's a Wookiee unit, at
  - SHD_208 Final Showdown: Ready each unit you control. At the start of the regroup phase, you lose the game.
  - SHD_226 Unrefusable Offer: Attach to a non-leader unit. Attached unit gains: "Bounty - Play this unit for free (under your control). It enters
  - SHD_248 Tech: Each friendly resource gains Smuggle. The gained Smuggle cost is that card's cost plus 2 resources and its aspect i
- [ ] **Batch 14.3 — SHD_256**
  - SHD_256 Mercenary Gunship: Action [4 resources]: Take control of this unit. Any player may use this ability.

