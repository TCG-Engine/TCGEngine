<?php
function CardIDOverride($cardID) {
  switch($cardID) {
    case "SHD_030": return "SOR_033"; //Death Trooper
    case "SHD_063": return "SOR_066"; //System Patrol Craft
    case "SHD_066": return "SOR_068"; //Cargo Juggernaut
    case "SHD_070": return "SOR_069"; //Resilient
    case "SHD_081": return "SOR_080"; //General Tagge
    case "SHD_085": return "SOR_083"; //Superlaser Technician
    case "SHD_083": return "SOR_081"; //Seasoned Shoretrooper
    case "SHD_166": return "SOR_162"; //Disabling Fang Fighter
    case "SHD_223": return "SOR_215"; //Snapshot Reflexes
    case "SHD_231": return "SOR_220"; //Surprise Strike
    case "SHD_236": return "SOR_227"; //Snowtrooper Lieutenant
    case "SHD_238": return "SOR_229"; //Cell Block Guard
    case "SHD_257": return "SOR_247"; //Underworld Thug
    case "SHD_262": return "SOR_251"; //Confiscate
    case "SHD_121": return "SOR_117"; //Mercenary Company
    case "TWI_077": return "SOR_078"; //Vanquish
    case "TWI_107": return "SOR_111"; //Patrolling V-Wing
    case "TWI_123": return "SHD_128"; //Outflank
    case "TWI_124": return "SOR_124"; //Tactical Advantage
    case "TWI_127": return "SOR_126"; //Resupply
    case "TWI_128": return "SHD_131"; //Take Captive
    case "TWI_170": return "SHD_178"; //Daring Raid
    case "TWI_174": return "SOR_172"; //Open Fire
    case "TWI_226": return "SOR_222"; //Waylay
    case "TWI_254": return "SOR_248"; //Volunteer Soldier
    case "C24_001": return "SOR_038"; //Count Dooku - Darth Tyranus
    case "C24_002": return "SOR_087"; //Darth Vader - Commanding the First Legion
    case "C24_003": return "SOR_135"; //Emperor Palpatine - Master of the Dark Side
    case "C24_004": return "SHD_141"; //Kylo Ren - Killing the Past
    case "C24_005": return "TWI_134"; //Asajj Ventress - Count Dooku's Assassin
    case "C24_006": return "TWI_135"; //Darth Maul - Revenge at Last
    case "J24_001": return "SOR_040"; //Avenger
    case "J24_002": return "SOR_145"; //K-2SO
    case "J24_003": return "SHD_037"; //Supreme Leader Snoke
    case "J24_004": return "SHD_090"; //Maul
    case "J24_005": return "SHD_154"; //Wrecker
    case "J24_006": return "SHD_248"; //Tech
    case "GG_001": return "SOR_021"; //Dagobah Swamp
    case "GG_002": return "SOR_024"; //Echo Base
    case "GG_003": return "SOR_026"; //Catacombs of Cadera
    case "GG_004": return "SHD_026"; //Jabba's Palace
    case "GG_005": return "SOR_001"; //Experience Token
    case "GG_006": return "SOR_002"; //Shield Token
    case "JTL_029": return "SOR_030"; //Chopper Base
    case "JTL_258": return "SOR_250"; //Corellian Freighter
    case "JTL_113": return "SOR_113"; //Homestead Militia
    case "JTL_167": return "SOR_165"; //Occupier Siege Tank
    case "JTL_128": return "SOR_125"; //Prepare Prepare for Takeoff
    case "JTL_075": return "SOR_074"; //Repair
    case "JTLW_001": return "JTL_032"; //Director Krennic
    case "JTLW_002": return "JTL_033"; //Onyx Squadron Brute
    case "JTLW_003": return "JTL_045"; //Hera Syndulla
    case "JTLW_004": return "JTL_048"; //Cassian Andor
    case "JTLW_005": return "JTL_097"; //Leia Organa
    case "JTLW_006": return "JTL_100"; //Poe Dameron
    case "JTLW_007": return "JTL_103"; //Chewbacca
    case "JTLW_008": return "JTL_134"; //General Hux
    case "JTLW_009": return "JTL_141"; //IG-88
    case "JTLW_010": return "JTL_145"; //BB-8
    case "JTLW_011": return "JTL_149"; //Red Squadron Y-Wing
    case "JTLW_012": return "JTL_151"; //Red Five
    case "JTLW_013": return "JTL_161"; //Captain Tarkin
    case "JTLW_014": return "JTL_176"; //Shoot Down
    case "JTLW_015": return "JTL_189"; //Boba Fett
    case "JTLW_016": return "JTL_197"; //Anakin Skywalker
    case "JTLW_017": return "JTL_203"; //Han Solo
    case "JTLW_018": return "JTL_240"; //Fett's Firespray
    case "JTLW_019": return "JTL_249"; //Millennium Falcon
    case "JTLW_020": return "JTL_256"; //Swarming Vulture Droid
    case "P25_001":
    case "P25_002": return "JTL_187"; //Bossk
    case "P25_003":
    case "P25_004":
    case "P25_005":
    case "P25_006": return "JTL_143"; //Devastator
    case "P25_007":
    case "P25_008": return "JTL_147"; //Black One
    case "P25_009":
    case "P25_010":
    case "P25_011":
    case "P25_012": return "JTL_204"; //Home One
    case "J25_001": return "JTL_053"; //The Ghost
    case "J25_002": return "TWI_064"; //Ki-Adi-Mundi
    case "J25_003": return "TWI_080"; //Poggle The Lesser
    case "J25_004": return "JTL_094"; //Luke Skywalker
    case "J25_005": return "TWI_135"; //Darth Maul
    case "J25_006": return "JTL_142"; //Darth Vader
    case "J25_007": return "TWI_138"; //Count Dooku
    case "J25_008": return "JTL_201"; //Ahsoka Tano - Chasing Whispers
    case "J25_009": return "TWI_198"; //Enfys Nest
    case "P25_046": return "SHD_141"; //Kylo Ren
    case "P25_044": return "SOR_049"; //Obi-Wan Kenobi
    case "P25_039": return "SOR_097"; //Admiral Ackbar
    case "P25_040": return "SOR_115"; //Agent Kallus
    case "P25_041": return "SOR_144"; //Red Three
    case "P25_042": return "SOR_196"; //Chewbacca
    case "P25_043": return "SOR_198"; //Han Solo
    case "P25_052": return "SHD_073"; //Mandalorian Armor
    case "P25_053": return "TWI_138"; //Count Dooku
    case "P25_054": return "TWI_165"; //Kit Fisto
    case "P25_055": return "SOR_184"; //Fett's Firespray
    case "P25_056": return "TWI_189"; //Unnatural Life
    case "P25_057": return "SOR_135"; //Emperor Palpatine - Master of the Dark Side
    case "P25_059": return "SHD_046"; //Rey
    case "LOF_058": return "SOR_061"; //Guardian of the Whills
    case "LOF_060": return "TWI_058"; //Padawan Starfighter
    case "LOF_162": return "SHD_168"; //Hunting Nexu
    case "LOF_164": return "SOR_164"; //Wampa
    case "SEC_030": return "SOR_033"; //Death Trooper
    case "SEC_184": return "SOR_176"; //ISB Agent
    case "SEC_250": return "SOR_239"; //Rebel Pathfinder
    case "LAW_115": return "SHD_057"; //Rickety Quadjumper
    case "LAW_175": return "JTL_136"; //Prototype TIE Advanced
    case "LAW_253": return "SOR_237"; //Alliance X-Wing
    case "LAW_261": return "SHD_260"; //Street Gang Recruiter
    case "ASH_122": return "SOR_112"; //Consortium StarViper
    case "ASH_152": return "LAW_180"; //Inspired Recruit
    case "ASH_258": return "SEC_258"; //Grassroots Resistance
    // ── Intra-set duplicate printings (IBH) ──────────────────────────────────────────────────────
    // IBH prints many cards at several collector numbers within the one product; every printing here
    // is rarity Special. Same Title + Subtitle = the same card, so they MUST fold together — the
    // 3-copy limit is computed as array_count_values(array_map('CardIDOverride', $pool))
    // (AppCore/SWU/DeckValidation.php:223), so unlinked printings each got their own budget of 3 and
    // a deck could legally run six Hoth Lieutenants.
    // Canonical is the LOWEST collector number: cross-set reprints fold to the earliest SET, and
    // within a single set the lowest number is the equivalent reading.
    // Display is unaffected either way — SWUDisplayCardID never selects a Special printing.
    case "IBH_046": return "IBH_003"; //Chewbacca - Rrruuuurrr
    case "IBH_017": return "IBH_004"; //Rogue Squadron Speeder
    case "IBH_034": return "IBH_004"; //Rogue Squadron Speeder
    case "IBH_039": return "IBH_005"; //I'll Cover For You
    case "IBH_024": return "IBH_006"; //Rebellion Y-Wing
    case "IBH_032": return "IBH_006"; //Rebellion Y-Wing
    case "IBH_043": return "IBH_007"; //Echo Coordinator
    case "IBH_047": return "IBH_007"; //Echo Coordinator
    case "IBH_029": return "IBH_008"; //Trench Defender
    case "IBH_050": return "IBH_008"; //Trench Defender
    case "IBH_025": return "IBH_009"; //I've Found Them
    case "IBH_042": return "IBH_010"; //Han Solo - Scruffy-Looking Nerf Herder
    case "IBH_049": return "IBH_011"; //R2-D2 - Known to Make Mistakes
    case "IBH_035": return "IBH_012"; //Evacuation Escort
    case "IBH_044": return "IBH_012"; //Evacuation Escort
    case "IBH_026": return "IBH_014"; //Bright Hope - Narrow Escape
    case "IBH_028": return "IBH_015"; //Tauntaun Mount
    case "IBH_051": return "IBH_015"; //Tauntaun Mount
    case "IBH_027": return "IBH_016"; //Ion Cannon
    case "IBH_045": return "IBH_018"; //Go for the Legs
    case "IBH_041": return "IBH_019"; //C-3PO - Oh Dear, Oh Dear
    case "IBH_030": return "IBH_021"; //Improvised Detonation
    case "IBH_033": return "IBH_022"; //GR-75 Medium Transport
    case "IBH_040": return "IBH_022"; //GR-75 Medium Transport
    case "IBH_036": return "IBH_023"; //General Rieekan - Stalwart Tactician
    case "IBH_038": return "IBH_037"; //Hoth Trooper
    case "IBH_048": return "IBH_037"; //Hoth Trooper
    case "IBH_073": return "IBH_055"; //First Legion Trooper
    case "IBH_101": return "IBH_055"; //First Legion Trooper
    case "IBH_067": return "IBH_056"; //Ground Assault AT-AT
    case "IBH_080": return "IBH_057"; //Snowtrooper Vanguard
    case "IBH_096": return "IBH_057"; //Snowtrooper Vanguard
    case "IBH_084": return "IBH_058"; //Lambda Shuttle
    case "IBH_090": return "IBH_058"; //Lambda Shuttle
    case "IBH_071": return "IBH_059"; //Target the Main Generator
    case "IBH_065": return "IBH_060"; //Admiral Piett - In Command Now
    case "IBH_086": return "IBH_061"; //We're In Trouble
    case "IBH_100": return "IBH_062"; //Imperial Deck Officer
    case "IBH_077": return "IBH_063"; //Snowtrooper
    case "IBH_087": return "IBH_063"; //Snowtrooper
    case "IBH_092": return "IBH_064"; //Hoth Lieutenant
    case "IBH_091": return "IBH_066"; //Too Strong for Blasters
    case "IBH_088": return "IBH_068"; //General Veers - Leading the Assault
    case "IBH_083": return "IBH_069"; //E-Web Gunner
    case "IBH_089": return "IBH_070"; //Blizzard Force AT-ST
    case "IBH_103": return "IBH_070"; //Blizzard Force AT-ST
    case "IBH_102": return "IBH_074"; //I Want Proof, Not Leads
    case "IBH_081": return "IBH_075"; //Scouting TIE Fighter
    case "IBH_097": return "IBH_075"; //Scouting TIE Fighter
    case "IBH_093": return "IBH_078"; //Surface Assault Bomber
    case "IBH_094": return "IBH_078"; //Surface Assault Bomber
    case "IBH_098": return "IBH_079"; //Death Squadron Star Destroyer
    case "IBH_085": return "IBH_082"; //Admiral Ozzel - As Clumsy as He Is Stupid
    case "SEC_239": return "SOR_228"; //Viper Probe Droid
    case "IC27_097": return "SOR_128"; //Death Star Stormtrooper
    case "IC27_015": return "JTL_033"; //Onyx Squadron Brute
    case "IC27_108": return "TWI_137"; //Savage Opress
    case "IC27_136": return "SOR_172"; //Open Fire
    case "IC27_154": return "SHD_196"; //Grogu
    case "IC27_188": return "SOR_238"; //C-3PO
    case "IC27_194": return "SOR_246"; //You're My Only Hope
    case "HMW_022": return "JTL_020"; //Shield Generator Complex
    case "HMW_025": return "JTL_023"; //Theed Palace
    case "HMW_032": return "JTL_030"; //Mos Eisley
    case "HMW_096": return "SOR_070"; //Devotion
    case "HMW_239": return "LOF_224"; //Pounce
    case "IC27_009": return "JTL_019"; //City in the Clouds
    case "IC27_010": return "SOR_024"; //Echo Base
    case "IC27_011": return "TWI_026"; //KCM Mining Facility
    case "IC27_012": return "JTL_030"; //Mos Eisley
    case "IC27_016": return "TWI_031"; //Rune Haako
    case "IC27_017": return "SOR_033"; //Death Trooper
    case "IC27_020": return "IBH_060"; //Admiral Piett
    case "IC27_025": return "IBH_099"; //Blizzard One
    case "IC27_027": return "SOR_039"; //AT-AT Suppressor
    case "IC27_028": return "IBH_072"; //Avenger
    case "IC27_030": return "TWI_041"; //Lethal Crackdown
    case "IC27_052": return "SOR_069"; //Resilient
    case "IC27_053": return "SEC_071"; //Disciples' Devotion
    case "IC27_073": return "JTL_095"; //Phoenix Squadron A-Wing
    case "IC27_080": return "IBH_014"; //Bright Hope
    case "IC27_082": return "IBH_020"; //Luke Skywalker
    case "IC27_085": return "SHD_104"; //Inspiring Mentor
    case "IC27_090": return "SOR_113"; //Homestead Militia
    case "IC27_093": return "SOR_120"; //Academy Training
    case "IC27_094": return "JTL_123"; //Dogfight
    case "IC27_105": return "IBH_068"; //General Veers
    case "IC27_186": return "SOR_236"; //R2-D2
    case "IC27_161": return "IBH_010"; //Han Solo
    case "IC27_164": return "IBH_031"; //Millennium Falcon
    case "IC27_169": return "SEC_213"; //A-Wing
    case "IC27_178": return "SOR_225"; //TIE/ln Fighter
    case "IC27_185": return "SOR_233"; //I Am Your Father
    case "IC27_191": return "LOF_250"; //Medical Frigate
    case "IC27_182": return "LOF_235"; //HK-87 Assassin Droid
    case "IC27_173": return "TWI_214"; //Hidden Sharpshooter
    case "IC27_175": return "SOR_217"; //Shoot First
    case "IC27_184": return "LOF_239"; //Consumed by the Dark Side
    case "IC27_190": return "JTL_248"; //Dilapidated Ski Speeder
    case "IC27_192": return "TWI_244"; //Eta-2 Light Interceptor
    case "IC27_189": return "TWI_241"; //Phase I Clone Trooper
    case "IC27_181": return "JTL_241"; //Rogue-class Starfighter
    case "IC27_180": return "TWI_230"; //Super Battle Droid
    case "IC27_131": return "JTL_164"; //Cham Syndulla
    case "IC27_129": return "LOF_155"; //DRK-1 Probe Droid
    case "IC27_018": return "LOF_032"; //Magistrate's Scout
    case "IC27_036": return "SHD_042"; //Concord Dawn Interceptors
    case "IC27_055": return "LAW_130"; //Betrayed Trust
    case "IC27_059": return "LOF_080"; //Exegol Patroller
    case "IC27_061": return "LOF_084"; //Knight of Ren
    case "IC27_064": return "JTL_088"; //Captain Phasma
    case "IC27_069": return "JTL_091"; //Apology Accepted
    case "IC27_100": return "JTL_135"; //Special Forces TIE Fighter
    case "IC27_111": return "LOF_139"; //Battle Fury
    case "IC27_133": return "SOR_166"; //Infiltrator's Skill
    case "IC27_150": return "SHD_193"; //Frozen in Carbonite
    case "IC27_033": return "SHD_040"; //Clan Wren Rescuer
    case "IC27_047": return "SHD_055"; //Moisture Farmer
    case "IC27_048": return "JTL_060"; //Desperate Commando
    case "IC27_118": return "LOF_145"; //Jedi Knight
    case "IC27_176": return "TWI_224"; //Breaking In
    case "IC27_044": return "TWI_052"; //Hello There
    case "IC27_156": return "LOF_194"; //J-Type Nubian Starship
    case "IC27_050": return "SEC_063"; //Rotunda Senate Guards
    case "IC27_051": return "JTL_069"; //Munificent Frigate
    case "IC27_054": return "SOR_073"; //Moment of Peace
    case "IC27_072": return "LOF_095"; //Lor San Tekka
    case "IC27_074": return "TWI_093"; //Advanced Recon Commando
    case "IC27_088": return "SEC_108"; //Senator's Aide
    case "IC27_091": return "ASH_129"; //Defenders of the Forest
    case "IC27_092": return "JTL_119"; //Resupply Carrier
    case "IC27_095": return "JTL_124"; //Tandem Assault
    case "IC27_096": return "SHD_130"; //Moment of Glory
    case "IC27_112": return "SEC_146"; //Rebellious Functionary
    case "IC27_125": return "TWI_153"; //Bold Resistance
    case "IC27_128": return "SEC_160"; //Reckless Rebel
    case "IC27_130": return "LOF_157"; //Cartel Interceptor
    case "IC27_132": return "JTL_168"; //Insurgent Saboteurs
    case "IC27_134": return "SHD_179"; //Desperate Attack
    case "IC27_135": return "SOR_170"; //Power Failure
    case "IC27_137": return "SHD_183"; //Kintan Intimidator
    case "IC27_139": return "SOR_177"; //Bib Fortuna
    case "IC27_140": return "TWI_180"; //Separatist Commando
    case "IC27_141": return "LOF_181"; //Banking Clan Shuttle
    case "IC27_152": return "SEC_199"; //Bravo Squadron Fighter
    case "IC27_170": return "TWI_207"; //B1 Security Team
    case "IC27_171": return "SOR_210"; //Swoop Racer
    case "IC27_174": return "ASH_228"; //Preparation
    case "IC27_177": return "LOF_223"; //Force Illusion
    case "IC27_183": return "SOR_232"; //AT-ST
    case "IC27_193": return "JTL_253"; //Coordinated Front
    case "P26_277": return "IC27_117"; //Hera Syndulla - Against the Odds
    // ── Promo / organized-play printings (P25, P26, C2x, J2x, prerelease sets, …) ────────────────────────
    // Promo sets are not in the card DB, so a promo id has no card, art or handler behind it: this alias is
    // the only thing that makes it playable. swudb sometimes sends the promo printing's id instead of the base
    // card's (P-34: P26_277 Hera loaded as a blank card). Generated 2026-11-18 from the official card API
    // (admin.starwarsunlimited.com) by matching Title + Subtitle + Cost + Aspects against the card DB, then
    // folded through the cases above to the earliest printing. Prerelease sets get both spellings: the
    // official code (JTLP) and the one swudb has sent (JTLW) — they share one numbering.
    case "ASHP_001": return "ASH_027"; //Enoch - Solemn Servant
    case "ASHP_002": return "ASH_029"; //Scorpenek Annihilator Droid
    case "ASHP_003": return "ASH_034"; //Wicket - Yub Nub!
    case "ASHP_004": return "ASH_044"; //Barriss Offee - Redeeming Herself
    case "ASHP_005": return "ASH_045"; //Reanimated Night Trooper
    case "ASHP_006": return "ASH_050"; //Morgan Elsbeth - Life Abandoned
    case "ASHP_007": return "ASH_064"; //The Armorer - Secrecy is Our Survival
    case "ASHP_008": return "ASH_067"; //Get Lost
    case "ASHP_009": return "ASH_073"; //Palace Chef Droid
    case "ASHP_010": return "ASH_109"; //T-6 Shuttle 1974 - With a Mentor's Dedication
    case "ASHP_011": return "ASH_119"; //Greef Karga - Introductions are in Order
    case "ASHP_012": return "ASH_121"; //Blurrg
    case "ASHP_013": return "ASH_131"; //Dinosaur Turtle
    case "ASHP_014": return "ASH_135"; //The Darksaber - Icon of Leadership
    case "ASHP_015": return "ASH_147"; //The Cyborg Mech - Mysterious Threat
    case "ASHP_016": return "ASH_189"; //Emperor's Messenger
    case "ASHP_017": return "ASH_192"; //Fennec Shand - The Galaxy Is Dangerous
    case "ASHP_018": return "ASH_203"; //Mando's N-1 Starfighter - Faster than a Fathier
    case "ASHP_019": return "ASH_225"; //Tatooine Sand Beast
    case "ASHP_020": return "ASH_237"; //Mouse Droid
    case "ASHP_021": return "ASH_027"; //Enoch - Solemn Servant
    case "ASHP_022": return "ASH_029"; //Scorpenek Annihilator Droid
    case "ASHP_023": return "ASH_034"; //Wicket - Yub Nub!
    case "ASHP_024": return "ASH_044"; //Barriss Offee - Redeeming Herself
    case "ASHP_025": return "ASH_045"; //Reanimated Night Trooper
    case "ASHP_026": return "ASH_050"; //Morgan Elsbeth - Life Abandoned
    case "ASHP_027": return "ASH_064"; //The Armorer - Secrecy is Our Survival
    case "ASHP_028": return "ASH_067"; //Get Lost
    case "ASHP_029": return "ASH_073"; //Palace Chef Droid
    case "ASHP_030": return "ASH_109"; //T-6 Shuttle 1974 - With a Mentor's Dedication
    case "ASHP_031": return "ASH_119"; //Greef Karga - Introductions are in Order
    case "ASHP_032": return "ASH_121"; //Blurrg
    case "ASHP_033": return "ASH_131"; //Dinosaur Turtle
    case "ASHP_034": return "ASH_135"; //The Darksaber - Icon of Leadership
    case "ASHP_035": return "ASH_147"; //The Cyborg Mech - Mysterious Threat
    case "ASHP_036": return "ASH_189"; //Emperor's Messenger
    case "ASHP_037": return "ASH_192"; //Fennec Shand - The Galaxy Is Dangerous
    case "ASHP_038": return "ASH_203"; //Mando's N-1 Starfighter - Faster than a Fathier
    case "ASHP_039": return "ASH_225"; //Tatooine Sand Beast
    case "ASHP_040": return "ASH_237"; //Mouse Droid
    case "ASHW_001": return "ASH_027"; //Enoch - Solemn Servant
    case "ASHW_002": return "ASH_029"; //Scorpenek Annihilator Droid
    case "ASHW_003": return "ASH_034"; //Wicket - Yub Nub!
    case "ASHW_004": return "ASH_044"; //Barriss Offee - Redeeming Herself
    case "ASHW_005": return "ASH_045"; //Reanimated Night Trooper
    case "ASHW_006": return "ASH_050"; //Morgan Elsbeth - Life Abandoned
    case "ASHW_007": return "ASH_064"; //The Armorer - Secrecy is Our Survival
    case "ASHW_008": return "ASH_067"; //Get Lost
    case "ASHW_009": return "ASH_073"; //Palace Chef Droid
    case "ASHW_010": return "ASH_109"; //T-6 Shuttle 1974 - With a Mentor's Dedication
    case "ASHW_011": return "ASH_119"; //Greef Karga - Introductions are in Order
    case "ASHW_012": return "ASH_121"; //Blurrg
    case "ASHW_013": return "ASH_131"; //Dinosaur Turtle
    case "ASHW_014": return "ASH_135"; //The Darksaber - Icon of Leadership
    case "ASHW_015": return "ASH_147"; //The Cyborg Mech - Mysterious Threat
    case "ASHW_016": return "ASH_189"; //Emperor's Messenger
    case "ASHW_017": return "ASH_192"; //Fennec Shand - The Galaxy Is Dangerous
    case "ASHW_018": return "ASH_203"; //Mando's N-1 Starfighter - Faster than a Fathier
    case "ASHW_019": return "ASH_225"; //Tatooine Sand Beast
    case "ASHW_020": return "ASH_237"; //Mouse Droid
    case "ASHW_021": return "ASH_027"; //Enoch - Solemn Servant
    case "ASHW_022": return "ASH_029"; //Scorpenek Annihilator Droid
    case "ASHW_023": return "ASH_034"; //Wicket - Yub Nub!
    case "ASHW_024": return "ASH_044"; //Barriss Offee - Redeeming Herself
    case "ASHW_025": return "ASH_045"; //Reanimated Night Trooper
    case "ASHW_026": return "ASH_050"; //Morgan Elsbeth - Life Abandoned
    case "ASHW_027": return "ASH_064"; //The Armorer - Secrecy is Our Survival
    case "ASHW_028": return "ASH_067"; //Get Lost
    case "ASHW_029": return "ASH_073"; //Palace Chef Droid
    case "ASHW_030": return "ASH_109"; //T-6 Shuttle 1974 - With a Mentor's Dedication
    case "ASHW_031": return "ASH_119"; //Greef Karga - Introductions are in Order
    case "ASHW_032": return "ASH_121"; //Blurrg
    case "ASHW_033": return "ASH_131"; //Dinosaur Turtle
    case "ASHW_034": return "ASH_135"; //The Darksaber - Icon of Leadership
    case "ASHW_035": return "ASH_147"; //The Cyborg Mech - Mysterious Threat
    case "ASHW_036": return "ASH_189"; //Emperor's Messenger
    case "ASHW_037": return "ASH_192"; //Fennec Shand - The Galaxy Is Dangerous
    case "ASHW_038": return "ASH_203"; //Mando's N-1 Starfighter - Faster than a Fathier
    case "ASHW_039": return "ASH_225"; //Tatooine Sand Beast
    case "ASHW_040": return "ASH_237"; //Mouse Droid
    case "C25_001": return "JTL_049"; //L3-37 - Get Out Of My Seat
    case "C25_002": return "JTL_145"; //BB-8 - Happy Beeps
    case "C25_003": return "JTL_245"; //R2-D2 - Artooooooooo!
    case "C25_004": return "LOF_191"; //BD-1 - Beep Boo Boo Bweep
    case "C25_005": return "SEC_093"; //C-3PO - Anything I Might Do?
    case "C25_006": return "SEC_147"; //Chopper - War Hero
    case "C26_001": return "LAW_214"; //Boba Fett - For a Price
    case "C26_002": return "LAW_052"; //The Mandalorian - Let's See the Puck
    case "C26_003": return "HMW_134"; //Zam Wesell - Not What She Seems
    case "C26_004": return "ASH_226"; //Qi'ra - Master of Teräs Käsi
    case "C26_005": return "LAW_032"; //Cad Bane - Now It's My Turn
    case "C26_006": return "ASH_042"; //Jabba the Hutt - Eminence of Tatooine
    case "CST_002": return "ASH_059"; //Leia Organa - Vigilant for Danger
    case "CST_003": return "ASH_158"; //Han Solo - It'll Work
    case "G25_002": return "LOF_039"; //Darth Sidious - The Phantom Menace
    case "G25_003": return "LOF_096"; //Obi-Wan Kenobi - Protective Padawan
    case "HMWP_001": return "HMW_047"; //Eravana - Hauling Rathtars
    case "HMWP_002": return "HMW_064"; //Scorch - Imperial Commando
    case "HMWP_003": return "HMW_076"; //Yanna - Sanctuary Elder
    case "HMWP_004": return "HMW_083"; //Batcher - Loyal Hound
    case "HMWP_005": return "HMW_084"; //Gunga City Guard
    case "HMWP_006": return "HMW_099"; //Always a Bigger Fish
    case "HMWP_007": return "HMW_111"; //Invasion Lander
    case "HMWP_008": return "HMW_122"; //Boga - Loyal Varactyl
    case "HMWP_009": return "HMW_134"; //Zam Wesell - Not What She Seems
    case "HMWP_010": return "HMW_156"; //Arena Acklay - Screaming Predator
    case "HMWP_011": return "HMW_159"; //General Grievous - Scourge of Dathomir
    case "HMWP_012": return "HMW_162"; //Teebo - Striped Hunter
    case "HMWP_013": return "HMW_164"; //Chief Chirpa - Defiant Elder
    case "HMWP_014": return "HMW_177"; //Adamant Ewoks
    case "HMWP_015": return "HMW_202"; //Inferno Squad - We Can Grieve Later
    case "HMWP_016": return "HMW_213"; //Tribubble Bongo Sub
    case "HMWP_017": return "HMW_217"; //Don't Touch Anything
    case "HMWP_018": return "HMW_218"; //New Tactics
    case "HMWP_019": return "HMW_224"; //Tusken Raider
    case "HMWP_020": return "HMW_245"; //Trandoshan Collaborator
    case "HMWP_021": return "HMW_047"; //Eravana - Hauling Rathtars
    case "HMWP_022": return "HMW_064"; //Scorch - Imperial Commando
    case "HMWP_023": return "HMW_076"; //Yanna - Sanctuary Elder
    case "HMWP_024": return "HMW_083"; //Batcher - Loyal Hound
    case "HMWP_025": return "HMW_084"; //Gunga City Guard
    case "HMWP_026": return "HMW_099"; //Always a Bigger Fish
    case "HMWP_027": return "HMW_111"; //Invasion Lander
    case "HMWP_028": return "HMW_122"; //Boga - Loyal Varactyl
    case "HMWP_029": return "HMW_134"; //Zam Wesell - Not What She Seems
    case "HMWP_030": return "HMW_156"; //Arena Acklay - Screaming Predator
    case "HMWP_031": return "HMW_159"; //General Grievous - Scourge of Dathomir
    case "HMWP_032": return "HMW_162"; //Teebo - Striped Hunter
    case "HMWP_033": return "HMW_164"; //Chief Chirpa - Defiant Elder
    case "HMWP_034": return "HMW_177"; //Adamant Ewoks
    case "HMWP_035": return "HMW_202"; //Inferno Squad - We Can Grieve Later
    case "HMWP_036": return "HMW_213"; //Tribubble Bongo Sub
    case "HMWP_037": return "HMW_217"; //Don't Touch Anything
    case "HMWP_038": return "HMW_218"; //New Tactics
    case "HMWP_039": return "HMW_224"; //Tusken Raider
    case "HMWP_040": return "HMW_245"; //Trandoshan Collaborator
    case "HMWW_001": return "HMW_047"; //Eravana - Hauling Rathtars
    case "HMWW_002": return "HMW_064"; //Scorch - Imperial Commando
    case "HMWW_003": return "HMW_076"; //Yanna - Sanctuary Elder
    case "HMWW_004": return "HMW_083"; //Batcher - Loyal Hound
    case "HMWW_005": return "HMW_084"; //Gunga City Guard
    case "HMWW_006": return "HMW_099"; //Always a Bigger Fish
    case "HMWW_007": return "HMW_111"; //Invasion Lander
    case "HMWW_008": return "HMW_122"; //Boga - Loyal Varactyl
    case "HMWW_009": return "HMW_134"; //Zam Wesell - Not What She Seems
    case "HMWW_010": return "HMW_156"; //Arena Acklay - Screaming Predator
    case "HMWW_011": return "HMW_159"; //General Grievous - Scourge of Dathomir
    case "HMWW_012": return "HMW_162"; //Teebo - Striped Hunter
    case "HMWW_013": return "HMW_164"; //Chief Chirpa - Defiant Elder
    case "HMWW_014": return "HMW_177"; //Adamant Ewoks
    case "HMWW_015": return "HMW_202"; //Inferno Squad - We Can Grieve Later
    case "HMWW_016": return "HMW_213"; //Tribubble Bongo Sub
    case "HMWW_017": return "HMW_217"; //Don't Touch Anything
    case "HMWW_018": return "HMW_218"; //New Tactics
    case "HMWW_019": return "HMW_224"; //Tusken Raider
    case "HMWW_020": return "HMW_245"; //Trandoshan Collaborator
    case "HMWW_021": return "HMW_047"; //Eravana - Hauling Rathtars
    case "HMWW_022": return "HMW_064"; //Scorch - Imperial Commando
    case "HMWW_023": return "HMW_076"; //Yanna - Sanctuary Elder
    case "HMWW_024": return "HMW_083"; //Batcher - Loyal Hound
    case "HMWW_025": return "HMW_084"; //Gunga City Guard
    case "HMWW_026": return "HMW_099"; //Always a Bigger Fish
    case "HMWW_027": return "HMW_111"; //Invasion Lander
    case "HMWW_028": return "HMW_122"; //Boga - Loyal Varactyl
    case "HMWW_029": return "HMW_134"; //Zam Wesell - Not What She Seems
    case "HMWW_030": return "HMW_156"; //Arena Acklay - Screaming Predator
    case "HMWW_031": return "HMW_159"; //General Grievous - Scourge of Dathomir
    case "HMWW_032": return "HMW_162"; //Teebo - Striped Hunter
    case "HMWW_033": return "HMW_164"; //Chief Chirpa - Defiant Elder
    case "HMWW_034": return "HMW_177"; //Adamant Ewoks
    case "HMWW_035": return "HMW_202"; //Inferno Squad - We Can Grieve Later
    case "HMWW_036": return "HMW_213"; //Tribubble Bongo Sub
    case "HMWW_037": return "HMW_217"; //Don't Touch Anything
    case "HMWW_038": return "HMW_218"; //New Tactics
    case "HMWW_039": return "HMW_224"; //Tusken Raider
    case "HMWW_040": return "HMW_245"; //Trandoshan Collaborator
    case "J25_010": return "LOF_063"; //Oggdo Bogdo - Bogano Brute
    case "J25_011": return "LOF_101"; //Yoda - My Ally is the Force
    case "J25_012": return "LOF_130"; //HK-47 - Exclamation: Die, Meatbag!
    case "J25_013": return "LOF_148"; //Rey - With Palpatine's Power
    case "J25_014": return "LOF_249"; //Luke Skywalker - A Hero's Beginning
    case "J25_015": return "SEC_035"; //Darth Sion - Lord of Pain
    case "J25_016": return "SEC_101"; //Queen Amidala - Championing Her People
    case "J25_017": return "SEC_148"; //Karis Nemik - Freedom is a Pure Idea
    case "J25_018": return "SEC_187"; //General Grievous - Scuttling to Safety
    case "J25_019": return "SEC_209"; //The Mandalorian - Cleaning Up Nevarro
    case "JTLP_001": return "JTL_032"; //Director Krennic - On the Verge of Greatness
    case "JTLP_002": return "JTL_033"; //Onyx Squadron Brute
    case "JTLP_003": return "JTL_045"; //Hera Syndulla - We've Lost Enough
    case "JTLP_004": return "JTL_048"; //Cassian Andor - Threading the Eye
    case "JTLP_005": return "JTL_097"; //Leia Organa - Pilots, To Your Stations
    case "JTLP_006": return "JTL_100"; //Poe Dameron - One Hell of a Pilot
    case "JTLP_007": return "JTL_103"; //Chewbacca - Faithful First Mate
    case "JTLP_008": return "JTL_134"; //General Hux - No Terms, No Surrender
    case "JTLP_009": return "JTL_141"; //IG-88 - Murderous Phlutdroid
    case "JTLP_010": return "JTL_145"; //BB-8 - Happy Beeps
    case "JTLP_011": return "JTL_149"; //Red Squadron Y-Wing
    case "JTLP_012": return "JTL_151"; //Red Five - Running the Trench
    case "JTLP_013": return "JTL_161"; //Captain Tarkin - Full Forward Assault
    case "JTLP_014": return "JTL_176"; //Shoot Down
    case "JTLP_015": return "JTL_189"; //Boba Fett - Feared Bounty Hunter
    case "JTLP_016": return "JTL_197"; //Anakin Skywalker - I'll Try Spinning
    case "JTLP_017": return "JTL_203"; //Han Solo - Has His Moments
    case "JTLP_018": return "JTL_240"; //Fett's Firespray - Feared Silhouette
    case "JTLP_019": return "JTL_249"; //Millennium Falcon - Get Out And Push
    case "JTLP_020": return "JTL_256"; //Swarming Vulture Droid
    case "JTLP_021": return "JTL_032"; //Director Krennic - On the Verge of Greatness
    case "JTLP_022": return "JTL_033"; //Onyx Squadron Brute
    case "JTLP_023": return "JTL_045"; //Hera Syndulla - We've Lost Enough
    case "JTLP_024": return "JTL_048"; //Cassian Andor - Threading the Eye
    case "JTLP_025": return "JTL_097"; //Leia Organa - Pilots, To Your Stations
    case "JTLP_026": return "JTL_100"; //Poe Dameron - One Hell of a Pilot
    case "JTLP_027": return "JTL_103"; //Chewbacca - Faithful First Mate
    case "JTLP_028": return "JTL_134"; //General Hux - No Terms, No Surrender
    case "JTLP_029": return "JTL_141"; //IG-88 - Murderous Phlutdroid
    case "JTLP_030": return "JTL_145"; //BB-8 - Happy Beeps
    case "JTLP_031": return "JTL_149"; //Red Squadron Y-Wing
    case "JTLP_032": return "JTL_151"; //Red Five - Running the Trench
    case "JTLP_033": return "JTL_161"; //Captain Tarkin - Full Forward Assault
    case "JTLP_034": return "JTL_176"; //Shoot Down
    case "JTLP_035": return "JTL_189"; //Boba Fett - Feared Bounty Hunter
    case "JTLP_036": return "JTL_197"; //Anakin Skywalker - I'll Try Spinning
    case "JTLP_037": return "JTL_203"; //Han Solo - Has His Moments
    case "JTLP_038": return "JTL_240"; //Fett's Firespray - Feared Silhouette
    case "JTLP_039": return "JTL_249"; //Millennium Falcon - Get Out And Push
    case "JTLP_040": return "JTL_256"; //Swarming Vulture Droid
    case "JTLW_021": return "JTL_032"; //Director Krennic - On the Verge of Greatness
    case "JTLW_022": return "JTL_033"; //Onyx Squadron Brute
    case "JTLW_023": return "JTL_045"; //Hera Syndulla - We've Lost Enough
    case "JTLW_024": return "JTL_048"; //Cassian Andor - Threading the Eye
    case "JTLW_025": return "JTL_097"; //Leia Organa - Pilots, To Your Stations
    case "JTLW_026": return "JTL_100"; //Poe Dameron - One Hell of a Pilot
    case "JTLW_027": return "JTL_103"; //Chewbacca - Faithful First Mate
    case "JTLW_028": return "JTL_134"; //General Hux - No Terms, No Surrender
    case "JTLW_029": return "JTL_141"; //IG-88 - Murderous Phlutdroid
    case "JTLW_030": return "JTL_145"; //BB-8 - Happy Beeps
    case "JTLW_031": return "JTL_149"; //Red Squadron Y-Wing
    case "JTLW_032": return "JTL_151"; //Red Five - Running the Trench
    case "JTLW_033": return "JTL_161"; //Captain Tarkin - Full Forward Assault
    case "JTLW_034": return "JTL_176"; //Shoot Down
    case "JTLW_035": return "JTL_189"; //Boba Fett - Feared Bounty Hunter
    case "JTLW_036": return "JTL_197"; //Anakin Skywalker - I'll Try Spinning
    case "JTLW_037": return "JTL_203"; //Han Solo - Has His Moments
    case "JTLW_038": return "JTL_240"; //Fett's Firespray - Feared Silhouette
    case "JTLW_039": return "JTL_249"; //Millennium Falcon - Get Out And Push
    case "JTLW_040": return "JTL_256"; //Swarming Vulture Droid
    case "LAWP_001": return "LAW_033"; //Hound's Tooth - Hunters' Approach
    case "LAWP_002": return "LAW_042"; //IG-88 - Programmed to Kill
    case "LAWP_003": return "LAW_045"; //Zeb Orellios - Spectre Four
    case "LAWP_004": return "LAW_055"; //Chopper - Spectre Three
    case "LAWP_005": return "LAW_068"; //Millennium Falcon - Dodging Patrols
    case "LAWP_006": return "LAW_074"; //Maz Kanata - Where's My Boyfriend?
    case "LAWP_007": return "LAW_075"; //Interrogation Droid
    case "LAWP_008": return "LAW_079"; //K-2SO - Locking the Vault
    case "LAWP_009": return "LAW_087"; //Jango Fett - Wily Mercenary
    case "LAWP_010": return "LAW_089"; //Kanan Jarrus - Spectre One
    case "LAWP_011": return "LAW_118"; //Droid Laser Turret
    case "LAWP_012": return "LAW_131"; //Incapacitate
    case "LAWP_013": return "LAW_152"; //C-3PO - Translation Protocol
    case "LAWP_014": return "LAW_168"; //Haymaker
    case "LAWP_015": return "LAW_172"; //Storm Raider
    case "LAWP_016": return "LAW_203"; //Daring Delve
    case "LAWP_017": return "LAW_216"; //Jabba's Rancor - Snack Time!
    case "LAWP_018": return "LAW_220"; //Wookiee Guerilla
    case "LAWP_019": return "LAW_226"; //Secret Battle of Pretend
    case "LAWP_020": return "LAW_247"; //Backed by the Hutts
    case "LAWP_021": return "LAW_033"; //Hound's Tooth - Hunters' Approach
    case "LAWP_022": return "LAW_042"; //IG-88 - Programmed to Kill
    case "LAWP_023": return "LAW_045"; //Zeb Orellios - Spectre Four
    case "LAWP_024": return "LAW_055"; //Chopper - Spectre Three
    case "LAWP_025": return "LAW_068"; //Millennium Falcon - Dodging Patrols
    case "LAWP_026": return "LAW_074"; //Maz Kanata - Where's My Boyfriend?
    case "LAWP_027": return "LAW_075"; //Interrogation Droid
    case "LAWP_028": return "LAW_079"; //K-2SO - Locking the Vault
    case "LAWP_029": return "LAW_087"; //Jango Fett - Wily Mercenary
    case "LAWP_030": return "LAW_089"; //Kanan Jarrus - Spectre One
    case "LAWP_031": return "LAW_118"; //Droid Laser Turret
    case "LAWP_032": return "LAW_131"; //Incapacitate
    case "LAWP_033": return "LAW_152"; //C-3PO - Translation Protocol
    case "LAWP_034": return "LAW_168"; //Haymaker
    case "LAWP_035": return "LAW_172"; //Storm Raider
    case "LAWP_036": return "LAW_203"; //Daring Delve
    case "LAWP_037": return "LAW_216"; //Jabba's Rancor - Snack Time!
    case "LAWP_038": return "LAW_220"; //Wookiee Guerilla
    case "LAWP_039": return "LAW_226"; //Secret Battle of Pretend
    case "LAWP_040": return "LAW_247"; //Backed by the Hutts
    case "LAWW_001": return "LAW_033"; //Hound's Tooth - Hunters' Approach
    case "LAWW_002": return "LAW_042"; //IG-88 - Programmed to Kill
    case "LAWW_003": return "LAW_045"; //Zeb Orellios - Spectre Four
    case "LAWW_004": return "LAW_055"; //Chopper - Spectre Three
    case "LAWW_005": return "LAW_068"; //Millennium Falcon - Dodging Patrols
    case "LAWW_006": return "LAW_074"; //Maz Kanata - Where's My Boyfriend?
    case "LAWW_007": return "LAW_075"; //Interrogation Droid
    case "LAWW_008": return "LAW_079"; //K-2SO - Locking the Vault
    case "LAWW_009": return "LAW_087"; //Jango Fett - Wily Mercenary
    case "LAWW_010": return "LAW_089"; //Kanan Jarrus - Spectre One
    case "LAWW_011": return "LAW_118"; //Droid Laser Turret
    case "LAWW_012": return "LAW_131"; //Incapacitate
    case "LAWW_013": return "LAW_152"; //C-3PO - Translation Protocol
    case "LAWW_014": return "LAW_168"; //Haymaker
    case "LAWW_015": return "LAW_172"; //Storm Raider
    case "LAWW_016": return "LAW_203"; //Daring Delve
    case "LAWW_017": return "LAW_216"; //Jabba's Rancor - Snack Time!
    case "LAWW_018": return "LAW_220"; //Wookiee Guerilla
    case "LAWW_019": return "LAW_226"; //Secret Battle of Pretend
    case "LAWW_020": return "LAW_247"; //Backed by the Hutts
    case "LAWW_021": return "LAW_033"; //Hound's Tooth - Hunters' Approach
    case "LAWW_022": return "LAW_042"; //IG-88 - Programmed to Kill
    case "LAWW_023": return "LAW_045"; //Zeb Orellios - Spectre Four
    case "LAWW_024": return "LAW_055"; //Chopper - Spectre Three
    case "LAWW_025": return "LAW_068"; //Millennium Falcon - Dodging Patrols
    case "LAWW_026": return "LAW_074"; //Maz Kanata - Where's My Boyfriend?
    case "LAWW_027": return "LAW_075"; //Interrogation Droid
    case "LAWW_028": return "LAW_079"; //K-2SO - Locking the Vault
    case "LAWW_029": return "LAW_087"; //Jango Fett - Wily Mercenary
    case "LAWW_030": return "LAW_089"; //Kanan Jarrus - Spectre One
    case "LAWW_031": return "LAW_118"; //Droid Laser Turret
    case "LAWW_032": return "LAW_131"; //Incapacitate
    case "LAWW_033": return "LAW_152"; //C-3PO - Translation Protocol
    case "LAWW_034": return "LAW_168"; //Haymaker
    case "LAWW_035": return "LAW_172"; //Storm Raider
    case "LAWW_036": return "LAW_203"; //Daring Delve
    case "LAWW_037": return "LAW_216"; //Jabba's Rancor - Snack Time!
    case "LAWW_038": return "LAW_220"; //Wookiee Guerilla
    case "LAWW_039": return "LAW_226"; //Secret Battle of Pretend
    case "LAWW_040": return "LAW_247"; //Backed by the Hutts
    case "LOFP_001": return "LOF_068"; //Luthen Rael - Masquerading Antiquarian
    case "LOFP_002": return "LOF_069"; //Graceful Purrgil
    case "LOFP_003": return "LOF_077"; //Crushing Blow
    case "LOFP_004": return "LOF_093"; //Gungi - Finding Himself
    case "LOFP_005": return "LOF_094"; //Jedi Consular
    case "LOFP_006": return "LOF_101"; //Yoda - My Ally is the Force
    case "LOFP_007": return "LOF_108"; //Malakili - Loving Rancor Keeper
    case "LOFP_008": return "LOF_129"; //Acolyte of the Beyond
    case "LOFP_009": return "LOF_148"; //Rey - With Palpatine's Power
    case "LOFP_010": return "LOF_149"; //Mace Windu - Leaping into Action
    case "LOFP_011": return "LOF_156"; //Infused Brawler
    case "LOFP_012": return "LOF_175"; //Do or Do Not
    case "LOFP_013": return "LOF_183"; //Shin Hati - Overeager Apprentice
    case "LOFP_014": return "LOF_185"; //Baylan Skoll - Enigmatic Master
    case "LOFP_015": return "LOF_191"; //BD-1 - Beep Boo Boo Bweep
    case "LOFP_016": return "LOF_194"; //J-Type Nubian Starship
    case "LOFP_017": return "LOF_202"; //Mind Trick
    case "LOFP_018": return "LOF_207"; //Loth-Cat
    case "LOFP_019": return "LOF_235"; //HK-87 Assassin Droid
    case "LOFP_020": return "LOF_240"; //Flight of the Inquisitor
    case "LOFP_021": return "LOF_068"; //Luthen Rael - Masquerading Antiquarian
    case "LOFP_022": return "LOF_069"; //Graceful Purrgil
    case "LOFP_023": return "LOF_077"; //Crushing Blow
    case "LOFP_024": return "LOF_093"; //Gungi - Finding Himself
    case "LOFP_025": return "LOF_094"; //Jedi Consular
    case "LOFP_026": return "LOF_101"; //Yoda - My Ally is the Force
    case "LOFP_027": return "LOF_108"; //Malakili - Loving Rancor Keeper
    case "LOFP_028": return "LOF_129"; //Acolyte of the Beyond
    case "LOFP_029": return "LOF_148"; //Rey - With Palpatine's Power
    case "LOFP_030": return "LOF_149"; //Mace Windu - Leaping into Action
    case "LOFP_031": return "LOF_156"; //Infused Brawler
    case "LOFP_032": return "LOF_175"; //Do or Do Not
    case "LOFP_033": return "LOF_183"; //Shin Hati - Overeager Apprentice
    case "LOFP_034": return "LOF_185"; //Baylan Skoll - Enigmatic Master
    case "LOFP_035": return "LOF_191"; //BD-1 - Beep Boo Boo Bweep
    case "LOFP_036": return "LOF_194"; //J-Type Nubian Starship
    case "LOFP_037": return "LOF_202"; //Mind Trick
    case "LOFP_038": return "LOF_207"; //Loth-Cat
    case "LOFP_039": return "LOF_235"; //HK-87 Assassin Droid
    case "LOFP_040": return "LOF_240"; //Flight of the Inquisitor
    case "LOFW_001": return "LOF_068"; //Luthen Rael - Masquerading Antiquarian
    case "LOFW_002": return "LOF_069"; //Graceful Purrgil
    case "LOFW_003": return "LOF_077"; //Crushing Blow
    case "LOFW_004": return "LOF_093"; //Gungi - Finding Himself
    case "LOFW_005": return "LOF_094"; //Jedi Consular
    case "LOFW_006": return "LOF_101"; //Yoda - My Ally is the Force
    case "LOFW_007": return "LOF_108"; //Malakili - Loving Rancor Keeper
    case "LOFW_008": return "LOF_129"; //Acolyte of the Beyond
    case "LOFW_009": return "LOF_148"; //Rey - With Palpatine's Power
    case "LOFW_010": return "LOF_149"; //Mace Windu - Leaping into Action
    case "LOFW_011": return "LOF_156"; //Infused Brawler
    case "LOFW_012": return "LOF_175"; //Do or Do Not
    case "LOFW_013": return "LOF_183"; //Shin Hati - Overeager Apprentice
    case "LOFW_014": return "LOF_185"; //Baylan Skoll - Enigmatic Master
    case "LOFW_015": return "LOF_191"; //BD-1 - Beep Boo Boo Bweep
    case "LOFW_016": return "LOF_194"; //J-Type Nubian Starship
    case "LOFW_017": return "LOF_202"; //Mind Trick
    case "LOFW_018": return "LOF_207"; //Loth-Cat
    case "LOFW_019": return "LOF_235"; //HK-87 Assassin Droid
    case "LOFW_020": return "LOF_240"; //Flight of the Inquisitor
    case "LOFW_021": return "LOF_068"; //Luthen Rael - Masquerading Antiquarian
    case "LOFW_022": return "LOF_069"; //Graceful Purrgil
    case "LOFW_023": return "LOF_077"; //Crushing Blow
    case "LOFW_024": return "LOF_093"; //Gungi - Finding Himself
    case "LOFW_025": return "LOF_094"; //Jedi Consular
    case "LOFW_026": return "LOF_101"; //Yoda - My Ally is the Force
    case "LOFW_027": return "LOF_108"; //Malakili - Loving Rancor Keeper
    case "LOFW_028": return "LOF_129"; //Acolyte of the Beyond
    case "LOFW_029": return "LOF_148"; //Rey - With Palpatine's Power
    case "LOFW_030": return "LOF_149"; //Mace Windu - Leaping into Action
    case "LOFW_031": return "LOF_156"; //Infused Brawler
    case "LOFW_032": return "LOF_175"; //Do or Do Not
    case "LOFW_033": return "LOF_183"; //Shin Hati - Overeager Apprentice
    case "LOFW_034": return "LOF_185"; //Baylan Skoll - Enigmatic Master
    case "LOFW_035": return "LOF_191"; //BD-1 - Beep Boo Boo Bweep
    case "LOFW_036": return "LOF_194"; //J-Type Nubian Starship
    case "LOFW_037": return "LOF_202"; //Mind Trick
    case "LOFW_038": return "LOF_207"; //Loth-Cat
    case "LOFW_039": return "LOF_235"; //HK-87 Assassin Droid
    case "LOFW_040": return "LOF_240"; //Flight of the Inquisitor
    case "MV26_001": return "ASH_018"; //Grogu - Charming Companion
    case "MV26_002": return "ASH_172"; //Razor Crest - Outfitted Armament
    case "P25_013": return "LOF_051"; //Jedi Holocron
    case "P25_014": return "LOF_051"; //Jedi Holocron
    case "P25_015": return "LOF_132"; //Grand Inquisitor - You're Right to Be Afraid
    case "P25_016": return "LOF_132"; //Grand Inquisitor - You're Right to Be Afraid
    case "P25_017": return "LOF_132"; //Grand Inquisitor - You're Right to Be Afraid
    case "P25_018": return "LOF_132"; //Grand Inquisitor - You're Right to Be Afraid
    case "P25_019": return "LOF_238"; //Darth Revan's Lightsabers
    case "P25_020": return "LOF_238"; //Darth Revan's Lightsabers
    case "P25_021": return "LOF_234"; //Darth Malak - Covetous Apprentice
    case "P25_022": return "LOF_234"; //Darth Malak - Covetous Apprentice
    case "P25_023": return "LOF_234"; //Darth Malak - Covetous Apprentice
    case "P25_024": return "LOF_234"; //Darth Malak - Covetous Apprentice
    case "P25_025": return "SEC_088"; //First Light - Threatening Elegance
    case "P25_026": return "SEC_088"; //First Light - Threatening Elegance
    case "P25_027": return "SEC_101"; //Queen Amidala - Championing Her People
    case "P25_028": return "SEC_101"; //Queen Amidala - Championing Her People
    case "P25_029": return "SEC_101"; //Queen Amidala - Championing Her People
    case "P25_030": return "SEC_101"; //Queen Amidala - Championing Her People
    case "P25_031": return "SEC_046"; //Galen Erso - You'll Never Win
    case "P25_032": return "SEC_046"; //Galen Erso - You'll Never Win
    case "P25_033": return "SEC_193"; //Grand Admiral Thrawn - Grand Schemer
    case "P25_034": return "SEC_193"; //Grand Admiral Thrawn - Grand Schemer
    case "P25_035": return "SEC_193"; //Grand Admiral Thrawn - Grand Schemer
    case "P25_036": return "SEC_193"; //Grand Admiral Thrawn - Grand Schemer
    case "P25_045": return "SOR_049"; //Obi-Wan Kenobi - Following Fate
    case "P25_047": return "SHD_141"; //Kylo Ren - Killing the Past
    case "P25_048": return "SHD_141"; //Kylo Ren - Killing the Past
    case "P25_049": return "SHD_141"; //Kylo Ren - Killing the Past
    case "P25_050": return "SHD_141"; //Kylo Ren - Killing the Past
    case "P25_051": return "SHD_141"; //Kylo Ren - Killing the Past
    case "P25_058": return "SOR_135"; //Emperor Palpatine - Master of the Dark Side
    case "P25_060": return "SHD_046"; //Rey - Keeping the Past
    case "P25_061": return "SHD_046"; //Rey - Keeping the Past
    case "P25_062": return "SHD_046"; //Rey - Keeping the Past
    case "P25_063": return "SHD_046"; //Rey - Keeping the Past
    case "P25_064": return "SHD_046"; //Rey - Keeping the Past
    case "P25_065": return "JTL_032"; //Director Krennic - On the Verge of Greatness
    case "P25_066": return "SOR_043"; //Superlaser Blast
    case "P25_067": return "SHD_044"; //Razor Crest - Reliable Gunship
    case "P25_068": return "JTL_103"; //Chewbacca - Faithful First Mate
    case "P25_069": return "LOF_149"; //Mace Windu - Leaping into Action
    case "P25_070": return "LOF_183"; //Shin Hati - Overeager Apprentice
    case "P25_071": return "LOF_148"; //Rey - With Palpatine's Power
    case "P25_072": return "LOF_229"; //Kylo Ren - I Know Your Story
    case "P25_073": return "SOR_005"; //Luke Skywalker - Faithful Friend
    case "P25_074": return "SOR_010"; //Darth Vader - Dark Lord of the Sith
    case "P25_075": return "LOF_037"; //Darth Vader - Twilight of the Apprentice
    case "P25_076": return "SHD_037"; //Supreme Leader Snoke - Shadow Ruler
    case "P25_077": return "LOF_046"; //Ezra Bridger - Attuned With Life
    case "P25_078": return "JTL_089"; //The Invisible Hand - Crawling With Vultures
    case "P25_079": return "LOF_096"; //Obi-Wan Kenobi - Protective Padawan
    case "P25_080": return "LOF_098"; //Leia Organa - Extraordinary
    case "P25_081": return "LOF_132"; //Grand Inquisitor - You're Right to Be Afraid
    case "P25_082": return "JTL_189"; //Boba Fett - Feared Bounty Hunter
    case "P25_083": return "TWI_194"; //Ahsoka Tano - Always Ready For Trouble
    case "P25_084": return "LOF_165"; //Asajj Ventress - Harden Your Heart
    case "P25_085": return "LOF_163"; //Quinlan Vos - Dark Disciple
    case "P25_086": return "JTL_053"; //The Ghost - Heart of the Family
    case "P25_087": return "LOF_070"; //Anakin Skywalker - Champion of Mortis
    case "P25_088": return "JTL_090"; //Executor - Might of the Empire
    case "P25_089": return "JTL_094"; //Luke Skywalker - You Still With Me?
    case "P25_090": return "JTL_100"; //Poe Dameron - One Hell of a Pilot
    case "P25_091": return "TWI_135"; //Darth Maul - Revenge At Last
    case "P25_092": return "SOR_193"; //Millennium Falcon - Piece of Junk
    case "P25_093": return "JTL_203"; //Han Solo - Has His Moments
    case "P25_094": return "LOF_200"; //Qui-Gon Jinn - The Negotiations Will Be Short
    case "P25_095": return "LOF_039"; //Darth Sidious - The Phantom Menace
    case "P25_096": return "LOF_231"; //Darth Tyranus - Servant of Sidious
    case "P25_097": return "JTL_020"; //Shield Generator Complex
    case "P25_098": return "JTL_023"; //Theed Palace
    case "P25_099": return "JTL_026"; //Massassi Temple
    case "P25_100": return "JTL_030"; //Mos Eisley
    case "P25_101": return "SHD_049"; //The Mandalorian - Wherever I Go, He Goes
    case "P25_102": return "SHD_049"; //The Mandalorian - Wherever I Go, He Goes
    case "P25_103": return "SOR_087"; //Darth Vader - Commanding the First Legion
    case "P25_104": return "LOF_040"; //Kylo Ren's Lightsaber
    case "P25_105": return "LOF_053"; //Heirloom Lightsaber
    case "P25_106": return "LOF_090"; //Inquisitor's Lightsaber
    case "P25_107": return "LOF_102"; //Yoda's Lightsaber
    case "P25_108": return "LOF_140"; //Darth Maul's Lightsaber
    case "P25_109": return "LOF_151"; //Knight's Saber
    case "P25_110": return "LOF_187"; //Corrupted Saber
    case "P25_111": return "LOF_201"; //Qui-Gon Jinn's Lightsaber
    case "P25_112": return "LOF_261"; //Constructed Lightsaber
    case "P25_113": return "SOR_087"; //Darth Vader - Commanding the First Legion
    case "P25_114": return "SOR_087"; //Darth Vader - Commanding the First Legion
    case "P25_115": return "SOR_087"; //Darth Vader - Commanding the First Legion
    case "P25_116": return "SOR_087"; //Darth Vader - Commanding the First Legion
    case "P25_117": return "SOR_087"; //Darth Vader - Commanding the First Legion
    case "P25_118": return "LOF_231"; //Darth Tyranus - Servant of Sidious
    case "P25_119": return "SOR_058"; //Vigilance
    case "P25_120": return "LOF_062"; //Axe Woves - Accomplished Warrior
    case "P25_121": return "LOF_093"; //Gungi - Finding Himself
    case "P25_122": return "SOR_107"; //Command
    case "P25_123": return "LOF_130"; //HK-47 - Exclamation: Die, Meatbag!
    case "P25_124": return "JTL_140"; //IG-2000 - Assassin's Aggressor
    case "P25_125": return "SHD_154"; //Wrecker - Boom!
    case "P25_126": return "SOR_155"; //Aggression
    case "P25_127": return "JTL_181"; //Planetary Bombardment
    case "P25_128": return "LOF_179"; //Aurra Sing - Patient and Deadly
    case "P25_129": return "LOF_195"; //Vernestra Rwoh - Precocious Knight
    case "P25_130": return "SOR_203"; //Cunning
    case "P25_131": return "SOR_058"; //Vigilance
    case "P25_132": return "LOF_062"; //Axe Woves - Accomplished Warrior
    case "P25_133": return "LOF_093"; //Gungi - Finding Himself
    case "P25_134": return "SOR_107"; //Command
    case "P25_135": return "LOF_130"; //HK-47 - Exclamation: Die, Meatbag!
    case "P25_136": return "JTL_140"; //IG-2000 - Assassin's Aggressor
    case "P25_137": return "SHD_154"; //Wrecker - Boom!
    case "P25_138": return "SOR_155"; //Aggression
    case "P25_139": return "JTL_181"; //Planetary Bombardment
    case "P25_140": return "LOF_179"; //Aurra Sing - Patient and Deadly
    case "P25_141": return "LOF_195"; //Vernestra Rwoh - Precocious Knight
    case "P25_142": return "SOR_203"; //Cunning
    case "P25_143": return "LOF_045"; //Yaddle - A Chance To Make Things Right
    case "P25_144": return "LOF_045"; //Yaddle - A Chance To Make Things Right
    case "P25_145": return "LOF_070"; //Anakin Skywalker - Champion of Mortis
    case "P25_146": return "LOF_070"; //Anakin Skywalker - Champion of Mortis
    case "P25_147": return "LOF_070"; //Anakin Skywalker - Champion of Mortis
    case "P25_148": return "LOF_070"; //Anakin Skywalker - Champion of Mortis
    case "P25_149": return "LOF_070"; //Anakin Skywalker - Champion of Mortis
    case "P25_151": return "LOF_031"; //Karis - We Don't Like Strangers
    case "P25_152": return "LOF_037"; //Darth Vader - Twilight of the Apprentice
    case "P25_153": return "LOF_079"; //Shatterpoint
    case "P25_154": return "JTL_085"; //Victor Leader - Leading from the Front
    case "P25_155": return "SHD_090"; //Maul - Shadow Collective Visionary
    case "P25_156": return "TWI_121"; //General's Blade
    case "P25_157": return "JTL_147"; //Black One - Straight At Them
    case "P25_158": return "SHD_153"; //Poe Dameron - Quick to Improvise
    case "P25_159": return "JTL_154"; //Profundity - We Fight!
    case "P25_160": return "JTL_201"; //Ahsoka Tano - Chasing Whispers
    case "P25_161": return "LOF_031"; //Karis - We Don't Like Strangers
    case "P25_162": return "LOF_037"; //Darth Vader - Twilight of the Apprentice
    case "P25_163": return "LOF_079"; //Shatterpoint
    case "P25_164": return "JTL_085"; //Victor Leader - Leading from the Front
    case "P25_165": return "SHD_090"; //Maul - Shadow Collective Visionary
    case "P25_166": return "TWI_121"; //General's Blade
    case "P25_167": return "JTL_147"; //Black One - Straight At Them
    case "P25_168": return "SHD_153"; //Poe Dameron - Quick to Improvise
    case "P25_169": return "JTL_154"; //Profundity - We Fight!
    case "P25_170": return "JTL_201"; //Ahsoka Tano - Chasing Whispers
    case "P25_171": return "JTL_142"; //Darth Vader - Scourge of Squadrons
    case "P25_172": return "JTL_142"; //Darth Vader - Scourge of Squadrons
    case "P25_173": return "SOR_051"; //Luke Skywalker - Jedi Knight
    case "P25_174": return "SOR_051"; //Luke Skywalker - Jedi Knight
    case "P25_175": return "SOR_051"; //Luke Skywalker - Jedi Knight
    case "P25_176": return "SOR_051"; //Luke Skywalker - Jedi Knight
    case "P25_177": return "SOR_051"; //Luke Skywalker - Jedi Knight
    case "P26_001": return "ASH_243"; //Darth Vader - Meet Your Destiny
    case "P26_002": return "LAW_093"; //Rio Durant - Beckett's Right Hands
    case "P26_003": return "LAW_093"; //Rio Durant - Beckett's Right Hands
    case "P26_004": return "LAW_054"; //Maul - Master of the Shadow Collective
    case "P26_005": return "LAW_054"; //Maul - Master of the Shadow Collective
    case "P26_006": return "LAW_054"; //Maul - Master of the Shadow Collective
    case "P26_007": return "LAW_054"; //Maul - Master of the Shadow Collective
    case "P26_008": return "LAW_057"; //Benthic “Two Tubes” - The War Has Just Begun
    case "P26_009": return "LAW_057"; //Benthic “Two Tubes” - The War Has Just Begun
    case "P26_010": return "LAW_034"; //Chewbacca - Mighty Rescuer
    case "P26_011": return "LAW_034"; //Chewbacca - Mighty Rescuer
    case "P26_012": return "LAW_034"; //Chewbacca - Mighty Rescuer
    case "P26_013": return "LAW_034"; //Chewbacca - Mighty Rescuer
    case "P26_014": return "ASH_031"; //Hera Syndulla - Renegade General
    case "P26_015": return "ASH_031"; //Hera Syndulla - Renegade General
    case "P26_016": return "ASH_155"; //Grogu - Yes. Yes. Yes.
    case "P26_017": return "ASH_155"; //Grogu - Yes. Yes. Yes.
    case "P26_018": return "ASH_155"; //Grogu - Yes. Yes. Yes.
    case "P26_019": return "ASH_155"; //Grogu - Yes. Yes. Yes.
    case "P26_020": return "ASH_030"; //Marrok - Mysterious Warrior
    case "P26_021": return "ASH_030"; //Marrok - Mysterious Warrior
    case "P26_022": return "ASH_112"; //Luke Skywalker - Answering the Call
    case "P26_023": return "ASH_112"; //Luke Skywalker - Answering the Call
    case "P26_024": return "ASH_112"; //Luke Skywalker - Answering the Call
    case "P26_025": return "ASH_112"; //Luke Skywalker - Answering the Call
    case "P26_026": return "HMW_128"; //Mace Windu - Austere Centrist
    case "P26_027": return "HMW_128"; //Mace Windu - Austere Centrist
    case "P26_028": return "HMW_110"; //Emperor Palpatine - Consolidating Power
    case "P26_029": return "HMW_110"; //Emperor Palpatine - Consolidating Power
    case "P26_030": return "HMW_110"; //Emperor Palpatine - Consolidating Power
    case "P26_031": return "HMW_110"; //Emperor Palpatine - Consolidating Power
    case "P26_032": return "HMW_053"; //Fett's Firespray - Settling the Score
    case "P26_033": return "HMW_053"; //Fett's Firespray - Settling the Score
    case "P26_034": return "HMW_078"; //Qui-Gon Jinn - We'll Handle This
    case "P26_035": return "HMW_078"; //Qui-Gon Jinn - We'll Handle This
    case "P26_036": return "HMW_078"; //Qui-Gon Jinn - We'll Handle This
    case "P26_037": return "HMW_078"; //Qui-Gon Jinn - We'll Handle This
    case "P26_038": return "SEC_046"; //Galen Erso - You'll Never Win
    case "P26_039": return "LOF_062"; //Axe Woves - Accomplished Warrior
    case "P26_040": return "LOF_093"; //Gungi - Finding Himself
    case "P26_041": return "LOF_130"; //HK-47 - Exclamation: Die, Meatbag!
    case "P26_042": return "SEC_133"; //Syril Karn - Where Is He?
    case "P26_043": return "JTL_140"; //IG-2000 - Assassin's Aggressor
    case "P26_044": return "SEC_147"; //Chopper - War Hero
    case "P26_045": return "JTL_181"; //Planetary Bombardment
    case "P26_046": return "LOF_179"; //Aurra Sing - Patient and Deadly
    case "P26_047": return "SEC_187"; //General Grievous - Scuttling to Safety
    case "P26_048": return "LOF_195"; //Vernestra Rwoh - Precocious Knight
    case "P26_049": return "LOF_246"; //Grogu - Mysterious Child
    case "P26_050": return "SEC_046"; //Galen Erso - You'll Never Win
    case "P26_051": return "LOF_062"; //Axe Woves - Accomplished Warrior
    case "P26_052": return "LOF_093"; //Gungi - Finding Himself
    case "P26_053": return "LOF_130"; //HK-47 - Exclamation: Die, Meatbag!
    case "P26_054": return "SEC_133"; //Syril Karn - Where Is He?
    case "P26_055": return "JTL_140"; //IG-2000 - Assassin's Aggressor
    case "P26_056": return "SEC_147"; //Chopper - War Hero
    case "P26_057": return "JTL_181"; //Planetary Bombardment
    case "P26_058": return "LOF_179"; //Aurra Sing - Patient and Deadly
    case "P26_059": return "SEC_187"; //General Grievous - Scuttling to Safety
    case "P26_060": return "LOF_195"; //Vernestra Rwoh - Precocious Knight
    case "P26_061": return "LOF_246"; //Grogu - Mysterious Child
    case "P26_062": return "SEC_082"; //Chancellor Palpatine - I Am the Senate
    case "P26_063": return "SEC_082"; //Chancellor Palpatine - I Am the Senate
    case "P26_064": return "LOF_200"; //Qui-Gon Jinn - The Negotiations Will Be Short
    case "P26_065": return "LOF_200"; //Qui-Gon Jinn - The Negotiations Will Be Short
    case "P26_066": return "LOF_200"; //Qui-Gon Jinn - The Negotiations Will Be Short
    case "P26_067": return "LOF_200"; //Qui-Gon Jinn - The Negotiations Will Be Short
    case "P26_068": return "LOF_200"; //Qui-Gon Jinn - The Negotiations Will Be Short
    case "P26_070": return "LOF_031"; //Karis - We Don't Like Strangers
    case "P26_071": return "SEC_035"; //Darth Sion - Lord of Pain
    case "P26_072": return "LOF_037"; //Darth Vader - Twilight of the Apprentice
    case "P26_073": return "LOF_042"; //Always Two
    case "P26_074": return "LOF_079"; //Shatterpoint
    case "P26_075": return "SEC_099"; //Naboo Royal Starship - Fit For A Queen
    case "P26_076": return "JTL_147"; //Black One - Straight At Them
    case "P26_077": return "SEC_148"; //Karis Nemik - Freedom is a Pure Idea
    case "P26_078": return "JTL_201"; //Ahsoka Tano - Chasing Whispers
    case "P26_079": return "SEC_244"; //Darth Nihilus - Lord of Hunger
    case "P26_080": return "LAW_032"; //Cad Bane - Now It's My Turn
    case "P26_081": return "LAW_037"; //Han Solo - Hibernation Sick
    case "P26_082": return "LAW_072"; //Max Rebo - Encore!
    case "P26_083": return "LAW_101"; //Lawbringer - Shadow Over Lothal
    case "P26_084": return "SEC_148"; //Karis Nemik - Freedom is a Pure Idea
    case "P26_085": return "JTL_250"; //Sabine's Masterpiece - Crazy Colorful
    case "P26_086": return "LOF_031"; //Karis - We Don't Like Strangers
    case "P26_087": return "SEC_035"; //Darth Sion - Lord of Pain
    case "P26_088": return "LOF_037"; //Darth Vader - Twilight of the Apprentice
    case "P26_089": return "LOF_042"; //Always Two
    case "P26_090": return "LOF_079"; //Shatterpoint
    case "P26_091": return "SEC_099"; //Naboo Royal Starship - Fit For A Queen
    case "P26_092": return "JTL_147"; //Black One - Straight At Them
    case "P26_093": return "SEC_148"; //Karis Nemik - Freedom is a Pure Idea
    case "P26_094": return "JTL_201"; //Ahsoka Tano - Chasing Whispers
    case "P26_095": return "SEC_244"; //Darth Nihilus - Lord of Hunger
    case "P26_096": return "LAW_032"; //Cad Bane - Now It's My Turn
    case "P26_097": return "LAW_037"; //Han Solo - Hibernation Sick
    case "P26_098": return "LAW_072"; //Max Rebo - Encore!
    case "P26_099": return "LAW_101"; //Lawbringer - Shadow Over Lothal
    case "P26_100": return "SEC_148"; //Karis Nemik - Freedom is a Pure Idea
    case "P26_101": return "JTL_250"; //Sabine's Masterpiece - Crazy Colorful
    case "P26_102": return "LOF_098"; //Leia Organa - Extraordinary
    case "P26_103": return "LOF_098"; //Leia Organa - Extraordinary
    case "P26_104": return "SEC_188"; //Darth Traya - Lord of Betrayal
    case "P26_105": return "SEC_188"; //Darth Traya - Lord of Betrayal
    case "P26_106": return "SEC_188"; //Darth Traya - Lord of Betrayal
    case "P26_107": return "SEC_188"; //Darth Traya - Lord of Betrayal
    case "P26_108": return "SEC_188"; //Darth Traya - Lord of Betrayal
    case "P26_110": return "LAW_086"; //The Stranger - No Survivors
    case "P26_111": return "LAW_086"; //The Stranger - No Survivors
    case "P26_112": return "LAW_080"; //Luke Skywalker - Profit or Be Destroyed
    case "P26_113": return "LAW_080"; //Luke Skywalker - Profit or Be Destroyed
    case "P26_114": return "LAW_080"; //Luke Skywalker - Profit or Be Destroyed
    case "P26_115": return "LAW_080"; //Luke Skywalker - Profit or Be Destroyed
    case "P26_116": return "LAW_080"; //Luke Skywalker - Profit or Be Destroyed
    case "P26_118": return "ASH_097"; //Moff Gideon - Remnant Commander
    case "P26_119": return "ASH_062"; //The Mandalorian - Devoted Rescuer
    case "P26_120": return "LAW_214"; //Boba Fett - For a Price
    case "P26_121": return "ASH_053"; //Pre Vizsla - Strong-Willed Ruler
    case "P26_122": return "LAW_071"; //The Max Rebo Band - Jatz-Wailers
    case "P26_123": return "ASH_056"; //Huyang - Your Aptitude Falls Short
    case "P26_128": return "LAW_237"; //Qui-Gon Jinn - Influencing Chance
    case "P26_129": return "LAW_020"; //Daimyo's Palace
    case "P26_130": return "LAW_024"; //Imperial Command Complex
    case "P26_131": return "LAW_027"; //Stygeon Spire
    case "P26_132": return "LAW_028"; //Canto Bight
    case "P26_133": return "TS26_01"; //Count Dooku - Offering Aid
    case "P26_134": return "TS26_02"; //Anakin Skywalker - Protect Her At All Costs
    case "P26_135": return "TS26_03"; //Maul - Collective Ambition
    case "P26_136": return "TS26_04"; //Padmé Amidala - Follow My Lead
    case "P26_137": return "TS26_05"; //Savage Opress - You Must Have Your Revenge
    case "P26_138": return "TS26_06"; //Rex - No Other Option
    case "P26_139": return "TS26_07"; //Asajj Ventress - Ambitious Apprentice
    case "P26_140": return "TS26_08"; //Ahsoka Tano - I Have an Idea
    case "P26_141": return "LAW_068"; //Millennium Falcon - Dodging Patrols
    case "P26_142": return "ASH_050"; //Morgan Elsbeth - Life Abandoned
    case "P26_143": return "LAW_108"; //Lando Calrissian - Eyes Open
    case "P26_144": return "LAW_067"; //Jyn Erso - Take the Next Chance
    case "P26_145": return "SEC_090"; //Director Krennic - I Lose Nothing But Time
    case "P26_146": return "SEC_133"; //Syril Karn - Where Is He?
    case "P26_147": return "LAW_045"; //Zeb Orellios - Spectre Four
    case "P26_148": return "SEC_172"; //Cinta Kaz - The Struggle Comes First
    case "P26_149": return "SEC_205"; //Obi-Wan Kenobi - Finding What Doesn't Exist
    case "P26_150": return "LOF_213"; //The Legacy Run - Doomed Debris
    case "P26_151": return "LAW_252"; //Fett's Firespray - In Pursuit
    case "P26_152": return "ASH_255"; //Anakin Skywalker - You Were Right About Me
    case "P26_153": return "LAW_068"; //Millennium Falcon - Dodging Patrols
    case "P26_154": return "ASH_050"; //Morgan Elsbeth - Life Abandoned
    case "P26_155": return "LAW_108"; //Lando Calrissian - Eyes Open
    case "P26_156": return "LAW_067"; //Jyn Erso - Take the Next Chance
    case "P26_157": return "SEC_090"; //Director Krennic - I Lose Nothing But Time
    case "P26_158": return "SEC_133"; //Syril Karn - Where Is He?
    case "P26_159": return "LAW_045"; //Zeb Orellios - Spectre Four
    case "P26_160": return "SEC_172"; //Cinta Kaz - The Struggle Comes First
    case "P26_161": return "SEC_205"; //Obi-Wan Kenobi - Finding What Doesn't Exist
    case "P26_162": return "LOF_213"; //The Legacy Run - Doomed Debris
    case "P26_163": return "LAW_252"; //Fett's Firespray - In Pursuit
    case "P26_164": return "ASH_255"; //Anakin Skywalker - You Were Right About Me
    case "P26_165": return "LAW_045"; //Zeb Orellios - Spectre Four
    case "P26_166": return "LAW_067"; //Jyn Erso - Take the Next Chance
    case "P26_167": return "LAW_068"; //Millennium Falcon - Dodging Patrols
    case "P26_168": return "ASH_050"; //Morgan Elsbeth - Life Abandoned
    case "P26_169": return "LAW_108"; //Lando Calrissian - Eyes Open
    case "P26_170": return "SEC_090"; //Director Krennic - I Lose Nothing But Time
    case "P26_171": return "SEC_133"; //Syril Karn - Where Is He?
    case "P26_172": return "SEC_172"; //Cinta Kaz - The Struggle Comes First
    case "P26_173": return "SEC_205"; //Obi-Wan Kenobi - Finding What Doesn't Exist
    case "P26_174": return "LOF_213"; //The Legacy Run - Doomed Debris
    case "P26_175": return "LAW_252"; //Fett's Firespray - In Pursuit
    case "P26_176": return "ASH_255"; //Anakin Skywalker - You Were Right About Me
    case "P26_177": return "ASH_030"; //Marrok - Mysterious Warrior
    case "P26_178": return "ASH_034"; //Wicket - Yub Nub!
    case "P26_179": return "ASH_053"; //Pre Vizsla - Strong-Willed Ruler
    case "P26_180": return "SEC_042"; //Cassian Andor - Lay Low
    case "P26_181": return "ASH_105"; //Bo-Katan Kryze - For All of Mandalore
    case "P26_182": return "SEC_103"; //Mon Mothma - Clinging to Hope
    case "P26_183": return "ASH_148"; //Ninth Sister - Hulking Inquisitor
    case "P26_184": return "LAW_214"; //Boba Fett - For a Price
    case "P26_185": return "SEC_209"; //The Mandalorian - Cleaning Up Nevarro
    case "P26_186": return "SEC_219"; //Ebon Hawk - Cause and Effect
    case "P26_187": return "LAW_044"; //Single Reactor Ignition
    case "P26_188": return "ASH_112"; //Luke Skywalker - Answering the Call
    case "P26_189": return "ASH_052"; //Chimaera - A Frightening Reality
    case "P26_190": return "ASH_028"; //Paz Vizsla - For a Brighter Future
    case "P26_191": return "ASH_062"; //The Mandalorian - Devoted Rescuer
    case "P26_192": return "ASH_064"; //The Armorer - Secrecy is Our Survival
    case "P26_193": return "SEC_051"; //Bo-Katan Kryze - Alone
    case "P26_194": return "ASH_079"; //Koska Reeves - Warrior of Mandalore
    case "P26_195": return "ASH_097"; //Moff Gideon - Remnant Commander
    case "P26_196": return "ASH_135"; //The Darksaber - Icon of Leadership
    case "P26_197": return "ASH_155"; //Grogu - Yes. Yes. Yes.
    case "P26_198": return "ASH_172"; //Razor Crest - Outfitted Armament
    case "P26_199": return "LAW_095"; //Finn - Looking Closer
    case "P26_200": return "LAW_185"; //Ben Solo - Facing the Light
    case "P26_201": return "ASH_040"; //Poe Dameron - I'll Come Back For You
    case "P26_202": return "LAW_149"; //Rey - Skywalker
    case "P26_203": return "ASH_033"; //Grand Admiral Thrawn - Orchestrating His Return
    case "P26_204": return "TS26_14"; //Yoda - Begun, the Clone War Has
    case "P26_205": return "LAW_066"; //Tear This Ship Apart
    case "P26_206": return "LAW_088"; //Anakin Skywalker - Prescient Podracer
    case "P26_207": return "TS26_36"; //Tribunal - Grave of the 332nd
    case "P26_208": return "ASH_047"; //Gar Saxon - Coveting Power
    case "P26_209": return "ASH_048"; //Imperial Armored Commando
    case "P26_210": return "ASH_060"; //Cobb Vanth - Let Me Handle This
    case "P26_211": return "ASH_063"; //Bo-Katan's Gauntlet - Reinforce from Above
    case "P26_212": return "TS26_50"; //General Grievous - Crush Them!
    case "P26_213": return "TS26_56"; //Galactic Escalation
    case "P26_214": return "ASH_192"; //Fennec Shand - The Galaxy Is Dangerous
    case "P26_215": return "ASH_245"; //Eye of Sion - Delivered from Exile
    case "P26_216": return "TS26_14"; //Yoda - Begun, the Clone War Has
    case "P26_217": return "LAW_066"; //Tear This Ship Apart
    case "P26_218": return "LAW_088"; //Anakin Skywalker - Prescient Podracer
    case "P26_219": return "TS26_36"; //Tribunal - Grave of the 332nd
    case "P26_220": return "ASH_047"; //Gar Saxon - Coveting Power
    case "P26_221": return "ASH_048"; //Imperial Armored Commando
    case "P26_222": return "ASH_060"; //Cobb Vanth - Let Me Handle This
    case "P26_223": return "ASH_063"; //Bo-Katan's Gauntlet - Reinforce from Above
    case "P26_224": return "TS26_50"; //General Grievous - Crush Them!
    case "P26_225": return "TS26_56"; //Galactic Escalation
    case "P26_226": return "ASH_192"; //Fennec Shand - The Galaxy Is Dangerous
    case "P26_227": return "ASH_245"; //Eye of Sion - Delivered from Exile
    case "P26_228": return "ASH_031"; //Hera Syndulla - Renegade General
    case "P26_229": return "ASH_100"; //Gallius Rax - Counselor to the Empire
    case "P26_230": return "LAW_171"; //Stockpile
    case "P26_231": return "ASH_153"; //Green Leader - Crynyd's Sacrifice
    case "P26_232": return "LAW_229"; //The Master Codebreaker - High Stakes
    case "P26_233": return "ASH_219"; //Jod Na Nawood - Keeping Secrets
    case "P26_234": return "LAW_039"; //Latts Razzi - Deadly Whipmaster
    case "P26_235": return "ASH_053"; //Pre Vizsla - Strong-Willed Ruler
    case "P26_236": return "ASH_158"; //Han Solo - It'll Work
    case "P26_237": return "ASH_197"; //Executor - Final Destruction of the Alliance
    case "P26_238": return "ASH_059"; //Leia Organa - Vigilant for Danger
    case "P26_239": return "ASH_243"; //Darth Vader - Meet Your Destiny
    case "P26_240": return "TS26_30"; //Maul - One Last Lesson
    case "P26_241": return "HMW_053"; //Fett's Firespray - Settling the Score
    case "P26_242": return "TS26_21"; //Gar Saxon - Sundari Pillager
    case "P26_243": return "IC27_041"; //Captain Rex - Staunch Advocate
    case "P26_244": return "SOR_052"; //Redemption - Medical Frigate
    case "P26_245": return "TS26_47"; //Take Cover
    case "P26_246": return "HMW_104"; //Garnac - Let the Hunt Begin!
    case "P26_247": return "ASH_037"; //Red Leader - Strike the Reactor
    case "P26_248": return "IC27_103"; //Grand Inquisitor - How the Mighty Will Fall
    case "P26_249": return "HMW_177"; //Adamant Ewoks
    case "P26_250": return "HMW_203"; //Victor Squadron - In Attack Formation
    case "P26_251": return "HMW_208"; //Luke Skywalker - Dreaming Farmboy
    case "P26_252": return "TS26_30"; //Maul - One Last Lesson
    case "P26_253": return "HMW_053"; //Fett's Firespray - Settling the Score
    case "P26_254": return "TS26_21"; //Gar Saxon - Sundari Pillager
    case "P26_255": return "IC27_041"; //Captain Rex - Staunch Advocate
    case "P26_256": return "SOR_052"; //Redemption - Medical Frigate
    case "P26_257": return "TS26_47"; //Take Cover
    case "P26_258": return "HMW_104"; //Garnac - Let the Hunt Begin!
    case "P26_259": return "ASH_037"; //Red Leader - Strike the Reactor
    case "P26_260": return "IC27_103"; //Grand Inquisitor - How the Mighty Will Fall
    case "P26_261": return "HMW_177"; //Adamant Ewoks
    case "P26_262": return "HMW_203"; //Victor Squadron - In Attack Formation
    case "P26_263": return "HMW_208"; //Luke Skywalker - Dreaming Farmboy
    case "P26_264": return "HMW_044"; //Ima-Gun Di - Holding Out For Resupply
    case "P26_265": return "HMW_062"; //Nuvo Vindi - Blue Shadow Perfected
    case "P26_266": return "IC27_038"; //Admiral Holdo - We Are the Spark
    case "P26_267": return "HMW_126"; //Verdant Fortress
    case "P26_268": return "ASH_151"; //Operation Cinder
    case "P26_269": return "IC27_166"; //I've Got A Bad Feeling
    case "P26_270": return "HMW_055"; //Mae - Kill the Dream
    case "P26_271": return "HMW_124"; //Luminara Unduli - Besieged General
    case "P26_272": return "HMW_225"; //Boba Fett - Family Found
    case "P26_273": return "IC27_187"; //Jar Jar Binks - Bumbling Representative
    case "P26_274": return "ASH_034"; //Wicket - Yub Nub!
    case "P26_275": return "HMW_117"; //Chewbacca - Resourceful Wookiee
    case "P26_279": return "ASH_028"; //Paz Vizsla - For a Brighter Future
    case "P26_280": return "LAW_077"; //Shadow of Stygeon Prime
    case "P26_281": return "LAW_079"; //K-2SO - Locking the Vault
    case "P26_282": return "ASH_042"; //Jabba the Hutt - Eminence of Tatooine
    case "P26_283": return "ASH_147"; //The Cyborg Mech - Mysterious Threat
    case "P26_284": return "ASH_179"; //Boba Fett's Rancor - Emotionally Complex Creature
    case "P26_285": return "LAW_054"; //Maul - Master of the Shadow Collective
    case "P26_286": return "SEC_048"; //Captain Rex - Into the Firefight
    case "P26_287": return "LAW_086"; //The Stranger - No Survivors
    case "P26_288": return "LAW_214"; //Boba Fett - For a Price
    case "P26_289": return "ASH_062"; //The Mandalorian - Devoted Rescuer
    case "SECP_001": return "SEC_037"; //Cantwell Arrestor Cruiser
    case "SECP_002": return "SEC_051"; //Bo-Katan Kryze - Alone
    case "SECP_003": return "SEC_068"; //Lando Calrissian - Trust Me
    case "SECP_004": return "SEC_070"; //Armor of Fortune
    case "SECP_005": return "SEC_073"; //The Eye of Aldhani
    case "SECP_006": return "SEC_086"; //Cruel Commandos
    case "SECP_007": return "SEC_093"; //C-3PO - Anything I Might Do?
    case "SECP_008": return "SEC_111"; //Jar Jar Binks - Mesa Propose…
    case "SECP_009": return "SEC_114"; //Kino Loy - You Answer to Me
    case "SECP_010": return "SEC_130"; //Ferrix Uprising
    case "SECP_011": return "SEC_143"; //The Elite Squad - Neutralizing Insurgents
    case "SECP_012": return "SEC_142"; //Fulminatrix - Fleet Killer
    case "SECP_013": return "SEC_151"; //Kazuda Xiono - I'm Not A Spy
    case "SECP_014": return "SEC_158"; //Oppression Breeds Rebellion
    case "SECP_015": return "SEC_170"; //Corellian Hounds
    case "SECP_016": return "SEC_189"; //Lurking Snub Fighter
    case "SECP_017": return "SEC_198"; //Bail Organa - Responding to Catastrophe
    case "SECP_018": return "SEC_199"; //Bravo Squadron Fighter
    case "SECP_019": return "SEC_248"; //B2EMO - That's Two Lies
    case "SECP_020": return "SEC_258"; //Grassroots Resistance
    case "SECP_021": return "SEC_037"; //Cantwell Arrestor Cruiser
    case "SECP_022": return "SEC_051"; //Bo-Katan Kryze - Alone
    case "SECP_023": return "SEC_068"; //Lando Calrissian - Trust Me
    case "SECP_024": return "SEC_070"; //Armor of Fortune
    case "SECP_025": return "SEC_073"; //The Eye of Aldhani
    case "SECP_026": return "SEC_086"; //Cruel Commandos
    case "SECP_027": return "SEC_093"; //C-3PO - Anything I Might Do?
    case "SECP_028": return "SEC_111"; //Jar Jar Binks - Mesa Propose…
    case "SECP_029": return "SEC_114"; //Kino Loy - You Answer to Me
    case "SECP_030": return "SEC_130"; //Ferrix Uprising
    case "SECP_031": return "SEC_143"; //The Elite Squad - Neutralizing Insurgents
    case "SECP_032": return "SEC_142"; //Fulminatrix - Fleet Killer
    case "SECP_033": return "SEC_151"; //Kazuda Xiono - I'm Not A Spy
    case "SECP_034": return "SEC_158"; //Oppression Breeds Rebellion
    case "SECP_035": return "SEC_170"; //Corellian Hounds
    case "SECP_036": return "SEC_189"; //Lurking Snub Fighter
    case "SECP_037": return "SEC_198"; //Bail Organa - Responding to Catastrophe
    case "SECP_038": return "SEC_199"; //Bravo Squadron Fighter
    case "SECP_039": return "SEC_248"; //B2EMO - That's Two Lies
    case "SECP_040": return "SEC_258"; //Grassroots Resistance
    case "SECW_001": return "SEC_037"; //Cantwell Arrestor Cruiser
    case "SECW_002": return "SEC_051"; //Bo-Katan Kryze - Alone
    case "SECW_003": return "SEC_068"; //Lando Calrissian - Trust Me
    case "SECW_004": return "SEC_070"; //Armor of Fortune
    case "SECW_005": return "SEC_073"; //The Eye of Aldhani
    case "SECW_006": return "SEC_086"; //Cruel Commandos
    case "SECW_007": return "SEC_093"; //C-3PO - Anything I Might Do?
    case "SECW_008": return "SEC_111"; //Jar Jar Binks - Mesa Propose…
    case "SECW_009": return "SEC_114"; //Kino Loy - You Answer to Me
    case "SECW_010": return "SEC_130"; //Ferrix Uprising
    case "SECW_011": return "SEC_143"; //The Elite Squad - Neutralizing Insurgents
    case "SECW_012": return "SEC_142"; //Fulminatrix - Fleet Killer
    case "SECW_013": return "SEC_151"; //Kazuda Xiono - I'm Not A Spy
    case "SECW_014": return "SEC_158"; //Oppression Breeds Rebellion
    case "SECW_015": return "SEC_170"; //Corellian Hounds
    case "SECW_016": return "SEC_189"; //Lurking Snub Fighter
    case "SECW_017": return "SEC_198"; //Bail Organa - Responding to Catastrophe
    case "SECW_018": return "SEC_199"; //Bravo Squadron Fighter
    case "SECW_019": return "SEC_248"; //B2EMO - That's Two Lies
    case "SECW_020": return "SEC_258"; //Grassroots Resistance
    case "SECW_021": return "SEC_037"; //Cantwell Arrestor Cruiser
    case "SECW_022": return "SEC_051"; //Bo-Katan Kryze - Alone
    case "SECW_023": return "SEC_068"; //Lando Calrissian - Trust Me
    case "SECW_024": return "SEC_070"; //Armor of Fortune
    case "SECW_025": return "SEC_073"; //The Eye of Aldhani
    case "SECW_026": return "SEC_086"; //Cruel Commandos
    case "SECW_027": return "SEC_093"; //C-3PO - Anything I Might Do?
    case "SECW_028": return "SEC_111"; //Jar Jar Binks - Mesa Propose…
    case "SECW_029": return "SEC_114"; //Kino Loy - You Answer to Me
    case "SECW_030": return "SEC_130"; //Ferrix Uprising
    case "SECW_031": return "SEC_143"; //The Elite Squad - Neutralizing Insurgents
    case "SECW_032": return "SEC_142"; //Fulminatrix - Fleet Killer
    case "SECW_033": return "SEC_151"; //Kazuda Xiono - I'm Not A Spy
    case "SECW_034": return "SEC_158"; //Oppression Breeds Rebellion
    case "SECW_035": return "SEC_170"; //Corellian Hounds
    case "SECW_036": return "SEC_189"; //Lurking Snub Fighter
    case "SECW_037": return "SEC_198"; //Bail Organa - Responding to Catastrophe
    case "SECW_038": return "SEC_199"; //Bravo Squadron Fighter
    case "SECW_039": return "SEC_248"; //B2EMO - That's Two Lies
    case "SECW_040": return "SEC_258"; //Grassroots Resistance
    default: return $cardID;
  }
}
?>