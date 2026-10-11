# DOT — Card Implementation Plan

176 base cards (310 dictionary entries including 134 variants): 179 Minion, 71 Event, 38 Asset,
12 Monster, 10 Location.

**90 needs-work · 45 implemented · 35 vanilla · 6 keyword-only.**

Generated from `.claude/skills/hellbreaksim-generate-set-doc/inventory.sh DOT` on 2026-09-16, after
a full regeneration. Driven by `hellbreaksim-implement-set-plan`; each card goes through
`hellbreaksim-implement-card`.

> ⚠ **Four printings are unlinked and count as base cards in error** — DOT_273→DOT_073,
> DOT_433→DOT_129, DOT_439→DOT_001, DOT_440→DOT_006. Add them to `baseCards` in
> `HellbreakSim/CardData/ReviewedCardFaces.json`. DOT_273 is a live bug: a deck containing that
> printing resolves to nothing and plays with no abilities.
>
> ⚠ **Three dead ability rows** sit on variant IDs and can never fire: DOT_245, DOT_436, DOT_437.

### Already Done
DOT_001, DOT_006, DOT_013, DOT_015, DOT_016, DOT_020, DOT_023, DOT_025, DOT_026, DOT_027, DOT_028, DOT_030, DOT_031, DOT_032, DOT_034, DOT_035, DOT_036, DOT_037, DOT_039, DOT_040, DOT_041, DOT_042, DOT_044, DOT_045, DOT_049, DOT_052, DOT_053, DOT_068, DOT_070, DOT_076, DOT_080, DOT_081, DOT_082, DOT_083, DOT_084, DOT_085, DOT_086, DOT_087, DOT_088, DOT_098, DOT_104, DOT_105, DOT_106, DOT_108, DOT_109, DOT_110, DOT_112, DOT_114, DOT_115, DOT_119, DOT_122, DOT_127, DOT_128, DOT_129, DOT_134, DOT_135, DOT_141, DOT_142, DOT_145, DOT_146, DOT_147, DOT_151, DOT_152, DOT_159, DOT_160, DOT_161, DOT_165, DOT_169, DOT_171, DOT_174, DOT_180, DOT_182, DOT_183, DOT_184, DOT_189, DOT_191, DOT_192, DOT_196, DOT_206, DOT_207, DOT_208, DOT_209, DOT_273, DOT_433, DOT_439, DOT_440

## Phase 1 — Played triggers (autonomous)

The `Played` macro, the best-precedented dispatch point in the set (14 existing rows). Start here: it is the shape every later phase builds on.

- [ ] **Batch 1.1 — DOT_187, DOT_029, DOT_185**
  - DOT_187 Hooded Figure (Minion): Played — If you control a Demon, gain 1 malice.
  - DOT_029 Youthful Vampire (Minion): Played — If you control this location, draw 1.
  - DOT_185 Marcus Abbott, No Longer Afraid (Minion): Stealth. Played — If another Abbott minion is here, gain 1 blood.
- [ ] **Batch 1.2 — DOT_199, DOT_107, DOT_158**
  - DOT_199 Dark Avenger (Minion): Malicious 2. Played — You may terrify a minion here with 2 or less remaining health.
  - DOT_107 Pippit, Loyal Labrador (Minion): Played — You may move 1 malice from the opponent's side of this location to your side.
  - DOT_158 Body Snatcher (Minion): Played — You may add a minion in a crypt to its owner's hand.
- [ ] **Batch 1.3 — DOT_155, DOT_156, DOT_073**
  - DOT_155 Groundskeeper (Minion): Played — You may discard 1 card from your hand and collect its resource icons. Jumpscare — Prevent the next 1 damage d
  - DOT_156 Lost Hunter (Minion): Played — You may kill an Upgrade here. Jumpscare — Pay 1 malice: Kill an Upgrade.
  - DOT_073 Dr. Seward, Diligent Psychiatrist (Minion): Played — You may ready or exhaust an asset. Jumpscare — Ready or exhaust an asset.
- [ ] **Batch 1.4 — DOT_197, DOT_190, DOT_201**
  - DOT_197 Corrupt Councilman (Minion): Played — You may choose a player. That player discards 1 card from their hand.
  - DOT_190 Regan Abbott, Invader's Bane (Minion): Played — Search the top 6 cards of your deck for a Tactic, reveal it, and draw it. Put the rest on the bottom of your 
  - DOT_201 Evelyn Abbot, Resilient Mother (Minion): Played — Search the top 9 cards of your deck for an Abbott, reveal it, and draw it. Put the rest on the bottom of your
- [ ] **Batch 1.5 — DOT_116, DOT_111, DOT_150**
  - DOT_116 John Harker, Fearful Fiance (Minion): Played — Choose one: Kill an asset; deal 1 damage to a minion here; or remove up to 2 malice from this location.
  - DOT_111 Dock Bandit (Minion): Played — You may deal 2 damage to a minion here unless its controller gives you 1 blood.
  - DOT_150 Stitched Corpse (Minion): Played — Kill this card unless you banish 2 minions from a crypt.
- [ ] **Batch 1.6 — DOT_210**
  - DOT_210 Spellbook (Asset): Played — If you control a Witch, gain 1 blood and 1 malice. Action — Pay 1 malice and exhaust this card: Search the to

## Phase 2 — Static value modifiers (autonomous)

Pure `CombatModifier` / `SchemeModifier` / `KeywordModifier` / `TraitModifier` / `PlayCostModifier` / `LocationThresholdModifier` deltas — no triggers, no decisions. The DOT_171 rows are the template.

- [ ] **Batch 2.1 — DOT_117, DOT_113, DOT_118**
  - DOT_117 Menacing Death Angel (Minion): While your monster is unleashed, this card gets +2 combat.
  - DOT_113 Stalking Death Angel (Minion): This location's malice threshold is increased by 2.
  - DOT_118 Prowling Death Angel (Minion): Each allied Death Angel gains Bloodlust 1.
- [ ] **Batch 2.2 — DOT_121, DOT_148, DOT_004**
  - DOT_121 Aggressive Death Angel (Minion): Each allied Death Angel gains Fierce 1.
  - DOT_148 Karl, Vile Henchman (Minion): While 5 or more cards are in your crypt, this card gets +1 combat and +1 prowl. While 10 or more cards are in your cry
  - DOT_004 Jaws, Apex Predator (Monster): While an enemy minion is damaged, this card gets +1 prowl and +1 foresee.
- [ ] **Batch 2.3 — DOT_168, DOT_125**
  - DOT_168 Angry Mob (Minion): Guardian. This card costs 1 blood less to play for each unleashed monster.
  - DOT_125 Rampaging Death Angel (Minion): Each allied Death Angel gains Overkill. When this card kills a minion by combat damage as an attacker, you may kill an

## Phase 3 — Jumpscare (autonomous)

`JumpscareUsed`, already carrying 17 rows. Most are a play-for-0 or a small effect; the destination helpers exist.

- [ ] **Batch 3.1 — DOT_096, DOT_139, DOT_221**
  - DOT_096 Breaking and Entering (Event): Remove up to 3 malice from a location. Jumpscare — Play this card for 0 blood.
  - DOT_139 Pulverize (Event): Kill an asset. Jumpscare — Play this card for 0 blood.
  - DOT_221 Scavenge Supplies (Event): Add this card to your vault and gain 1 malice. Jumpscare — Gain 1 blood or 1 malice.
- [ ] **Batch 3.2 — DOT_097, DOT_178, DOT_102**
  - DOT_097 Get to the Point (Event): Deal 2 damage to a minion. Jumpscare — Pay 2 malice: Play this card for 0 blood.
  - DOT_178 Defenestration (Event): Move an enemy minion. Deal 1 damage to it. Jumpscare — Pay 2 malice: Play this card for 0 blood.
  - DOT_102 Shock (Event): Deal 1 damage to each minion. Jumpscare — Pay 1 malice: Deal 1 damage to a minion.
- [ ] **Batch 3.3 — DOT_140, DOT_219, DOT_056**
  - DOT_140 Circling the Prey (Event): If an enemy minion is damaged, play 2 Shark tokens. Jumpscare — Pay 1 malice: Play 1 Shark token.
  - DOT_219 Intimidate (Event): Terrify a minion that costs less than an allied minion at the same location. Jumpscare — Pay 2 malice: Play this card 
  - DOT_056 Transform (Event): Flip your monster. Then, if it's lurking, play 1 Bat token and ready that token. Jumpscare — Pay 2 malice: Play this c
- [ ] **Batch 3.4 — DOT_143, DOT_205**
  - DOT_143 Strength In Numbers (Event): If you control a Death Angel, this card costs 1 blood less to play. Each minion you control gets +1 combat this phase.
  - DOT_205 Lee Abbott, Protective Father (Minion): Guardian. Stealth. This card gets +1 combat and +1 prowl for each damage on it. Jumpscare — A minion gains Guardian an

## Phase 4 — Attachments (autonomous)

The layer built for DOT_171. Ordered so each card adds ONE new capability to the shared helpers.

- [ ] **Batch 4.1 — DOT_211, DOT_050, DOT_133**
  - DOT_211 Hessian Sword (Asset): Attach to a character. It gets +1 combat. If it's a Demon, it gets +1 prowl.
  - DOT_050 Dracula's Cape, Fine Attire (Asset): Attach to a character. It gets +1 health. If you control Dracula, this card costs 1 blood less to play.
  - DOT_133 Razor-Sharp Claws (Asset): Attach to a character. Attached card gains Fierce 1. When this card is attached to a minion, draw 1.
- [ ] **Batch 4.2 — DOT_047, DOT_095, DOT_131**
  - DOT_047 Hockey Mask (Asset): Attach to a character. When this card is attached to a Human, draw 1. Attached card gains Bloodlust 1.
  - DOT_095 Frozen With Fear (Asset): Attach to a minion. Attached card can't ready. Jumpscare — Pay 2 malice: Play this card for 0 blood.
  - DOT_131 Machete (Asset): Attach to a character. Attached card gets +1 combat and gains: Attack — You may remove 1 malice from this location.
- [ ] **Batch 4.3 — DOT_091, DOT_212, DOT_130**
  - DOT_091 M1 Rifle (Asset): Attach to a character. Attached card gains: Attack — You may pay 2 malice. If you do, deal 2 damage to a minion here.
  - DOT_212 Warding Sign (Asset): Attach to a character. When attached card would be dealt damage, prevent that damage. If you do, kill this card.
  - DOT_130 Rusty Bear Trap (Asset): Attach to a location. When a minion enters this location, you may reveal the top card of your deck. If the revealed ca

## Phase 5 — Action abilities (autonomous)

`ActivateAbility` — the ability index is the player-facing menu slot, so mind the `:N` ordering.

- [ ] **Batch 5.1 — DOT_132, DOT_172, DOT_175**
  - DOT_132 Oxygen Tank (Asset): Action — Exhaust a Weapon: Discard 1 card from your deck and 1 card from an opponent's deck. If those cards have the s
  - DOT_172 Homunculus Casket (Asset): Action — Pay 1 malice and exhaust this card: Play 1 Homunculus token. Then, if you control 7 or more of those tokens, 
  - DOT_175 Tree of the Dead, Demonic Portal (Asset): Action — Exhaust this card: Discard 1 card from a deck. You may pay 1 malice. If you do, play that card, ignoring its 
- [ ] **Batch 5.2 — DOT_022, DOT_019, DOT_163**
  - DOT_022 Sleepy Hollow (Location): Action — Pay 1 malice: Move an exhausted allied minion here. Limit once per round per player.
  - DOT_019 Lennox Steel Foundry (Location): Action — Attack with a minion here. If it shares a trait with another allied character here, it gets +1 combat this at
  - DOT_163 Gothic Gargoyle (Minion): Action — If this card entered your crypt this phase, play it from your crypt, paying its cost.
- [ ] **Batch 5.3 — DOT_005**
  - DOT_005 Collosal Death Angel, Sound Hunter (Monster): Action — If a minion attacked a character you control this phase, discard 1 card from your deck and 1 card from an opp

## Phase 6 — Scheme and scheme icons (autonomous)

`SchemeStarted` and `SchemeIcon`. DOT_008/DOT_011 hook the FORESEE icon specifically.

- [ ] **Batch 6.1 — DOT_007, DOT_009, DOT_193**
  - DOT_007 Dracula, Lord of the Night (Monster): Scheme — If you triggered a Played ability this phase, you may play 1 Bat token for free.
  - DOT_009 Headless Horseman, The Legend of Sleepy Hollow (Monster): Scheme — If an enemy minion left play this phase, you may add 1 malice to a location.
  - DOT_193 Katrina Van Tassel, Strong-Willed Witch (Minion): Scheme — If you played a Spell this phase, you may add 1 malice to a location. Jumpscare — If you played a Spell this 
- [ ] **Batch 6.2 — DOT_003, DOT_011, DOT_008**
  - DOT_003 Jason, Crystal Lake Killer (Monster): Scheme — Search the top 6 cards of your deck for an Item, reveal it, and draw it. Put the rest on the bottom of your d
  - DOT_011 Abandoned Watchtower (Location): When you foresee for the first time each round, you may draw 1, then discard 1 card from your hand.
  - DOT_008 The Bride, Kissed by Lightning (Monster): When you look at 1 or more cards with foresee, you may discard 1 of them.

## Phase 7 — Combat and kill triggers (autonomous)

`DamageDealt` and `MinionKilled` listeners; the payload is already normalized across attacks, retaliation, effects and monster damage.

- [ ] **Batch 7.1 — DOT_126, DOT_166, DOT_124**
  - DOT_126 Collosal Death Angel, Always Listening (Minion): Stealth. When this card kills a minion by combat damage, you may ready this card. Limit once per round.
  - DOT_166 Brom Van Brunt, Arrogant Suitor (Minion): First Strike. When this card deals combat damage to a minion, you may terrify that minion.
  - DOT_124 Emmett, Grieving Survivor (Minion): Stealth. When a unique allied Human minion enters this location, including this card, you may deal 1 damage to a minio
- [ ] **Batch 7.2 — DOT_198**
  - DOT_198 Daredevil, Infernal Steed (Minion): Attack — Discard 1 card from a deck. If that card is a minion, a Demon here gets +1 combat and gains Overkill this pha

## Phase 8 — Round boundary (autonomous)

`RefreshReady` — two cards, both accumulating state on a location or on themselves.

- [ ] **Batch 8.1 — DOT_012, DOT_173**
  - DOT_012 Abbott Family Farm (Location): At the start of Refresh, if 2 or more allied Human minions are here, add 1 malice to this card. Then, if 2 or more of 
  - DOT_173 Pact with the Devil (Asset): At the start of Refresh, put 1 blood on this card. Then, deal damage equal to the blood on it to your monster. Action 

## Phase 9 — One-shot events (autonomous)

Events whose whole text is an immediate effect. No new dispatch point, but several need target pools and `await` prompts.

- [ ] **Batch 9.1 — DOT_061, DOT_064, DOT_063**
  - DOT_061 Cold Blooded Murder (Event): Kill a minion.
  - DOT_064 We Belong Dead (Event): Kill all minions.
  - DOT_063 Pagan Festival (Event): Malicious 2. Ready up to 1 minion at each location.
- [ ] **Batch 9.2 — DOT_215, DOT_059, DOT_137**
  - DOT_215 Silent Patrol (Event): Add 2 malice to a location with a ready allied minion there.
  - DOT_059 Smile, You Sonofa- (Event): Kill a minion with remaining health equal to or less than the combat of an allied character at the same location.
  - DOT_137 Clash (Event): Choose an allied minion and an enemy minion at the same location. They deal damage to each other equal to their combat
- [ ] **Batch 9.3 — DOT_181, DOT_223, DOT_222**
  - DOT_181 Murder Begets Murder (Event): Kill an allied non-token minion. If you do, kill another minion there.
  - DOT_223 To Hell and Back (Event): Banish a minion, then put it at a location under its owner's control and exhaust it. If it's a Demon, you may ready it
  - DOT_222 Amplify Feedback (Event): Reveal cards from the top of an opponent's deck until 2 non-minions are revealed. Deal damage to a minion equal to the
- [ ] **Batch 9.4 — DOT_224**
  - DOT_224 Summoning Ritual (Event): If you control a Witch, gain 2 blood or 2 malice. Search the top 9 cards of your deck for up to 2 minions with a total

## Phase 10 — Side-restricted actions (Lurking / Unleashed) (pair-programmed)

These gate on the monster's side and several grant a temporary attack. Needs a decision on how a side-restricted Action is offered and how 'gains Attack — …' is modelled.

- [ ] **Batch 10.1 — DOT_062, DOT_055, DOT_060**
  - DOT_062 Hypnotic Gaze (Event): Lurking Action — Take control of a minion that costs 3 blood or less.
  - DOT_055 Savage Blow (Event): Unleashed Action — Attack with a character. It gets +1 combat this attack for each location you control. Jumpscare — P
  - DOT_060 Dracula's Fury (Event): Lurking Action — You may flip your monster. You may attack with a Dracula. It gets +2 combat this attack. Jumpscare — 
- [ ] **Batch 10.2 — DOT_138, DOT_144, DOT_092**
  - DOT_138 Eviscerate (Event): Unleashed Action — Attack with a character. It gets +1 combat this attack, or +2 combat this attack instead if it's a 
  - DOT_144 Wrath of Jaws (Event): Unleashed Action — Attack with a Jaws. It gets +3 combat this attack. Jumpscare — A Jaws gets +2 combat this phase.
  - DOT_092 Orca, Timeworn Trawler (Asset): Lurking Action — Pay 1 malice and exhaust this card: Attack with a character. It gets +2 combat this attack. If it is 
- [ ] **Batch 10.3 — DOT_179**
  - DOT_179 Piercing Scream (Event): Unleashed Action — Attack with a character. For this attack, it gains ‘Attack — Each enemy minion here gets -1 combat 

## Phase 11 — New dispatch points (pair-programmed)

Each needs a macro that does not exist yet, or a moment the engine does not currently fire. A schema change is a design decision — raise it before writing.

- [ ] **Batch 11.1 — DOT_157, DOT_176, DOT_188**
  - DOT_157 Strange Apparition (Minion): When this card is discarded from your hand or deck, you may gain 1 malice.
  - DOT_176 Cosmic Ray Diffuser (Asset): When this card is discarded from your hand or deck, you may deal 1 indirect damage to a player. Action — Exhaust this 
  - DOT_188 Malevolent Mist (Minion): Killed — If you took control of a location this phase, you may add this card to its owner's hand.
- [ ] **Batch 11.2 — DOT_167, DOT_149, DOT_153**
  - DOT_167 Dracula, Blood is Life (Minion): Attack or Moved — You may deal 1 damage to a minion here. If it's a Human, gain 1 blood. When this card deals combat d
  - DOT_149 Ludwig, Dastardly Goon (Minion): Played or Moved — You may have a player draw 2, then they discard 2 cards from their hand.
  - DOT_153 Bloodhound (Minion): Played or Moved — Discard 1 card from a deck. If it's a minion, gain 1 malice. When another minion here moves, you may
- [ ] **Batch 11.3 — DOT_204, DOT_024**
  - DOT_204 Spectral Assassin (Minion): Overkill. When you take control of this location, you may deal 1 damage to a character here.
  - DOT_024 Western Woods (Location): Take Control — Draw 1 or terrify a minion here with 2 or less remaining health.

## Phase 12 — Multi-player sequencing (pair-programmed)

Interactive across both seats — an opponent decision inside your ability.

- [ ] **Batch 12.1 — DOT_136, DOT_203**
  - DOT_136 Shark in the Pond (Event): Lurking Action — Terrify an enemy minion unless its controller says ‘it's a prank.’ If they do, play a non-unique Shar
  - DOT_203 Lady Van Tassel, Vengeful Witch (Minion): Malicious 2. The first Spell you play each round costs 1 blood less to play. Scheme — If you played a Spell this phase

