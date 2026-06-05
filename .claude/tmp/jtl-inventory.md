# JTL — Stage 1 Inventory (scratch)

266 cards (262 numbered + 4 tokens): 18 Leaders, 13 Bases, ~150 Units, ~60 Events, 7 Upgrades, 4 Tokens.

## ⚠ New core mechanics introduced by this set — build first

**None blocking.** The set's defining mechanic is **Piloting** (CR §17 — 14 cards reference Pilot/Piloting in text, far more carry the keyword). It is **already implemented in the engine**: `SWUComputePilotCost`, `SWUQueuePilotVehiclePick`, `SWUGetPilotValidTargets`, `SWUVehiclePilotCount/Capacity`, `SWUPilotCanAttach`, `IsPilot` across GameLogic.php (80 refs), CardDQHandlers.php (45), KeywordEffects.php, CustomInput.php, plus generic Pilot test cases in the working tree (`SWUSim/Tests/Cases/jtl/Pilot_*`, `Asajj_*`, `R2D2_*`). CR §17 defines it. **Gate clears.**

All other keywords appearing in JTL text are already-implemented carry-overs from SOR: Sentinel, Ambush, Shielded, Restore, Overwhelm, Raid, Grit, Saboteur, Bounty. No Plot/Credits/other unbuilt mechanic detected.

> Note: a separate agent landed the Piloting foundation on this branch (uncommitted: CardDQHandlers.php / GameLogic.php / KeywordEffects.php + the jtl/ Pilot test cases). This survey treats it as built. If those changes are reverted, re-run this skill — the gate would then HARD-STOP on Piloting.

### Open boundary questions for review
- **Pure-Piloting units** (108, 159, 196, 236, 246, 255): only text is `Piloting [cost]` + reminder, no "attached gains"/"when played as upgrade" clause. Classified **KEYWORD-ONLY** on the assumption the generic Piloting wiring fully handles play-as-upgrade + the pilot's printed power/HP modifier with zero card code. Confirm this holds (otherwise they're NEEDS-WORK).
- **Setup/deckbuild bases** (021 Colossus, 024 Data Vault, 025 Thermal Oscillator, 028 Mos Eisley): abilities modify starting-hand size / minimum deck size / mulligan — game-setup, not in-play triggers. Marked NEEDS-WORK but flagged **possibly out of scope** for the card-ability engine.
- **Swarming Vulture Droid (256)** and others carry deckbuild riders ("up to 15 copies") alongside a real in-play passive; only the passive is implemented, the deckbuild rider is import-layer.

## Card inventory (266 cards)

| ID | Name | Type | Bucket | Note |
|----|------|------|--------|------|
| JTL_001 | Asajj Ventress | Leader | NEEDS-WORK | Action: 1 dmg friendly→1 dmg enemy same arena; deploy side is a Pilot |
| JTL_002 | Grand Admiral Thrawn | Leader | NEEDS-WORK | when you use a When-Defeated ability, may exhaust to reuse it |
| JTL_003 | Lando Calrissian | Leader | NEEDS-WORK | Action: play a unit; if ground+space, give a Shield |
| JTL_004 | Rose Tico | Leader | NEEDS-WORK | Action: heal 2 from a Vehicle that attacked this phase |
| JTL_005 | Admiral Piett | Leader | NEEDS-WORK | Action: play a Capital Ship −1 |
| JTL_006 | Darth Vader | Leader | NEEDS-WORK | Action: if attacked w/ non-token Vehicle, create TIE |
| JTL_007 | Admiral Holdo | Leader | NEEDS-WORK | Action: Resistance unit +2/+2 this phase |
| JTL_008 | Wedge Antilles | Leader | NEEDS-WORK | Action: play a card using Piloting −1 |
| JTL_009 | Boba Fett | Leader | NEEDS-WORK | non-combat dmg → may exhaust → 1 indirect to a player |
| JTL_010 | Captain Phasma | Leader | NEEDS-WORK | Action: if First Order played, 1 dmg to base |
| JTL_011 | Major Vonreg | Leader | NEEDS-WORK | Action: play a Vehicle; give another unit +1/+0 |
| JTL_012 | Luke Skywalker | Leader | NEEDS-WORK | Action: if Fighter attacked, 1 dmg to a unit |
| JTL_013 | Poe Dameron | Leader | NEEDS-WORK | Action: flip + attach as Pilot upgrade to a Vehicle |
| JTL_014 | Admiral Trench | Leader | NEEDS-WORK | Action: discard a 3+ cost, draw |
| JTL_015 | Rio Durant | Leader | NEEDS-WORK | Action: attack with space unit, +1/+0 + Saboteur |
| JTL_016 | Admiral Ackbar | Leader | NEEDS-WORK | Action: exhaust non-leader → its controller makes an X-Wing |
| JTL_017 | Han Solo | Leader | NEEDS-WORK | Action: reveal top, attack; odd-cost mismatch → +1/+0 |
| JTL_018 | Kazuda Xiono | Leader | NEEDS-WORK | Action: friendly loses abilities this round; take extra action |
| JTL_019 | City in the Clouds | Base | no-op | blank |
| JTL_020 | Shield Generator Complex | Base | no-op | blank |
| JTL_021 | Colossus | Base | NEEDS-WORK | draw 1 less in starting hand (setup — maybe out of scope) |
| JTL_022 | Resistance Headquarters | Base | no-op | blank |
| JTL_023 | Theed Palace | Base | no-op | blank |
| JTL_024 | Data Vault | Base | NEEDS-WORK | min deck size +10 (deckbuild — maybe out of scope) |
| JTL_025 | Thermal Oscillator | Base | NEEDS-WORK | min deck size −5 (deckbuild — maybe out of scope) |
| JTL_026 | Massassi Temple | Base | no-op | blank |
| JTL_027 | Nadiri Dockyards | Base | no-op | blank |
| JTL_028 | Nabat Village | Base | NEEDS-WORK | draw 3 more, no mulligan (setup — maybe out of scope) |
| JTL_029 | Chopper Base | Base | no-op | blank |
| JTL_030 | Mos Eisley | Base | no-op | blank |
| JTL_031 | Lake Country | Base | no-op | blank |
| JTL_032 | Director Krennic | Unit | NEEDS-WORK | Shielded + first When-Defeated unit each round costs 1 less |
| JTL_033 | Onyx Squadron Brute | Unit | NEEDS-WORK | When Defeated: heal 2 from a base |
| JTL_034 | Interceptor Ace | Unit | NEEDS-WORK | Grit + Piloting; attached gains Grit |
| JTL_035 | Tam Ryvora | Unit | NEEDS-WORK | Piloting; attached On Attack: enemy −1/−1 this phase |
| JTL_036 | Iden Versio | Unit | NEEDS-WORK | Shielded + Piloting; on attach give attached a Shield |
| JTL_037 | Banshee | Unit | NEEDS-WORK | On Attack: deal dmg to a unit = damage on this |
| JTL_038 | Corvus | Unit | NEEDS-WORK | Restore 2 + When Played: attach a friendly Pilot to this |
| JTL_039 | Chimaera | Unit | NEEDS-WORK | When Played: use a friendly unit's When-Defeated ability |
| JTL_040 | Fleet Interdictor | Unit | NEEDS-WORK | Sentinel + When Defeated: defeat a space unit ≤3 |
| JTL_041 | Annihilator | Unit | NEEDS-WORK | When Played/Defeated: defeat enemy + name-hunt deck&hand |
| JTL_042 | Power from Pain | Event | NEEDS-WORK | unit +1/+0 per damage on it, this phase |
| JTL_043 | No Glory, Only Results | Event | NEEDS-WORK | take control of a non-leader unit, then defeat it |
| JTL_044 | Echo Base Engineer | Unit | NEEDS-WORK | When Played: Shield to a damaged Vehicle |
| JTL_045 | Hera Syndulla | Unit | NEEDS-WORK | Restore 1 + Piloting; attached gains Restore 1 |
| JTL_046 | Paige Tico | Unit | NEEDS-WORK | Piloting; attached On Attack: exp then 1 dmg to self |
| JTL_047 | Admiral Yularen | Unit | NEEDS-WORK | When Played: choose keyword; friendly Vehicles gain it |
| JTL_048 | Cassian Andor | Unit | NEEDS-WORK | Piloting; attached On Attack: discard top of defender deck |
| JTL_049 | L3-37 | Unit | NEEDS-WORK | if would be defeated, instead attach as Pilot upgrade |
| JTL_050 | Phantom II | Unit | NEEDS-WORK | Grit + Action: attach to The Ghost; +3/+3 + Grit |
| JTL_051 | Red Squadron X-Wing | Unit | NEEDS-WORK | When Played: may deal 2 to self, draw |
| JTL_052 | D'Qar Cargo Frigate | Unit | NEEDS-WORK | −1/−0 per damage on it (passive) |
| JTL_053 | The Ghost | Unit | NEEDS-WORK | Spectre units gain its keywords; while upgraded gains Sentinel |
| JTL_054 | Gold Leader | Unit | NEEDS-WORK | Shielded + while defending attacker −1/−0 |
| JTL_055 | You're All Clear, Kid | Event | NEEDS-WORK | defeat space unit ≤3 HP; if opp no space, give exp |
| JTL_056 | Hondo Ohnaka | Unit | NEEDS-WORK | Shielded + On Attack: steal a non-Pilot upgrade |
| JTL_057 | Astromech Pilot | Unit | NEEDS-WORK | Piloting; when played as upgrade heal 2 |
| JTL_058 | Academy Graduate | Unit | KEYWORD-ONLY | Sentinel |
| JTL_059 | Corporate Defense Shuttle | Unit | NEEDS-WORK | "This unit can't attack" (passive restriction) |
| JTL_060 | Desperate Commando | Unit | NEEDS-WORK | When Defeated: a unit −1/−1 this phase |
| JTL_061 | Royal Security Fighter | Unit | KEYWORD-ONLY | Grit |
| JTL_062 | Silver Angel | Unit | NEEDS-WORK | when dmg healed from this: 1 dmg to a space unit |
| JTL_063 | Landing Shuttle | Unit | NEEDS-WORK | When Defeated: may draw |
| JTL_064 | Omicron Strike Craft | Unit | KEYWORD-ONLY | Sentinel |
| JTL_065 | Outer Rim Outlaws | Unit | KEYWORD-ONLY | Shielded |
| JTL_066 | Trace Martez | Unit | NEEDS-WORK | Piloting; attached On Attack heal 2 total among units |
| JTL_067 | Cloaked StarViper | Unit | NEEDS-WORK | When Played: give 2 Shields to this |
| JTL_068 | Perimeter AT-RT | Unit | KEYWORD-ONLY | Sentinel |
| JTL_069 | Munificent Frigate | Unit | VANILLA | blank |
| JTL_070 | U-Wing Lander | Unit | NEEDS-WORK | When Played: 3 exp; complete-attack: move an upgrade |
| JTL_071 | CR90 Relief Runner | Unit | NEEDS-WORK | Restore 2 + When Defeated: heal up to 3 |
| JTL_072 | Wing Guard Security Team | Unit | NEEDS-WORK | Sentinel + When Played: Shields to up to 2 Fringe |
| JTL_073 | Grim Valor | Upgrade | NEEDS-WORK | attached gains When Defeated: may exhaust a unit |
| JTL_074 | Close the Shield Gate | Event | NEEDS-WORK | prevent next damage to a chosen base this phase |
| JTL_075 | Repair | Event | NEEDS-WORK | heal 3 from a unit or base |
| JTL_076 | Covering the Wing | Event | NEEDS-WORK | X-Wing token + may Shield another unit |
| JTL_077 | In the Heat of Battle | Event | NEEDS-WORK | each unit gains Sentinel loses Saboteur this phase |
| JTL_078 | Direct Hit | Event | NEEDS-WORK | defeat a non-leader Vehicle |
| JTL_079 | Out the Airlock | Event | NEEDS-WORK | a unit −5/−5 this phase |
| JTL_080 | Nebula Ignition | Event | NEEDS-WORK | defeat each unit that isn't upgraded |
| JTL_081 | First Order TIE Fighter | Unit | NEEDS-WORK | while you control a token unit, gains Raid 1 (conditional) |
| JTL_082 | Kijimi Patrollers | Unit | NEEDS-WORK | When Played: create a TIE Fighter |
| JTL_083 | Pantoran Starship Thief | Unit | NEEDS-WORK | When Played: pay 3, attach to + take control of a Fighter/Transport |
| JTL_084 | Wingman Victor Two | Unit | NEEDS-WORK | Piloting; when played as upgrade create a TIE |
| JTL_085 | Victor Leader | Unit | NEEDS-WORK | other friendly space units +1/+1 (aura) |
| JTL_086 | Wingman Victor Three | Unit | NEEDS-WORK | Piloting; when played as upgrade give an exp |
| JTL_087 | TIE Ambush Squadron | Unit | NEEDS-WORK | Ambush + When Played/Defeated: create a TIE |
| JTL_088 | Captain Phasma | Unit | NEEDS-WORK | When Played/On Attack: another First Order +2/+2 |
| JTL_089 | The Invisible Hand | Unit | NEEDS-WORK | When Played/complete-attack: search 8 for a Droid, draw/free |
| JTL_090 | Executor | Unit | NEEDS-WORK | Overwhelm + When Played/On Attack/Defeated: create 3 TIEs |
| JTL_091 | Apology Accepted | Event | NEEDS-WORK | defeat a friendly unit; may give 2 exp |
| JTL_092 | Scramble Fighters | Event | NEEDS-WORK | create 8 TIEs readied, can't attack bases this phase |
| JTL_093 | Nien Nunb | Unit | NEEDS-WORK | +1/+0 per other Pilot + Piloting; attached same |
| JTL_094 | Luke Skywalker | Unit | NEEDS-WORK | Piloting; if upgrade would be defeated move to ground |
| JTL_095 | Phoenix Squadron A-Wing | Unit | VANILLA | blank |
| JTL_096 | Blue Leader | Unit | NEEDS-WORK | Ambush + When Played: pay 2 → move to ground + 2 exp |
| JTL_097 | Leia Organa | Unit | NEEDS-WORK | Restore 1 + When Played: attack with a Pilot unit, +1/+0 |
| JTL_098 | Snap Wexley | Unit | NEEDS-WORK | when played as unit/On Attack: next Resistance card −1 |
| JTL_099 | Veteran Fleet Officer | Unit | NEEDS-WORK | When Played: create an X-Wing |
| JTL_100 | Poe Dameron | Unit | NEEDS-WORK | when played as unit: X-Wing + may attach as Pilot |
| JTL_101 | Red Leader | Unit | NEEDS-WORK | cost −1 per Pilot + when Pilot attaches: create X-Wing |
| JTL_102 | Resistance Blue Squadron | Unit | NEEDS-WORK | When Played: dmg to a unit = # friendly space units |
| JTL_103 | Chewbacca | Unit | NEEDS-WORK | can't be defeated/bounced by enemy + Piloting; attached same |
| JTL_104 | Raddus | Unit | NEEDS-WORK | conditional Sentinel + When Defeated: deal power to enemy |
| JTL_105 | The Starhawk | Unit | NEEDS-WORK | Ambush + pay half costs (rounded up) |
| JTL_106 | Unity of Purpose | Event | NEEDS-WORK | +1/+1 per friendly unit w/ different name |
| JTL_107 | Bunker Defender | Unit | NEEDS-WORK | while you control a Vehicle, gains Sentinel (conditional) |
| JTL_108 | Clone Pilot | Unit | KEYWORD-ONLY | Piloting only |
| JTL_109 | Jarek Yeager | Unit | NEEDS-WORK | Piloting; while ground+space attached gains Sentinel |
| JTL_110 | Scouting Headhunter | Unit | KEYWORD-ONLY | Sentinel |
| JTL_111 | Seasoned Fleet Admiral | Unit | NEEDS-WORK | Raid 1 + when opp draws in action phase: may give exp |
| JTL_112 | Eager Escort Fighter | Unit | KEYWORD-ONLY | Ambush |
| JTL_113 | Homestead Militia | Unit | NEEDS-WORK | while you control 6+ resources, gains Sentinel (conditional) |
| JTL_114 | Adept ARC-170 | Unit | KEYWORD-ONLY | Restore 2 |
| JTL_115 | Clone Combat Squadron | Unit | NEEDS-WORK | +1/+1 per other friendly space unit (passive) |
| JTL_116 | Dornean Gunship | Unit | NEEDS-WORK | When Played: indirect to a player = # Vehicles |
| JTL_117 | General Draven | Unit | NEEDS-WORK | When Played/On Attack: create an X-Wing |
| JTL_118 | MC30 Assault Frigate | Unit | KEYWORD-ONLY | Overwhelm + Raid 1 |
| JTL_119 | Resupply Carrier | Unit | NEEDS-WORK | When Played: put top of deck into play as a resource |
| JTL_120 | Dorsal Turret | Upgrade | NEEDS-WORK | attach Vehicle; granted "combat dmg to a unit → defeat it" |
| JTL_121 | Salvage | Event | NEEDS-WORK | play a Vehicle from discard, then 1 dmg to it |
| JTL_122 | All Wings Report In | Event | NEEDS-WORK | exhaust up to 2 space → an X-Wing per exhaust |
| JTL_123 | Dogfight | Event | NEEDS-WORK | attack with a unit even if exhausted, can't hit bases |
| JTL_124 | Tandem Assault | Event | NEEDS-WORK | attack with space then ground (+2/+0) |
| JTL_125 | Air Superiority | Event | NEEDS-WORK | if more space units, 4 dmg to a ground unit |
| JTL_126 | Eject | Event | NEEDS-WORK | detach a Pilot to ground, exhaust, draw |
| JTL_127 | Lightspeed Assault | Event | NEEDS-WORK | defeat friendly space, deal its power; indirect back |
| JTL_128 | Prepare for Takeoff | Event | NEEDS-WORK | search top 8 for up to 2 Vehicles, draw them |
| JTL_129 | Focus Fire | Event | NEEDS-WORK | each friendly Vehicle in arena deals power to a unit |
| JTL_130 | Timely Reinforcements | Event | NEEDS-WORK | X-Wings w/ Sentinel per 2 enemy resources |
| JTL_131 | Turbolaser Salvo | Event | NEEDS-WORK | a friendly space unit deals power to each enemy in arena |
| JTL_132 | First Order Stormtrooper | Unit | NEEDS-WORK | On Attack/When Defeated: 1 indirect to a player |
| JTL_133 | Allegiant General Pryde | Unit | NEEDS-WORK | when indirect dmg to a unit: may defeat a non-unique upgrade |
| JTL_134 | General Hux | Unit | NEEDS-WORK | FO Raid 1 aura + Action: if FO played, draw |
| JTL_135 | Special Forces TIE Fighter | Unit | NEEDS-WORK | When Played: if opp more space, ready this |
| JTL_136 | Prototype TIE Advanced | Unit | VANILLA | blank |
| JTL_137 | Vonreg's TIE Interceptor | Unit | NEEDS-WORK | power-gated Overwhelm (4+) and Raid 1 (6+) |
| JTL_138 | Decimator of Dissidents | Unit | NEEDS-WORK | if dealt indirect dmg this phase, costs 1 less |
| JTL_139 | Dengar | Unit | NEEDS-WORK | Piloting; attached On Attack: 2/3 indirect to a player |
| JTL_140 | IG-2000 | Unit | NEEDS-WORK | Overwhelm + When Played: 1 dmg to up to 3 units |
| JTL_141 | IG-88 | Unit | NEEDS-WORK | while enemy damaged +3/+0 + Piloting; attached same |
| JTL_142 | Darth Vader | Unit | NEEDS-WORK | Piloting; attached On Attack: 1 dmg, chain on defeat |
| JTL_143 | Devastator | Unit | NEEDS-WORK | you assign all indirect damage you deal (passive) |
| JTL_144 | No Disintegrations | Event | NEEDS-WORK | dmg to non-leader = 1 less than its remaining HP |
| JTL_145 | BB-8 | Unit | NEEDS-WORK | Piloting; when played as upgrade pay 2 → ready a Resistance |
| JTL_146 | Massassi Tactical Officer | Unit | NEEDS-WORK | Action: attack with a Fighter, +2/+0 |
| JTL_147 | Black One | Unit | NEEDS-WORK | while upgraded +1/+0 + On Attack: if Poe, 1 dmg |
| JTL_148 | Frisk | Unit | NEEDS-WORK | Piloting; when played as upgrade defeat an upgrade ≤2 |
| JTL_149 | Red Squadron Y-Wing | Unit | NEEDS-WORK | On Attack: 3 indirect to defending player |
| JTL_150 | Biggs Darklighter | Unit | NEEDS-WORK | Piloting; attached gains keyword by host subtype |
| JTL_151 | Red Five | Unit | NEEDS-WORK | On Attack: may deal 2 to a damaged unit |
| JTL_152 | Tactical Heavy Bomber | Unit | NEEDS-WORK | On Attack: indirect = power; base damaged → draw |
| JTL_153 | Rebellious Hammerhead | Unit | NEEDS-WORK | When Played: dmg to a unit = cards in hand |
| JTL_154 | Profundity | Unit | NEEDS-WORK | Overwhelm + When Played/Defeated: discard race |
| JTL_155 | They Hate That Ship | Event | NEEDS-WORK | opp 2 TIEs readied; play a Vehicle −3 |
| JTL_156 | Trench Run | Event | NEEDS-WORK | attack a Fighter +4/+0 + granted deck-discard On Attack |
| JTL_157 | Relentless Firespray | Unit | NEEDS-WORK | On Attack: ready this, once per round |
| JTL_158 | Crackshot V-Wing | Unit | NEEDS-WORK | When Played: if no other Fighter, 1 dmg to self |
| JTL_159 | Determined Recruit | Unit | KEYWORD-ONLY | Piloting only |
| JTL_160 | Supporting Eta-2 | Unit | NEEDS-WORK | On Attack: may give a ground unit +2/+0 |
| JTL_161 | Captain Tarkin | Unit | NEEDS-WORK | friendly Vehicles +1/+0 + Overwhelm (aura) |
| JTL_162 | Droid Missile Platform | Unit | NEEDS-WORK | When Defeated: 3 indirect to a player |
| JTL_163 | AT-DP Occupier | Unit | NEEDS-WORK | cost −1 per damaged ground unit + Overwhelm |
| JTL_164 | Cham Syndulla | Unit | NEEDS-WORK | When Played: if opp more resources, top→resource |
| JTL_165 | Hunting Aggressor | Unit | NEEDS-WORK | indirect damage you deal increased by 1 (passive) |
| JTL_166 | Orbiting K-Wing | Unit | KEYWORD-ONLY | Saboteur |
| JTL_167 | Occupier Siege Tank | Unit | KEYWORD-ONLY | Grit |
| JTL_168 | Insurgent Saboteurs | Unit | NEEDS-WORK | Saboteur + On Attack: may defeat an upgrade |
| JTL_169 | Shadow Caster | Unit | NEEDS-WORK | when a friendly unit defeated: reuse all its When-Defeated |
| JTL_170 | War Juggernaut | Unit | NEEDS-WORK | +1/+0 per damaged unit + When Played: 1 dmg to any number |
| JTL_171 | Targeting Computer | Upgrade | NEEDS-WORK | attached gains "you assign all indirect dealt by this" |
| JTL_172 | Twin Laser Turret | Upgrade | NEEDS-WORK | attach Vehicle; granted On Attack: 1 dmg to up to 2 |
| JTL_173 | Fight Fire With Fire | Event | NEEDS-WORK | 3 dmg to a friendly + an enemy in same arena |
| JTL_174 | Hotshot Maneuver | Event | NEEDS-WORK | per On-Attack ability deal 2; then attack |
| JTL_175 | System Shock | Event | NEEDS-WORK | defeat a non-leader upgrade; 1 dmg to that unit |
| JTL_176 | Shoot Down | Event | NEEDS-WORK | 3 to a space unit; if defeated, 2 to a base |
| JTL_177 | Stay on Target | Event | NEEDS-WORK | attack a Vehicle +2/+0 + granted base-dmg→draw |
| JTL_178 | Face Off | Event | NEEDS-WORK | if no initiative taken, ready an enemy + a friendly |
| JTL_179 | Koiogran Turn | Event | NEEDS-WORK | ready a Fighter/Transport ≤6 power |
| JTL_180 | Piercing Shot | Event | NEEDS-WORK | defeat all Shields on a unit, 3 dmg to it |
| JTL_181 | Planetary Bombardment | Event | NEEDS-WORK | 8 indirect (12 if you control a Capital Ship) |
| JTL_182 | Rampart | Unit | NEEDS-WORK | doesn't ready in regroup unless power 4+ (passive) |
| JTL_183 | Zygerrian Starhopper | Unit | NEEDS-WORK | When Defeated: 2 indirect to a player |
| JTL_184 | Contracted Jumpmaster | Unit | KEYWORD-ONLY | Sentinel |
| JTL_185 | Hound's Tooth | Unit | NEEDS-WORK | vs exhausted not-entered-this-phase, deals combat dmg first |
| JTL_186 | Mist Hunter | Unit | NEEDS-WORK | On Attack: if Bounty Hunter/Pilot played, may draw |
| JTL_187 | Bossk | Unit | NEEDS-WORK | On Attack: exhaust defender + 1 dmg |
| JTL_188 | Moff Gideon | Unit | NEEDS-WORK | combat dmg to a base → that opp's units cost 1 more |
| JTL_189 | Boba Fett | Unit | NEEDS-WORK | Shielded + Piloting; when played as upgrade 1–2 dmg |
| JTL_190 | Techno Union Transport | Unit | KEYWORD-ONLY | Shielded |
| JTL_191 | Invincible | Unit | NEEDS-WORK | cost −1 if unique Separatist + when you deploy a leader: bounce ≤3 |
| JTL_192 | In Debt to Crimson Dawn | Upgrade | NEEDS-WORK | when attached readies: exhaust unless controller pays 2 |
| JTL_193 | I Have You Now | Event | NEEDS-WORK | attack with a Vehicle, prevent all dmg to it |
| JTL_194 | Heartless Tactics | Event | NEEDS-WORK | exhaust −2/−0; if 0 power non-leader, may bounce |
| JTL_195 | Cat and Mouse | Event | NEEDS-WORK | exhaust enemy; ready a ≤-power friendly in same arena |
| JTL_196 | Dagger Squadron Pilot | Unit | KEYWORD-ONLY | Piloting only |
| JTL_197 | Anakin Skywalker | Unit | NEEDS-WORK | Piloting; complete-attack may return this upgrade |
| JTL_198 | Fireball | Unit | NEEDS-WORK | Ambush + when regroup starts: 1 dmg to self |
| JTL_199 | Blade Squadron B-Wing | Unit | NEEDS-WORK | When Played: if another player 3+ exhausted, give Shield |
| JTL_200 | Shuttle Tydirium | Unit | NEEDS-WORK | On Attack: discard from deck; odd → may give exp |
| JTL_201 | Ahsoka Tano | Unit | NEEDS-WORK | When Played: opp discards; if unit, may exhaust a unit |
| JTL_202 | Black Squadron Scout Wing | Unit | NEEDS-WORK | when you play an upgrade on this: may attack +1/+0 |
| JTL_203 | Han Solo | Unit | NEEDS-WORK | Ambush + Piloting; when played as upgrade attack |
| JTL_204 | Home One | Unit | NEEDS-WORK | cost −3 if opp 3+ space + Ambush |
| JTL_205 | Commence Patrol | Event | NEEDS-WORK | discard pile card to bottom of deck → create X-Wing |
| JTL_206 | Fly Casual | Event | NEEDS-WORK | ready a Vehicle, can't attack bases this phase |
| JTL_207 | Jam Communications | Event | NEEDS-WORK | look at opp hand, discard an event |
| JTL_208 | Never Tell Me the Odds | Event | NEEDS-WORK | mill 3 each; dmg = # odd-cost milled |
| JTL_209 | It's a Trap | Event | NEEDS-WORK | if opp more space, ready each space unit you control |
| JTL_210 | The Mandalorian | Unit | NEEDS-WORK | when played as unit: exhaust up to 2 ground units (+ Piloting) |
| JTL_211 | Independent Smuggler | Unit | NEEDS-WORK | Raid 1 + Piloting; attached gains Raid 1 |
| JTL_212 | Republic Y-Wing | Unit | VANILLA | blank |
| JTL_213 | Sidon Ithano | Unit | NEEDS-WORK | when played as unit: attach to an enemy Vehicle |
| JTL_214 | X-34 Landspeeder | Unit | KEYWORD-ONLY | Ambush |
| JTL_215 | BoShek | Unit | NEEDS-WORK | Piloting; when played as upgrade discard 2, return odd |
| JTL_216 | Contracted Hunter | Unit | NEEDS-WORK | Ambush + when regroup starts: defeat this |
| JTL_217 | Death Space Skirmisher | Unit | NEEDS-WORK | When Played: if another space unit, may exhaust a unit |
| JTL_218 | Guerilla Soldier | Unit | NEEDS-WORK | When Played: 3 indirect; if base dmg, ready this |
| JTL_219 | Rafa Martez | Unit | NEEDS-WORK | When Played/On Attack: 1 dmg to friendly + ready a resource |
| JTL_220 | Skyway Cloud Car | Unit | NEEDS-WORK | When Defeated: may bounce a non-leader ≤2 power |
| JTL_221 | Stolen AT-Hauler | Unit | NEEDS-WORK | When Defeated: opp may play this from discard free |
| JTL_222 | Kimogila Heavy Fighter | Unit | NEEDS-WORK | When Played: 3 indirect; exhaust units damaged this way |
| JTL_223 | Razor Crest | Unit | NEEDS-WORK | when a Pilot attaches: may bounce a small/exhausted unit |
| JTL_224 | Shadowed Hover Tank | Unit | KEYWORD-ONLY | Sentinel |
| JTL_225 | Corporate Light Cruiser | Unit | KEYWORD-ONLY | Ambush + Raid 1 |
| JTL_226 | Radiant VII | Unit | NEEDS-WORK | enemy non-leaders −1/−0 per dmg (aura) + When Played: 5 indirect |
| JTL_227 | Superheavy Ion Cannon | Upgrade | NEEDS-WORK | attach Capital/Transport; granted On Attack: exhaust→indirect |
| JTL_228 | Barrel Roll | Event | NEEDS-WORK | attack space; after, may exhaust a space unit |
| JTL_229 | Diversion | Event | NEEDS-WORK | give a unit Sentinel this phase |
| JTL_230 | Electromagnetic Pulse | Event | NEEDS-WORK | 2 dmg to a Droid/Vehicle and exhaust it |
| JTL_231 | Punch It | Event | NEEDS-WORK | attack with a Vehicle +2/+0 |
| JTL_232 | Jump to Lightspeed | Event | NEEDS-WORK | return a friendly space unit + upgrades; replay copy free |
| JTL_233 | Sweep the Area | Event | NEEDS-WORK | return up to 2 units ≤3 combined cost in same arena |
| JTL_234 | Torpedo Barrage | Event | NEEDS-WORK | 5 indirect to a player |
| JTL_235 | Commandeer | Event | NEEDS-WORK | take control of a Vehicle ≤6 no Pilot; ready; return next regroup |
| JTL_236 | Indoctrinated Conscript | Unit | KEYWORD-ONLY | Piloting only |
| JTL_237 | TIE Bomber | Unit | NEEDS-WORK | On Attack: 3 indirect to defending player |
| JTL_238 | Sith Trooper | Unit | NEEDS-WORK | On Attack: +1/+0 per damaged unit defender controls |
| JTL_239 | TIE Dagger Vanguard | Unit | NEEDS-WORK | When Played: may deal 2 to a damaged unit |
| JTL_240 | Fett's Firespray | Unit | NEEDS-WORK | When Played/On Attack: 1 indirect (2 if you control Boba) |
| JTL_241 | Rogue-class Starfighter | Unit | KEYWORD-ONLY | Sentinel |
| JTL_242 | Shuttle ST-149 | Unit | NEEDS-WORK | Shielded + When Played/Defeated: steal a token upgrade |
| JTL_243 | Quasar TIE Carrier | Unit | NEEDS-WORK | On Attack: create a TIE |
| JTL_244 | There Is No Escape | Event | NEEDS-WORK | up to 3 units lose abilities this round |
| JTL_245 | R2-D2 | Unit | NEEDS-WORK | Piloting (0); plays on a unit w/ a Pilot; raises Pilot capacity |
| JTL_246 | Hopeful Volunteer | Unit | KEYWORD-ONLY | Piloting only |
| JTL_247 | Resistance X-Wing | Unit | NEEDS-WORK | while it has a Pilot, +1/+1 (passive) |
| JTL_248 | Dilapidated Ski Speeder | Unit | NEEDS-WORK | When Played: 3 dmg to self |
| JTL_249 | Millennium Falcon | Unit | NEEDS-WORK | extra Pilot allowed + +1/+0 per Pilot on it |
| JTL_250 | Sabine's Masterpiece | Unit | NEEDS-WORK | On Attack: aspect-keyed effects (Vig/Cmd/Agg/Cun) |
| JTL_251 | Jedi Light Cruiser | Unit | VANILLA | blank |
| JTL_252 | Tantive IV | Unit | NEEDS-WORK | Sentinel + When Played: create an X-Wing |
| JTL_253 | Coordinated Front | Event | NEEDS-WORK | may give a ground unit +2/+2 this phase |
| JTL_254 | Dedicated Wingmen | Event | NEEDS-WORK | create 2 X-Wing tokens |
| JTL_255 | Sullustan Spacer | Unit | KEYWORD-ONLY | Piloting only |
| JTL_256 | Swarming Vulture Droid | Unit | NEEDS-WORK | +1/+0 per other Swarming Vulture Droid (aura; 15-copy is deckbuild) |
| JTL_257 | Flanking Fang Fighter | Unit | NEEDS-WORK | while you control another Fighter, gains Raid 2 (conditional) |
| JTL_258 | Corellian Freighter | Unit | KEYWORD-ONLY | Sentinel |
| JTL_259 | Retrofitted Airspeeder | Unit | NEEDS-WORK | Ambush + can attack space units, −1/−0 vs space |
| JTL_260 | Death Star Plans | Upgrade | NEEDS-WORK | when attached attacked: attacker steals this + granted cost-down |
| JTL_261 | Attack Run | Event | NEEDS-WORK | attack with 2 space units one at a time |
| JTL_262 | Evasive Maneuver | Event | NEEDS-WORK | exhaust a unit |
| JTL_T01 | TIE Fighter | Token Unit | VANILLA | blank |
| JTL_T02 | X-Wing | Token Unit | VANILLA | blank |
| JTL_T03 | Experience | Token Upgrade | VANILLA | blank (standard token) |
| JTL_T04 | Shield | Token Upgrade | KEYWORD-ONLY | standard Shield token (engine core) |

## Tallies
- **VANILLA**: 069, 095, 136, 212, 251, T01, T02, T03 (8)
- **No-op BASE**: 019, 020, 022, 023, 026, 027, 029, 030, 031 (9)
- **KEYWORD-ONLY**: 058, 061, 064, 065, 068, 108, 110, 112, 114, 118, 159, 166, 167, 184, 190, 196, 214, 224, 225, 236, 241, 246, 255, 258, T04 (25)
- **NEEDS-WORK**: everything else (224) — incl. 18 Leaders + 4 setup/deckbuild bases (021/024/025/028, flagged possibly-out-of-scope)

Auto-wired ("done by classification") = 8 + 9 + 25 = **42**. Needs-work = **224**.
