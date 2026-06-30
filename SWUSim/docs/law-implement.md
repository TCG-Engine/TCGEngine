´# LAW — Card Implementation Plan

267 cards total (264 + 3 tokens): 182 Unit, 38 Event, 18 Leader, 14 Upgrade, 12 Base, 2 Token Upgrade, 1 Credit Token. **200 needs-work, 67 auto-wired/done** (7 vanilla + 41 keyword-only + 3 tokens + 16 already-implemented: the 8 Credits cards/LAW_231 + the 8 common bases). No new core mechanics — Credits (CR §3.13) already built; all keywords implemented. All needs-work tagged **autonomous** as a starting estimate; `swusim-implement-set-plan` escalates on emergent forks (a Hard card / new shared subsystem / ambiguous ruling). Large set — run phase-by-phase. ⚠ LAW_149's session-52 immunity half is already wired (`SWUAvoids*`) — implement only its remaining ability.

### Already Done
LAW_020, LAW_021, LAW_022, LAW_024, LAW_025, LAW_027, LAW_028, LAW_030, LAW_038, LAW_042, LAW_049, LAW_050, LAW_060, LAW_070, LAW_081, LAW_082, LAW_090, LAW_098, LAW_100, LAW_106, LAW_114, LAW_117, LAW_118, LAW_120, LAW_121, LAW_122, LAW_123, LAW_135, LAW_143, LAW_146, LAW_153, LAW_154, LAW_160, LAW_162, LAW_164, LAW_172, LAW_175, LAW_177, LAW_180, LAW_188, LAW_190, LAW_196, LAW_199, LAW_209, LAW_211, LAW_218, LAW_220, LAW_222, LAW_230, LAW_231, LAW_234, LAW_239, LAW_244, LAW_247, LAW_248, LAW_250, LAW_251, LAW_253, LAW_254, LAW_255, LAW_259, LAW_260, LAW_262, LAW_263, LAW_T01, LAW_T02, LAW_T03, LAW_041, LAW_043, LAW_044, LAW_085, LAW_096, LAW_102, LAW_103, LAW_130, LAW_131, LAW_132, LAW_133, LAW_165, LAW_166, LAW_167, LAW_168, LAW_169, LAW_170, LAW_171, LAW_179, LAW_202, LAW_203, LAW_204, LAW_205, LAW_206, LAW_207, LAW_208, LAW_217, LAW_226, LAW_242, LAW_243, LAW_245, LAW_246, LAW_264, LAW_035, LAW_039, LAW_040, LAW_045, LAW_052, LAW_055, LAW_061, LAW_063, LAW_065, LAW_067, LAW_069, LAW_075, LAW_078, LAW_080, LAW_083, LAW_089, LAW_092, LAW_093, LAW_099, LAW_109, LAW_113, LAW_124, LAW_134, LAW_136, LAW_137, LAW_138, LAW_140, LAW_144, LAW_145, LAW_147, LAW_148, LAW_151, LAW_157, LAW_161, LAW_183, LAW_193, LAW_198, LAW_213, LAW_227, LAW_229, LAW_232, LAW_233, LAW_240, LAW_241, LAW_249, LAW_257, LAW_261, LAW_031, LAW_032, LAW_037, LAW_048, LAW_051, LAW_057, LAW_062, LAW_064, LAW_068, LAW_076, LAW_079, LAW_087, LAW_095, LAW_101, LAW_104, LAW_107, LAW_115, LAW_152, LAW_155, LAW_158, LAW_163, LAW_173, LAW_174, LAW_178, LAW_181, LAW_182, LAW_184, LAW_191, LAW_192, LAW_194, LAW_197, LAW_214, LAW_216, LAW_221, LAW_224, LAW_228, LAW_236, LAW_237, LAW_238, LAW_258, LAW_058, LAW_059, LAW_091, LAW_097, LAW_116, LAW_142, LAW_159, LAW_185, LAW_189, LAW_195, LAW_033, LAW_034, LAW_036, LAW_046, LAW_047, LAW_053, LAW_054, LAW_056, LAW_071, LAW_073, LAW_084, LAW_088, LAW_094, LAW_105, LAW_108, LAW_110, LAW_112, LAW_119, LAW_139, LAW_149, LAW_176, LAW_210, LAW_212, LAW_219, LAW_223, LAW_235, LAW_252, LAW_077, LAW_111, LAW_125, LAW_126, LAW_127, LAW_128, LAW_129, LAW_141, LAW_150, LAW_186, LAW_187, LAW_201, LAW_225, LAW_019, LAW_023, LAW_026, LAW_029, LAW_001, LAW_002, LAW_003, LAW_004, LAW_005, LAW_006, LAW_007, LAW_008, LAW_009, LAW_010, LAW_011, LAW_012, LAW_013, LAW_016, LAW_017, LAW_018, LAW_014, LAW_015, LAW_066, LAW_072, LAW_074, LAW_086, LAW_156, LAW_200, LAW_215, LAW_256

## Phase 1 — Events — damage / heal / draw / discard / exhaust / defeat / search (autonomous)
- [x] **Batch 1.1 — LAW_041, LAW_043, LAW_044** (+4 tests, 1706 passing). ⚠ **LAW_066 DEFERRED** — "play one of an opponent's resource cards for free" needs a new foreign-owned, any-card-type free-play path (event→owner's-discard + upgrade-host + ready-vs-exhausted refill ruling); only the unit case mirrors the opponent's-discard `Owner:opp/Controller:caster` seam.
  - LAW_041 Nothing Left to Fear: Choose a friendly unit and give it +2/+2 for this phase. Then, you may defeat a non-l
  - LAW_043 Shadow Cloaking: Ready a unit and give a Shield token to it.
  - LAW_044 Single Reactor Ignition: Defeat all units. For each enemy unit defeated this way, deal 1 damage to its control
  - LAW_066 Tear This Ship Apart: Look at all of an opponent\'s resources. You may play 1 of those cards for free. If y
- [x] **Batch 1.2 — LAW_085, LAW_096, LAW_102, LAW_103** (+5 tests, 1711 passing; take-control+deal, each-player-bounce+mass-defeat, deal-up-to-N+heal, defeat+resource)
  - LAW_085 You Hold This: Choose a friendly non-leader unit. An opponent takes control of it. If they do, deal
  - LAW_096 Rhydonium Detonation: Each player may return a non-leader unit to its owner\'s hand. Then, defeat all non-l
  - LAW_102 Choke on Aspirations: Deal up to 5 damage to a friendly non-Vehicle unit. If it survives, heal damage from
  - LAW_103 Display Piece: Defeat an enemy non-leader unit. Its controller resources it from its owner\'s discar
- [x] **Batch 1.3 — LAW_130, LAW_131, LAW_132, LAW_133** (+5 tests, 1716 passing; new NO_COMBAT_DAMAGE combat marker, -2/-2 debuff, lose-abilities+conditional-defeat, defeat+heal)
  - LAW_130 Betrayed Trust: Choose an enemy unit. For this phase, that unit can\'t deal combat damage.
  - LAW_131 Incapacitate: Give a unit -2/-2 for this phase.
  - LAW_132 The Tree Remembers: An enemy unit loses all abilities for this phase. If it costs 3 or less, defeat it.
  - LAW_133 Lost and Forgotten: Defeat a non-leader unit. If you do, heal 3 damage from your base.
- [x] **Batch 1.4 — LAW_165, LAW_166, LAW_167, LAW_168** (+4 tests, 1720 passing; exhaust+2Exp, top-8 aspect search, +N/+N per distinct aspect, Exp+deal-power)
  - LAW_165 Combat Exercise: Exhaust a friendly unit. If you do, give 2 Experience tokens to it.
  - LAW_166 Putting a Team Together: Search the top 8 cards of your deck for a Vigilance, Aggression, or Cunning unit, rev
  - LAW_167 Common Cause: Give a unit +1/+1 for this phase for each different aspect among units you control.
  - LAW_168 Haymaker: Give an Experience token to a friendly unit. That unit deals damage equal to its powe
- [x] **Batch 1.5 — LAW_169, LAW_170, LAW_171, LAW_179** (+4 tests, 1724 passing; granted On-Attack credit marker, control-swap+credits, resource-this-event+topdeck, discard-this-phase cost-reduction + AOE). LAW_179 discount via new SWU_DISCARDED_HAND counter (DoDiscardCard funnel), verified by inspection.
  - LAW_169 Payroll Heist: For this phase, each friendly unit gains: "On Attack: Create a Credit token."
  - LAW_170 Double-Cross: Choose a friendly non-leader unit and an enemy non-leader unit. Exchange control of t
  - LAW_171 Stockpile: Resource this event and the top card of your deck.
  - LAW_179 Fear and Dead Men: This card costs 1 resource less to play for each card discarded from your hand this p
- [x] **Batch 1.6 — LAW_202, LAW_203, LAW_204, LAW_205** (+5 tests, 1729 passing; attack+Saboteur+cond-buff, mill2+return-Aggression, each-player-discard, attack+Overwhelm+self-defeat-on-base via LAW_205 combat marker)
  - LAW_202 Commence the Festivities: Attack with a unit. It gains Saboteur for this attack. If you control fewer resources
  - LAW_203 Daring Delve: Discard 2 cards from your deck. You may return a Aggression card discarded this way t
  - LAW_204 Every Day, More Lies: Each player discards a card from their hand.
  - LAW_205 Flash the Vents: Attack with a unit. It gets +2/+0 and gains Overwhelm for this attack. After completi
- [x] **Batch 1.7 — LAW_206, LAW_207, LAW_208, LAW_217** (+6 tests, 1735 passing; deal1+when-discarded rider [new gPlayingEventCardID guard so a played event doesn't self-trigger], deal3-or-5-by-aspect-count, deal2+2-same-arena, exhaust+discard-shared-aspect)
  - LAW_206 That's a Rock: Deal 1 damage to a unit.
When this event is discarded from your hand or deck: You may
  - LAW_207 Attack From All Sides: Deal 3 damage to a unit. If there are 4 or more different aspects among friendly unit
  - LAW_208 Collateral Damage: Deal 2 damage to a unit. Then, deal 2 damage to a base or another unit in the same ar
  - LAW_217 Hold For Questioning: Exhaust an enemy unit. If you do, look at its controller\'s hand and discard a card f
- [x] **Batch 1.8 — LAW_226, LAW_242, LAW_243, LAW_245** (+6 tests, 1741 passing; exhaust-per-aspect, look-at-top play/discard/leave, phase name-block [new SWU_NAMEBLOCK_PHASE], play-Item-from-discard-3 + delayed regroup self-defeat [SWU_LAW245_DEFEAT])
  - LAW_226 Secret Battle of Pretend: Exhaust a friendly unit. If you do, for each different aspect it has, exhaust an enem
  - LAW_242 Improvise: Look at the top card of your deck. You may play it. It costs 1 resource less. If you
  - LAW_243 Transmission Jamming: Name a card. Cards with that name can\'t be played this phase.
  - LAW_245 Salvaged Materials: Play an Item upgrade from your discard pile. It costs 3 resources less. At the start
- [x] **Batch 1.9 — LAW_246, LAW_264** (+2 tests, 1743 passing; bounce cost≤3, play-from-hand ignoring aspect penalties). ⚠ **LAW_256 DEFERRED** — "Use any number of 'When Played' abilities on friendly Spectre units" needs multi-unit When-Played re-resolution orchestration (chained decisions across several units); revisit after Phase 2 builds the Spectre When-Played handlers.
  - LAW_246 The Axe Forgets: Return a non-leader unit that costs 3 or less to its owner\'s hand.
  - LAW_256 Fire Across the Galaxy: Use any number of "When Played" abilities on friendly Spectre units.
  - LAW_264 From a Certain Point of View: Play a card from your hand, ignoring its aspect penalties.

## Phase 2 — Unit When Played (autonomous)
- [x] **Batch 2.1 — LAW_035, LAW_039, LAW_040, LAW_045** (+4 tests, 1747; conditional heal2/4, token+deal-power, defeat-credit+dual-Exp, conditional deal3/5)
  - LAW_035 Ezra Bridger: Raid 1
When Played: You may heal 2 damage from a unit. If you control a Aggression or
  - LAW_039 Latts Razzi: When Played: Give a Shield token or an Experience token to this unit. Then, she deals
  - LAW_040 Taramyn Barcona: When Played: You may defeat a Credit token. If you do, give an Experience token to th
  - LAW_045 Zeb Orellios: Sentinel
When Played: You may deal 3 damage to a ground unit. If you control a Comman
- [x] **Batch 2.2 — LAW_052, LAW_055, LAW_061, LAW_063** (+4 tests, 1751; draw+self-shield via _SWUOnPlayerDrew, cond 1/2 Exp, ready-another-BountyHunter, search-top10-play-Droids-≤5)
  - LAW_052 The Mandalorian: When Played: Draw a card.
When you draw 1 or more cards during the action phase: Give
  - LAW_055 Chopper: Raid 1
When Played: Give an Experience token to this unit. If you control a Cunning o
  - LAW_061 Asajj Ventress: When Played: You may ready another Bounty Hunter unit.
  - LAW_063 L3-37: Hidden
When Played: Search the top 10 cards of your deck for any number of Droid unit
- [x] **Batch 2.3 — LAW_065, LAW_067, LAW_069, LAW_075** (+4 tests, 1755; attack-with-BountyHunter-noBases, either-Exp-or-exhaust, Exp+Shield-up-to-2, exhaust+cheap-discard)
  - LAW_065 4-LOM: When Played: You may attack with a friendly Bounty Hunter unit, even if it\'s exhaust
  - LAW_067 Jyn Erso: When Played: Either give an Experience token to a unit or exhaust a unit.
  - LAW_069 The Ghost: When Played: You may give an Experience token and a Shield token to a unit. If you co
  - LAW_075 Interrogation Droid: When Played: Exhaust an enemy unit. If you do and that unit costs 3 or less, its cont
- [x] **Batch 2.4 — LAW_078, LAW_080, LAW_083, LAW_089** (+5 tests, 1760; defeat-(non)unique-upgrade, opponent-chooses-credit/deal5, fewer-hand-draw+fewer-res-ramp, cond bounce ≤2/≤4. Lesson: CleanupRemovedCards between a draw and a deck-source MZMove)
  - LAW_078 Sabine Wren: Ambush
When Played: You may defeat a non-<uq> upgrade. If you control a Vigilance or
  - LAW_080 Luke Skywalker: When Played: An opponent chooses one:
They create a Credit token. Ready this unit.
Yo
  - LAW_083 Broken Horn: When Played: If you have fewer cards in hand than an opponent, draw a card. If you co
  - LAW_089 Kanan Jarrus: Restore 1
When Played: You may return a non-leader unit that costs 2 or less to its o
- [x] **Batch 2.5 — LAW_092, LAW_093, LAW_099, LAW_109** (+4 tests, 1764; give-to-opp+credits, bounce+free-replay-Shielded [LOF_185], each-player-defeat-own, heal-if-friendly-defeated. Lesson: ships→space arena)
  - LAW_092 Two-Faced Troig: Sentinel
When Played: You may have an opponent take control of this unit. If you do,
  - LAW_093 Rio Durant: When Played: You may return a non-leader unit that costs 3 or less to its owner\'s ha
  - LAW_099 Governor's Shuttle: When Played: Each player chooses a unit they control. Defeat those units.
  - LAW_109 Tantive IV: Restore 2
When Played: If a friendly unit was defeated this phase, heal 4 damage from
- [x] **Batch 2.6 — LAW_113, LAW_124, LAW_134, LAW_136** (+4 tests, 1768; pay1-shield, defeat-≤4HP, Underworld-credit, search-Underworld)
  - LAW_113 Shield Drive Outfitter: When Played: You may pay 1 resource. If you do, give a Shield token to a unit.
  - LAW_124 Industrious Team: When Played: You may defeat a non-leader unit with 4 or less remaining HP.
  - LAW_134 Bib Fortuna: When Played: If you control another Underworld unit, create a Credit token.
  - LAW_136 Syndicate Spice Runner: When Played: Search the top 3 cards of your deck for an Underworld unit, reveal it, a
- [x] **Batch 2.7 — LAW_137, LAW_138, LAW_140, LAW_144** (+4 tests, 1772; deal-if-Villainy, search-BountyHunter, return-resources-for-credits, play-Heroism-unit+Exp)
  - LAW_137 Ruthless Duo: When Played: If you control another Villainy unit, you may deal 2 damage to a ground
  - LAW_138 Undercity Hunting Team: When Played: Search the top 5 cards of your deck for a Bounty Hunter unit, reveal it,
  - LAW_140 Intimidator: When Played: Return any number of friendly resources to their owners\' hands. For eac
  - LAW_144 Phantom: When Played: You may play a Heroism unit from your hand and give an Experience token
- [x] **Batch 2.8 — LAW_145, LAW_147, LAW_148, LAW_151** (+4 tests, 1776; search-shared-aspect, Exp-per-aspect-self, pay1-+1/+1, buff-another)
  - LAW_145 R2-D2: When Played: Search the top 5 cards of your deck for a unit that shares an aspect wit
  - LAW_147 Jaunty Light Freighter: When Played: Give an Experience token to this unit for each different aspect among un
  - LAW_148 Smuggler's YT-2400: Ambush
When Played: You may pay 1 resource. If you do, this unit gets +1/+1 for this
  - LAW_151 Profiteering Hunter: When Played: Another friendly unit gets +1/+1 for this phase.
- [x] **Batch 2.9 — LAW_157, LAW_161, LAW_183, LAW_193** (+4 tests, 1780; attack-with-unit-BH-buff, credit-if-friendly-defeated, deal1-to-2-space, pay1-opp-discard)
  - LAW_157 Target Tagger: When Played: You may attack with a unit. If it\'s a Bounty Hunter, it gets +2/+0 for
  - LAW_161 Partisan U-Wing: When Played: If a friendly unit was defeated this phase, create a Credit token.
  - LAW_183 B-Wing Skirmisher: When Played: Deal 1 damage to each of up to 2 space units.
  - LAW_193 Mid Rim Sharpshooter: Saboteur
When Played: You may pay 1 resource. If you do, an opponent discards a card
- [x] **Batch 2.10 — LAW_198, LAW_213, LAW_227, LAW_229** (+5 tests, 1785; pay1-deal2, deal-exhausted, pay1-shield, search-Gambit + first-Gambit/round -1 discount [JTL_260 mirror])
  - LAW_198 Dogged Pursuers: When Played: You may pay 1 resource. If you do, deal 2 damage to a ground unit.
  - LAW_213 Cutthroat Podracer: When Played: You may deal 2 damage to an exhausted ground unit.
  - LAW_227 Rookie Rocket-jumper: When Played: You may pay 1 resource. If you do, give a Shield token to this unit.
  - LAW_229 The Master Codebreaker: The first Gambit card you play each round costs 1 resource less.
When Played: Search
- [x] **Batch 2.11 — LAW_232, LAW_233, LAW_240, LAW_241** (+4 tests, 1789; create-credit, give-to-opp+enemy-Raid/Saboteur-passive, bounce-another-friendly, bounce-any)
  - LAW_232 Champion's KT9 Podracer: When Played: Create a Credit token.
  - LAW_233 Galen Erso: When Played: You may have an opponent take control of this unit.
Enemy units gain Rai
  - LAW_240 Milodon Rider: Ambush
When Played: You may return another friendly non-leader unit to its owner\'s h
  - LAW_241 The Blade Wing: When Played: You may return a non-leader unit to its owner\'s hand.
- [x] **Batch 2.12 — LAW_249, LAW_257, LAW_261** (+3 tests, 1792; Exp-to-Underworld, pay1-Exp-another, return-Underworld-from-discard)
  - LAW_249 Black Sun Cabalist: When Played: Give an Experience token to another friendly Underworld unit.
  - LAW_257 Hidden Hand Supplier: When Played: You may pay 1 resource. If you do, give an Experience token to another u
  - LAW_261 Street Gang Recruiter: When Played: You may return an Underworld card from your discard pile to your hand.

## Phase 3 — Unit On Attack / On Attack End (autonomous)
- [x] **Batch 3.1 — LAW_031, LAW_032, LAW_037, LAW_048** (+4 tests, 1796; OnAttack buff/debuff, defeat-credits-for-Exp [snapshot-then-cleanup], self-Exp, both-players-draw)
  - LAW_031 Bossk: On Attack: Give a unit +1/+1 for this phase. You may give a unit -1/-1 for this phase
  - LAW_032 Cad Bane: Shielded
Overwhelm
On Attack: Defeat any number of friendly Credit tokens. Give an Ex
  - LAW_037 Han Solo: Shielded
On Attack: Give an Experience token to this unit.
  - LAW_048 Chio Fain: On Attack: You may choose 2 players. If you do, they each draw a card.
- [x] **Batch 3.2 — LAW_051, LAW_057, LAW_062, LAW_064** (+5 tests, 1801; draw+deal-N-drawn, OnAttack-deal+WhenDefeated-base, may-+4/+0-self-defeat, deal-power-if-BountyHunter)
  - LAW_051 Beilert Valance: On Attack: Draw a card. You may deal damage to a ground unit equal to the number of c
  - LAW_057 Benthic "Two Tubes": On Attack: Deal 1 damage to an enemy ground unit.
When Defeated: Deal 1 damage to a b
  - LAW_062 Defiant Hammerhead: On Attack: If this unit is attacking a unit, you may give this unit +4/+0 for this at
  - LAW_064 Zuckuss: Saboteur
On Attack: If you control another Bounty Hunter unit, you may deal damage eq
- [x] **Batch 3.3 — LAW_068, LAW_076, LAW_079, LAW_087** (+4 tests, 1805; space-debuff+ground-buff, WhenPlayed-shield-if-discarded[new SWU_DISCARDED_PHASE]+OnAttack-deal-exhaust, deal-damaged-ground, exhaust-if-upgraded)
  - LAW_068 Millennium Falcon: On Attack: You may give a space unit -2/-0 for this phase. You may give a ground unit
  - LAW_076 Vult Skerris's Defender: When Played: If you discarded a card from your hand or deck this phase, give a Shield
  - LAW_079 K-2SO: Ambush
On Attack: You may deal 3 damage to a damaged ground unit.
  - LAW_087 Jango Fett: Shielded
On Attack: If this unit is upgraded, exhaust an enemy unit.
- [x] **Batch 3.4 — LAW_095, LAW_101, LAW_104, LAW_107** (+4 tests, 1809; shield-non-unique, aspect -2/-2, grant-Rebel-Sentinel, OnAttack-draw)
  - LAW_095 Finn: Ambush
On Attack: You may give a Shield token to a non-<uq> unit.
  - LAW_101 Lawbringer: When Played/On Attack: Choose an aspect. Give each enemy unit with that aspect -2/-2
  - LAW_104 Bodhi Rook: On Attack: You may give a friendly Rebel unit Sentinel for this phase.
  - LAW_107 Swoop Bike Marauder: On Attack: Draw a card.
- [x] **Batch 3.5 — LAW_115, LAW_152, LAW_155, LAW_158** (+4 tests, 1813; reveal-nonunit-Exp, Exp-shared-leader-trait, credit-if-ground, next-Underworld-unit -1 discount)
  - LAW_115 Rickety Quadjumper: On Attack: You may reveal the top card of your deck. If it\'s not a unit, give an Exp
  - LAW_152 C-3PO: On Attack: You may give an Experience token to another non-leader unit that shares a
  - LAW_155 Getaway Freighter: On Attack: If you control a ground unit, create a Credit token.
  - LAW_158 Khetanna: When Played/On Attack: The next Underworld unit you play this phase costs 1 resource
- [x] **Batch 3.6 — LAW_163, LAW_173, LAW_174, LAW_178** (+4 tests, 1817; discard-unit-to-bottom-deal-power, mill-Aggression-deal, Aggression-to-bottom-deal-bases, arena AOE)
  - LAW_163 The Sarlacc of Carkoon: On Attack: Put a unit from your discard pile on the bottom of your deck. Deal damage
  - LAW_173 BT-1: On Attack: Discard a card from your deck. If it\'s Aggression, you may deal 1 damage
  - LAW_174 0-0-0: On Attack: You may put a Aggression card from your discard pile on the bottom of your
  - LAW_178 Persecutor: When Played/On Attack: Choose an arena. You may deal 3 damage to each unit in that ar
- [x] **Batch 3.7 — LAW_181, LAW_182, LAW_184, LAW_191** (+4 tests, 1821; deal2-base, grant-Raid2, deal-ground+base, defeat-credit-deal-unit/base)
  - LAW_181 Cloud-Rider Veteran: On Attack: Deal 2 damage to a base.
  - LAW_182 Weazel: On Attack: Another friendly unit gains Raid 2 for this phase.
  - LAW_184 Aerie: On Attack: Deal 2 damage to an enemy ground unit and 2 damage to a base.
  - LAW_191 Arvel Skeen: When Played/On Attack: You may defeat a Credit token. If you do, deal 1 damage to a u
- [x] **Batch 3.8 — LAW_192, LAW_194, LAW_197, LAW_214** (+4 tests, 1825; mill1, mill3-return-Underworld, no-base-heal[SOR_160 lock], pay1-deal3)
  - LAW_192 Bracca Shipbreaker: On Attack: Discard a card from your deck.
  - LAW_194 Doctor Aphra: On Attack: Discard 3 cards from your deck. You may return an Underworld card discarde
  - LAW_197 Shifty Suspects: On Attack: Bases can\'t be healed for this phase.
  - LAW_214 Boba Fett: When Played/On Attack: You may pay 1 resource. If you do, deal 3 damage to a ground u
- [x] **Batch 3.9 — LAW_216, LAW_221, LAW_224, LAW_228** (+4 tests, 1829; opp-picks-deal7, take-enemy-credit, exhaust+bounce-cheap-upgrades [SWUDefeatUpgrade bounce], debuff-if-first-attacker)
  - LAW_216 Jabba's Rancor: Hidden
On Attack: An opponent chooses a ground unit they control. You may deal 7 dama
  - LAW_221 Lieutenant Gorn: On Attack: Take control of an enemy Credit token.
  - LAW_224 Liberty: Sentinel
When Played/On Attack: Exhaust an enemy unit and return all upgrades on it t
  - LAW_228 Canyon Frontrunner: On Attack: If no other units have attacked this phase, you may give a unit -2/-0 for
- [x] **Batch 3.10 — LAW_236, LAW_237, LAW_238, LAW_258** (+4 tests, 1833; discard-hand-credit, look-top3-discard1, discard-to-bottom-credit, pay2-credit)
  - LAW_236 Bix Caleen: When Played/On Attack: You may discard a card from your hand. If you do, create a Cre
  - LAW_237 Qui-Gon Jinn: Sentinel
When Played/On Attack: Look at the top 3 cards of your deck. You may discard
  - LAW_238 Scavenging Sandcrawler: On Attack: You may put a card from your discard pile on the bottom of your deck. If y
  - LAW_258 Criminal Contact: On Attack: You may pay 2 resources. If you do, create a Credit token.

## Phase 4 — Unit When Defeated / On Defense (autonomous)
- [x] **Batch 4.1 — LAW_058, LAW_059, LAW_091, LAW_097** (+7 tests, 1840; WP-deal-base/WD-discount, WP-Command-Exp/WD-Aggression-Exp, WP-shield-friendly/WD-shield-enemy, WD-heal-base)
  - LAW_058 Honor-Bound Partisan: When Played: Deal 1 damage to a base.
When Defeated: The next unit you play this phas
  - LAW_059 Highsinger: When Played: Give an Experience token to another friendly Command unit.
When Defeated
  - LAW_091 Val: When Played: Give a Shield token to another friendly unit.
When Defeated: Give a Shie
  - LAW_097 Imperial Door Technician: When Defeated: Heal 2 damage from your base.
- [x] **Batch 4.2 — LAW_116, LAW_142, LAW_159, LAW_185** (+4 tests, 1844; WD-each-credit, WD-Exp-Rebel, WD-resource-self, WP/WD-ready-another+CANT_BE_ATTACKED)
  - LAW_116 Rodian Bondsman: When Defeated: Each player creates a Credit token.
  - LAW_142 Scarif Lieutenant: When Defeated: Give an Experience token to a friendly Rebel unit.
  - LAW_159 Expendable Mercenary: When Defeated: You may resource this unit from its owner\'s discard pile.
  - LAW_185 Ben Solo: Hidden
When Played/When Defeated: Ready another friendly unit. It can\'t be attacked
- [x] **Batch 4.3 — LAW_189, LAW_195** (+2 tests, 1846; WD-deal2-base, WP/WD-defeat-space-upgrade [onlyHostUID])
  - LAW_189 Cavern Angels X-Wing: When Defeated: Deal 2 damage to a base.
  - LAW_195 Overcharged Transport: When Played/When Defeated: You may defeat an upgrade attached to a space unit.

## Phase 5 — Unit passives & Action [Exhaust] abilities (autonomous)
- [x] **Batch 5.1 — LAW_033, LAW_034, LAW_036, LAW_046** (+4 tests, 1850; AttackEnd-defeat-lower-power, AttackEnd-Exp+heal, 7-units-7/7 passive, AttackEnd-heal-if-base. Note: LAW_033/034/046 wired via SWUCollectCombatHitTriggers switch — no onAttackEnd stub needed)
  - LAW_033 Hound's Tooth: When Attack Ends: If this unit survived, you may defeat a unit with less power than t
  - LAW_034 Chewbacca: Overwhelm
When Attack Ends: If the defending unit was defeated, give an Experience to
  - LAW_036 Obi-Wan Kenobi: Sentinel
While you control 7 or more units, their printed power is considered to be 7
  - LAW_046 Chirrut Îmwe: Saboteur
When Attack Ends: If this unit dealt combat damage to a base, you may heal 4
- [x] **Batch 5.2 — LAW_047, LAW_053, LAW_054, LAW_056** (+4 tests, 1854; healed→deal-that-much [_SWUOnUnitHealed amount], highest-cost-enemy-defeated-credit, AttackEnd-temp-take-control [SWU_SEC192 revert], friendly-defeat→deal2-base)
  - LAW_047 Baze Malbus: Sentinel
When 1 or more damage is healed from this unit: You may deal that much damag
  - LAW_053 Dengar: When a unit with the highest cost among enemy units is defeated: Create a Credit toke
  - LAW_054 Maul: Overwhelm
When Attack Ends: If this unit dealt combat damage to a player\'s base, you
  - LAW_056 Cassian Andor: When a friendly unit\'s attack ends: If the defending unit was defeated, deal 2 damag
- [x] **Batch 5.3 — LAW_071, LAW_073** (+2 tests, 1856; regroup-credit, regroup-Exp+can't-ready). ⚠ **LAW_072 DEFERRED** (extra regroup phase = turn-FSM change). ⚠ **LAW_074 DEFERRED** (search top5 + play a specific deck card discounted/ready + delayed deck-bottom — needs a play-arbitrary-deck-card seam).
  - LAW_071 The Max Rebo Band: When the regroup phase starts: Create a Credit token.
  - LAW_072 Max Rebo: There is an additional regroup phase after the first regroup phase each round.
  - LAW_073 Patient Hunter: When the regroup phase starts: You may give an Experience token to a non-leader unit.
  - LAW_074 Maz Kanata: When Attack Ends: If this unit survived, search the top 5 cards of your deck for an U
- [x] **Batch 5.4 — LAW_084, LAW_088, LAW_094** (+3 tests, 1859; Action[discard2]-bounce, friendly-attack-end-return+heal, Action-play-top-deck once/round. Lesson: $unitAbilities['X'] must be registered AFTER the $unitAbilities=[] init line). ⚠ **LAW_086 DEFERRED** (optional 'defender deals combat damage first' = new reverse-combat-ordering path).
  - LAW_084 Krrsantan: Ambush
Overwhelm
Action [discard 2 cards from your hand]: Return this unit to your ha
  - LAW_086 The Stranger: Ambush
Grit
While attacking, you may have the defending unit deal combat damage befor
  - LAW_088 Anakin Skywalker: When a friendly unit\'s attack ends: If no other units have attacked this phase, you
  - LAW_094 Hondo Ohnaka: You may look at the top card of your deck at any time.
Action: Play the top card of y
- [x] **Batch 5.5 — LAW_105, LAW_108, LAW_110, LAW_112** (+4 tests, 1863; Sentinel-while-upgraded, defender-debuffs-attacker, cost-per-damaged, first-attack-heal-base)
  - LAW_105 Cinta Kaz: While this unit is upgraded, she gains Sentinel.
  - LAW_108 Lando Calrissian: Sentinel
While this unit is defending, the attacker gets -1/-0.
  - LAW_110 Phoenix Squadron Fighters: This unit costs 1 resource less to play for each friendly damaged unit.
  - LAW_112 Boonta Eve Flagbearer: When a friendly unit attacks: If no other units have attacked this phase, heal 2 dama
- [x] **Batch 5.6 — LAW_119, LAW_139, LAW_149** (+2 tests, 1865; friendly-defeat-scry, leader-units +2/+2; LAW_149 immunities pre-wired [SWUAvoids*] verify-only). ⚠ **LAW_156 DEFERRED** (any-player-usable unit Action — opponent-usable activated ability, new framework seam).
  - LAW_119 Rogue One: When a friendly unit is defeated: Look at the top 2 cards of your deck. Put any numbe
  - LAW_139 Admiral Motti: Friendly leader units get +2/+2.
  - LAW_149 Rey: Opponents can\'t take control of this unit.
This unit can\'t be defeated by enemy car
  - LAW_156 Hunter For Hire: Action [defeat a friendly Credit token]: Take control of this unit. Any player may us
- [x] **Batch 5.7 — LAW_176, LAW_210, LAW_212** (+5 tests, 1870; deck-discard may-ready via trigger bag [SWU_LAW176_USED once/round], SEC_170-style enters-ready gated on controlling Jabba, Malakili Underworld trait-grant via _SWUUnitHasTrait routed through LAW_134/LAW_249/JTL_139 in-play reads). ⚠ **LAW_215 DEFERRED** (Vermillion — When Attack Ends cross-player reveal/choose-player/play-from-either-deck-for-free + different-player credits: Hard, real design fork).
  - LAW_176 Sebulba's Podracer: When you discard a card from your deck: You may ready this unit. Use this ability onl
  - LAW_210 Salacious Crumb: Raid 2
If you control Jabba the Hutt, this unit enters play ready.
  - LAW_212 Malakili: Each friendly Creature unit and each Creature unit you own that isn\'t in play gains
  - LAW_215 Vermillion: When Attack Ends: If this unit survived, reveal the top card of a deck, then choose a
- [x] **Batch 5.8 — LAW_219, LAW_223, LAW_235, LAW_252** (+5 tests, 1875; conditional SHOOT_FIRST for first-attacker Podracer, enters-ready gated on a non-unique unit, Lady Proxima exhaust→Credit unit action, Firespray Credit-on-defender-defeated via combat-hit switch).
  - LAW_219 Anakin's Podracer: Ambush
While attacking, if no other units have attacked this phase, this unit deals c
  - LAW_223 Rose Tico: If you control a non-<uq> unit, this unit enters play ready.
  - LAW_235 Lady Proxima: Action [Exhaust]: Create a Credit token.
  - LAW_252 Fett's Firespray: Ambush
When Attack Ends: If the defending unit was defeated, create a Credit token.

## Phase 6 — Upgrades (autonomous)
- [x] **Batch 6.1 — LAW_077, LAW_111, LAW_125, LAW_126** (+4 tests, 1879; can't-ready-while-attached + regroup-start base damage, Underworld-grant + when-played Leia shield, granted On-Attack scry-a-deck w/ deck choice, granted exhaust-action SET_HP_1 phase marker [new turnEffectRegistry row]).
  - LAW_077 Shadow of Stygeon Prime: Attach to a non-leader unit.
Attached unit can\'t ready. It gains: "When the regroup
  - LAW_111 Leia's Disguise: Attach to a non-Vehicle unit.
Attached unit gains the Underworld trait.
When Played:
  - LAW_125 Watchful: Attached unit gains: "On Attack: Look at the top card of a deck. You may put it on th
  - LAW_126 Adventurer Sniper Rifle: Attach to a non-Vehicle unit.
Attached unit gains: "Action [Exhaust]: Choose an undam
- [x] **Batch 6.2 — LAW_127, LAW_128, LAW_129, LAW_141** (+5 tests, 1884; when-played exhaust-host, Grit-grant, host-conditional -1 cost [two-mode like SOR_061], granted When-Defeated opp-Credits=host-cost [SEC_156-style subcard scan]).
  - LAW_127 Kill Switch: When Played: Exhaust attached unit.
  - LAW_128 Veiled Strength: Attach to a non-leader unit.
Attached unit gains Grit.
  - LAW_129 Mastery: This upgrade costs 1 resource less to play on a <uq> unit.
  - LAW_141 Targeted For Removal: Attached unit gains: "When Defeated: An opponent creates Credit tokens equal to this
- [x] **Batch 6.3 — LAW_150, LAW_186, LAW_187** (+3 tests, 1887; Fulcrum Rebel-grant + other-Rebel +2/+2 aura, Helmet granted On-Attack may +3/+0, Repeater when-played deal-1-to-3-ground [UID-safe vs index-shift]). ⚠ **LAW_200 DEFERRED** (Salvaged Blaster — Action playable from the DISCARD pile if discarded this phase: no discard-pile activated-ability surface exists; new infra).
  - LAW_150 Fulcrum: Attach to a non-Vehicle unit.
Attached unit gains the Rebel trait and "Each other fri
  - LAW_186 Enfys Nest's Helmet: Attach to a non-Vehicle unit.
Attached unit gains: "On Attack: You may give another u
  - LAW_187 "Staccato Lightning" Repeater: Attach to a non-Vehicle unit.
When Played: Deal 1 damage to each of up to 3 different
  - LAW_200 Salvaged Blaster: Attach to a non-Vehicle unit.
Action: If this upgrade was discarded from your hand or
- [x] **Batch 6.4 — LAW_201, LAW_225** (+4 tests, 1891; granted When-Defeated 'if ready' blast-2-each-enemy-ground [snapshot ready via mzID slot, UID-safe], granted On-Attack mill→odd-cost Credit). Note: cross-player auto When-Defeated triggers queue for the non-active owner; tests drain via a trailing P1>Pass.
  - LAW_201 Thermal Detonator: Attach to a non-Vehicle unit.
Attached unit gains: "When Defeated: If this unit was r
  - LAW_225 Han's Golden Dice: Attached unit gains: "On Attack: Discard a card from your deck. If its cost is odd, c

## Phase 7 — Bases (non-common) (autonomous)
- [x] **Batch 7.1 — LAW_019, LAW_023, LAW_026, LAW_029** (+4 tests, 1895; Epic-Action bases: defeat-token→Exp/Shield/Credit mode-pick, discard-unit→search-deck-for-Sarlacc, mill-3-return-top, [1 resource] return-resource→resource-top-of-deck).
  - LAW_019 Alliance Outpost: Epic Action [defeat a friendly token]: Give an Experience or Shield token to a unit,
  - LAW_023 Great Pit of Carkoon: Epic Action [discard a unit from your hand]: Search your deck for a card named The Sa
  - LAW_026 Shipbreaking Yard: Epic Action: Discard 3 cards from your deck. You may return a card discarded this way
  - LAW_029 Citadel Research Center: Epic Action [1 resource]: Return a friendly resource to its owner\'s hand. If you do,

## Phase 8 — Leaders (front action + deployed side) (autonomous)
- [x] **Batch 8.1 — LAW_001, LAW_002, LAW_003, LAW_004** (+5 tests, 1900; two-sided leaders: front Actions all tested + LAW_004 deployed When-Deployed [deploy path validated]. LAW_001 attack-buff-self-defeat [LAW_062 marker], LAW_002 give-control+credit / deployed defeat-owned-not-controlled, LAW_003 play-ignoring-aspect [front+deployed action]+Heroism-play heal, LAW_004 defeat-by-remaining-HP. Deployed sides of 001/002/003 wired via shared helpers + own-play hook, front-tested.)
  - LAW_001 Saw Gerrera: Action [Exhaust]: Attack with a unit. It gets +2/+0 and gains Overwhelm for this atta ||dep: When Attack Ends: If this unit survived, you may attack
  - LAW_002 Tobias Beckett: Action [Exhaust]: Choose a friendly unit. An opponent takes control of it. If they do ||dep: When Deployed: Defeat any number of units you own but d
  - LAW_003 Agent Kallus: Action [1 resource, Exhaust]: Play a card from your hand, ignoring its aspect penalti ||dep: Action [1 resource]: Play a card from your hand, ignori
  - LAW_004 Aurra Sing: Action [Exhaust]: Defeat a non-leader unit with 1 or less remaining HP.
Epic Action:  ||dep: When Deployed: You may defeat a non-leader unit with 5
- [x] **Batch 8.2 — LAW_005, LAW_006, LAW_007, LAW_008** (+6 tests, 1905; Jyn search-after-Rebel-defeat [new SWU_REBEL_DEFEATED flag], Vel Exp+opp-Credit, Boba combat-observer may-exhaust→Credit [deployed mandatory + auto Raid], Krennic defeat-friendly→Credit / deployed friendly-deals-power [reuses SOR_127#1]).
  - LAW_005 Jyn Erso: Action [1 resource, Exhaust]: If a friendly Rebel unit was defeated this phase, searc ||dep: On Attack: If a friendly Rebel unit was defeated this p
  - LAW_006 Vel Sartha: Action [Exhaust]: Give an Experience token to a unit. An opponent creates a Credit to ||dep: On Attack: You may give an Experience token to a unit.
  - LAW_007 Boba Fett: When a friendly Bounty Hunter unit\'s attack ends: If the defending unit was defeated ||dep: Raid 1
When a friendly Bounty Hunter unit\'s attack end
  - LAW_008 Director Krennic: Action [Exhaust, defeat a friendly unit]: Create a Credit token.
Epic Action: If you  ||dep: When Deployed: Another friendly unit deals damage equal
- [x] **Batch 8.3 — LAW_009, LAW_010, LAW_011, LAW_012** (+4 tests, 1909; Hera passive Heroism cost-waive [controls-Hera+2-units], Leia front +1/+1-per-own-aspect / deployed Exp-per-controlled-aspect, Vader front discard→deal-1-unit/base / deployed discard-N→deal-N, Sebulba front mill→grant-Raid-1 / deployed mill).
  - LAW_009 Hera Syndulla: While you control 2 or more units, ignore the aspect penalties on Heroism units you p ||dep: Restore 1
While you control 2 or more units, ignore the
  - LAW_010 Leia Organa: Action [2 resources, Exhaust]: For this phase, give a unit +1/+1 for each different a ||dep: Overwhelm
When Deployed: Choose a unit. Give an Experie
  - LAW_011 Darth Vader: Action [Exhaust, discard a card from your hand]: Deal 1 damage to a unit or base.
Epi ||dep: On Attack: Discard any number of cards from your hand.
  - LAW_012 Sebulba: Action [Exhaust, discard a card from your deck]: A friendly unit gains Raid 1 for thi ||dep: Raid 1
On Attack: Discard a card from your deck.
- [x] **Batch 8.4 — LAW_013, LAW_016** (+2 tests, 1911; Chewbacca defeat-resource→deal-2+Credit [front+deployed], The Client created-token-this-phase→exhaust-enemy [new SWU_CREATED_TOKEN flag on all token creators]). ⚠ **LAW_014 Enfys Nest DEFERRED** (re-use an arbitrary On-Attack ability — no re-invoke infra; Hard). ⚠ **LAW_015 Jabba DEFERRED** (deployed play-Underworld + Ambush-if-Credit-defeated-while-paying needs credit-pay detection across the ActivateCard boundary; Hard).
  - LAW_013 Chewbacca: Action [1 resource, Exhaust, defeat a friendly resource]: Deal 2 damage to a unit and ||dep: On Attack: You may defeat a friendly resource. If you d
  - LAW_014 Enfys Nest: When you use an "On Attack" ability: You may pay 2 resources and exhaust this leader. ||dep: When you use an "On Attack" ability: You may use that a
  - LAW_015 Jabba the Hutt: Action [1 resource, Exhaust, return a friendly Underworld unit to its owner\'s hand]: ||dep: Action: Play an Underworld unit from your hand. If you
  - LAW_016 The Client: Action [Exhaust]: If you created a token this phase, exhaust an enemy unit.
Epic Acti ||dep: Shielded
On Attack: If you created a token this phase,
- [x] **Batch 8.5 — LAW_017, LAW_018** (+3 tests, 1914; Han defeat-token(s)→deal [front 1, deployed N; tokens = Token Units + Credits, UID/re-resolve safe], Lando aspect+mill-a-deck→conditional-Credit / deployed defeat-Credit→create-3).
  - LAW_017 Han Solo: Action [Exhaust, defeat a friendly token]: Deal 1 damage to a unit.
Epic Action: If y ||dep: Saboteur
On Attack: Defeat any number of friendly token
  - LAW_018 Lando Calrissian: Action [1 resource, Exhaust]: Choose an aspect, then discard a card from a deck. If i ||dep: When Deployed: You may defeat a friendly Credit token.
