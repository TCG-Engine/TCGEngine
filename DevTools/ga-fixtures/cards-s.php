<?php
/**
 * Fixtures for cards starting with 'S', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Scars of Old: Draw + discard + buff counters on damaged allies ---
// Scars of Old's post-discard buff (ScarsOfOldBuffDamagedAllies in
// GrandArchiveSim/Custom/GameLogic.php) requires (a) a WARRIOR Class Bonus — no level 0
// starting champion has a class other than SPIRIT — and (b) an already-damaged ally on the
// field to actually receive the buff counter. Both are seeded directly since neither is
// reachable through a fresh pregame-only board.
// Notes carried over from the original definition:
//   - Nameless Champion (WARRIOR) - Class Bonus source
//   - Dungeon Guide, pre-damaged - buff target
//   - Play Scars of Old (2 reserve — reserve cost is a "choose a card from myHand"
//   - MZCHOOSE, not a myField pick)
//   - Pass remaining prompts
//   - Scars of Old's draw+discard: discard a card, which then triggers the buff-damaged-
//   - allies step.
gaScenario('scars-of-old-draw-discard')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Scars of Old
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
4 Windslice
4 Windslice
4 Windslice
4 Windslice
DECK
    )
    ->seed('myField', '#LahboNoSRx')
    ->seed('myField', 'Dungeon Guide', properties: ['Damage' => 2])
    ->playSlot(5)->pay(2)->pass(2)->pay(1)
    ->tested('Scars of Old')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Field-2', 'Counters', '{"buff":1}')
    ->expectProperty('p1Hand-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Hand-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-2', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-3', 'CardID', 'lD0sK81PZT')
    ->expectProperty('p1Graveyard-0', 'CardID', 'lD0sK81PZT')
    ->expectProperty('p1Graveyard-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-0', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Memory-1', 'CardID', 'n8wyfG9hbY')
    ->expectZoneCount('myHand', 4, 1)
    ->expectZoneCount('myGraveyard', 2, 1)
    ->expectZoneCount('myMemory', 2, 1)
    ->register();

// --- Sabela, Gossamer Penance: WARRIOR Class Bonus On Enter — recur a banished Sword regalia ---
// Sabela is a CRUX (advanced element) UNIQUE ALLY, so — like Luxem Sight above — the starting
// champion's Subcards must be patched to unlock element access. Lorraine, Crux Knight (a real
// WARRIOR/CRUX champion) is used for the patch AND seeded directly onto the field, so the
// same card also satisfies the WARRIOR Class Bonus that Sabela's On Enter ability requires
// (IsClassBonusActive checks CHAMPION-type objects physically on the field, not lineage).
// Clarent, Sword of Peace (a real REGALIA,WEAPON with the SWORD subtype and memory cost 1) is
// seeded into the banishment as the card On Enter recurs onto the field. The "On Leave:
// sacrifice each regalia with a bond counter" half of the card is not exercised here — it
// requires removing Sabela from the field, which needs its own removal scaffolding — so this
// fixture only covers the On Enter half.
// Play Sabela (myHand-7 with this deck/seed — verified live via DevTools discovery harness),
// pay her 3-reserve cost (no discount applies), then choose the banished Clarent for the On
// Enter ability.
// Notes carried over from the original definition:
//   - Lorraine, Crux Knight (WARRIOR/CRUX champion) - Class Bonus source
//   - Clarent, Sword of Peace (REGALIA,WEAPON,SWORD, memory cost 1) - On Enter target
//   - CRUX lineage/element unlock
gaScenario('sabela-gossamer-penance-enter')
    ->standardDeck('Sabela, Gossamer Penance')
    ->seed('myField', 'Lorraine, Crux Knight')
    ->seed('myBanish', 'Clarent, Sword of Peace')
    ->champion(lineage: ['Lorraine, Crux Knight'])
    ->seed('myHand', 'Sabela, Gossamer Penance')
    ->castSpell('Sabela, Gossamer Penance', pay: 3, target: 'myBanish-0')
    ->register();

// --- Shizun of the Ash: On Enter — optional discard to draw ---
// Shizun's element is FIRE (a basic element, matching the "Spirit of Fire" starting
// champion), so no lineage patch is needed here. Only the always-available On Enter "you may
// discard a card, if you do draw a card" half is covered — the [Kongming Bonus] REST ability
// is gated behind a specific champion identity plus the separate "Shifting Currents" facing
// mechanic, which is out of scope for this fixture.
// Play Shizun (myHand-0 with this deck/seed — verified live via DevTools discovery harness),
// pay her 2-reserve cost, answer YES to the optional discard, then choose a card to discard.
gaScenario('shizun-of-the-ash-discard-draw')
    ->standardDeck('Shizun of the Ash')
    ->playSlot(0)->pay(2)->answer(1, 'YES')->pay(1)
    ->tested('Shizun of the Ash')
    ->register();

// --- Spirit Blade: Infusion: combat-damage discount + grant power/on-hit-draw to a Sword weapon ---
// Spirit Blade: Infusion is a CRUX (advanced element) ACTION card, so — like Luxem Sight and
// Sabela above — the starting champion's Subcards are patched with a real WARRIOR/CRUX
// champion (Lorraine, Crux Knight) to unlock element access; the same card's WARRIOR class
// isn't needed here (this card's discount is combat-based, not class-based), it's reused
// purely for the CRUX unlock. The discount (activationCostModifierAbilities
// ["CgyJxpEgzk:0"]) requires GlobalEffectCount($player, "CHAMP_DEALT_COMBAT_DMG") > 0, which
// is normally set by TrackChampionCombatDamage() when a champion deals real combat damage —
// set it directly via AddGlobalEffects() rather than scripting a full attack sequence, since
// this fixture is about the card's own cost/effect logic, not the combat subsystem. Clarent,
// Sword of Peace is seeded onto the field as the only legal Sword weapon target.
// Play Spirit Blade: Infusion (myHand-7 with this deck/seed — verified live via DevTools
// discovery harness). With the discount active, activation costs 0 reserve: FSM play -> the
// player's own fast-action response window -> target choice (Clarent is the only legal Sword
// weapon, so no opponent response window appears).
// Notes carried over from the original definition:
//   - Clarent, Sword of Peace (WEAPON,SWORD) - effect target
//   - Discount condition
//   - CRUX lineage/element unlock
gaScenario('spirit-blade-infusion-combat-discount')
    ->standardDeck('Spirit Blade: Infusion')
    ->seed('myField', 'Clarent, Sword of Peace')
    ->globalEffect('CHAMP_DEALT_COMBAT_DMG')
    ->champion(lineage: ['Lorraine, Crux Knight'])
    ->seed('myHand', 'Spirit Blade: Infusion')
    ->castSpell('Spirit Blade: Infusion', window: 'me', target: 'myField-1')
    ->register();

// --- Stocked Outpost: On Enter — draw a card into memory ---
// Stocked Outpost is a NORM DOMAIN card (a siegeable permanent that materializes onto the
// field like any other permanent type — there's no separate domain zone), so no element
// unlock is needed. Only the always-available On Enter "draw a card into your memory" half is
// covered — "On Destroy: if it's an opponent's turn, that opponent draws a card into their
// memory" requires reducing its durability to 0 via siege combat damage, which is out of
// scope for this fixture.
// Play Stocked Outpost (myHand-2 with this deck/seed — verified live via DevTools discovery
// harness), pay its 2-reserve cost (each reserve payment itself moves a hand card into
// memory), then confirm the On Enter draw adds one more.
gaScenario('stocked-outpost-enter-draw-memory')
    ->standardDeck('Stocked Outpost')
    ->playSlot(2)->pay(2)
    ->tested('Stocked Outpost')
    ->expectZoneCount('myMemory', 1, 1, 'Baseline memory count after only the first reserve payment, before Stocked Outpost resolves', step: 2)
    ->expectDqEmpty()
    ->expectZoneCount('myMemory', 3, 1, 'On Enter draws one more card into memory on top of the second reserve payment, which also moves a card there')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":4}', 'Stocked Outpost enters the field as a siegeable permanent with 4 durability counters')
    ->register();

// --- Seed of Nature: enters rested, On Enter grants +2 level ---
// Seed of Nature is a TERA (advanced element) REGALIA,ITEM card, so the starting champion's
// Subcards are patched with a real TERA champion (Kongming, Fel Eidolon) to unlock element
// access. Its memory cost (0) needs no floating-payment setup. On Enter sets the dmfoA7jOjy
// global effect (the champion's +2 level until end of turn). Only the base On Enter is
// covered; the [Class Bonus] REST+banish repeat of the same buff is out of scope.
// Notes carried over from the original definition:
//   - TERA lineage/element unlock
gaScenario('seed-of-nature-enter-level-buff')
    ->standardDeck('Seed of Nature')
    ->readyFor('Seed of Nature')
    ->seed('myHand', 'Seed of Nature')
    ->playSlot(5)
    ->tested('Seed of Nature')
    ->register();

// --- Summon Sentinels: summon two Automaton Drone tokens with buff counters ---
// Summon Sentinels is a NEOS (advanced element) ACTION card, so the starting champion's
// Subcards are patched with a real NEOS champion. Only the base "summon two tokens" effect is
// covered; the [Class Bonus] per-domain discount isn't exercised (no domains are seeded),
// meaning the full 4-reserve cost is paid.
// Notes carried over from the original definition:
//   - NEOS lineage/element unlock (Tonoris, Creation's Will)
gaScenario('summon-sentinels-drone-tokens')
    ->standardDeck('Summon Sentinels')
    ->readyFor('Summon Sentinels')
    ->seed('myHand', 'Summon Sentinels')
    ->castSpell('Summon Sentinels', pay: 4)
    ->expectZoneCount('myMemory', 4, 1, 'The full 4-reserve cost is paid, moving 4 hand cards into memory')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'The first summoned Automaton Drone token has a buff counter')
    ->expectProperty('p1Field-2', 'Counters', '{"buff":1}', 'The second summoned Automaton Drone token has a buff counter')
    ->expectDqEmpty()
    ->register();

// --- Swerving Spring: prevent the next 2 damage to target unit ---
// Swerving Spring's element is NORM, so no lineage patch is needed. Only the base always-
// available prevention is covered; the [Class Bonus] preparation-counter clause isn't reached
// (no matching class bonus set up).
gaScenario('swerving-spring-prevent-2')
    ->standardDeck('Swerving Spring')
    ->seed('myHand', 'Swerving Spring')
    ->castSpell('Swerving Spring', pay: 2, window: 'opp', target: 'myField-0')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","PREVENT_ALL_2"]', 'The targeted champion is protected by a PREVENT_ALL_2 shield')
    ->expectDqEmpty()
    ->register();

// --- Solar Providence: draw a card, then discard a card ---
gaScenario('solar-providence-draw-discard')
    ->standardDeck('Solar Providence')
    ->seed('myHand', 'Solar Providence')
    ->play('Solar Providence')->pay(2)->passBoth()->pay(1)
    ->expectZoneCount('myGraveyard', 2, 1, 'Solar Providence and the discarded card both end up in the graveyard')
    ->expectZoneCount('myDeck', 8, 1, 'Solar Providence draws a card from the deck')
    ->expectDqEmpty()
    ->register();

// --- Spellshield: Arcane: prevent the next damage to your champion this turn ---
// Notes carried over from the original definition:
//   - ARCANE lineage/element unlock
gaScenario('spellshield-arcane-prevent-tag')
    ->standardDeck('Spellshield: Arcane')
    ->readyFor('Spellshield: Arcane')
    ->seed('myHand', 'Spellshield: Arcane')
    ->castSpell('Spellshield: Arcane', pay: 3)
    ->register();

// --- Spellshield: Astra: prevent the next damage to your champion this turn, then Glimpse X ---
// Notes carried over from the original definition:
//   - ASTRA lineage/element unlock
gaScenario('spellshield-astra-prevent-tag')
    ->standardDeck('Spellshield: Astra')
    ->readyFor('Spellshield: Astra')
    ->seed('myHand', 'Spellshield: Astra')
    ->castSpell('Spellshield: Astra', pay: 4)
    ->register();

// --- Stellar Bloom: Gather four times ---
// Notes carried over from the original definition:
//   - ASTRA lineage/element unlock
gaScenario('stellar-bloom-gather-four')
    ->standardDeck('Stellar Bloom')
    ->readyFor('Stellar Bloom')
    ->seed('myHand', 'Stellar Bloom')
    ->castSpell('Stellar Bloom', pay: 2)
    ->register();

// --- Spirit's Blessing: return a regalia to material deck, wake up champion, draw a card ---
// Notes carried over from the original definition:
//   - CRUX lineage/element unlock, rested so wake-up is observable
//   - Necklace of Foresight (REGALIA) - additional-cost fuel
gaScenario('spirits-blessing-wake-draw')
    ->standardDeck('Spirit\'s Blessing')
    ->patch('myField-0', ['Subcards' => ['NfbZ0nouSQ'], 'Status' => 1])
    ->seed('myField', 'Necklace of Foresight')
    ->seed('myHand', 'Spirit\'s Blessing')
    ->castSpell('Spirit\'s Blessing', pay: 1, window: 'both', target: 'myField-1')
    ->register();

// --- Safeguard Amulet: banish self to prevent 4 non-combat damage to champion this turn ---
// Safeguard Amulet's element is NORM, so no lineage patch is needed. It is seeded directly
// onto the field (REGALIA convention). Its ability is banish-self at zero reserve cost, so
// one CustomInput Activate click both tags the champion with the prevent-4 turn effect and
// moves the amulet to myBanish. Nascent Blast (also NORM) is then played and its own MZCHOOSE
// target is pointed at our own champion (myField-0, "target unit" allows own side) instead of
// an opponent's ally, dealing 3 non-combat damage that the prevention fully absorbs. The
// prevention's exact 4-point ceiling isn't independently proven (3 < 4 either way), only that
// the 3 damage dealt is fully prevented.
// Notes carried over from the original definition:
//   - Safeguard Amulet (REGALIA)
gaScenario('safeguard-amulet-prevent')
    ->standardDeck('Nascent Blast')
    ->seed('myField', 'Safeguard Amulet')
    ->seed('myHand', 'Nascent Blast')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)
    ->castSpell('Nascent Blast', pay: 3, window: 'both', target: 'myField-0')
    ->tested('Safeguard Amulet')
    ->register();

// --- Shard of Empowerment: banish self, Empower 2 ---
// Shard of Empowerment's element is NORM. Seeded directly onto the field (REGALIA
// convention), its ability is banish-self at zero reserve cost: one CustomInput Activate
// click both empowers the champion by 2 and moves the shard to myBanish. Empower() tags the
// champion's TurnEffects with the source card ID, EMPOWERED, and EMPOWER_PLUS_2.
// Notes carried over from the original definition:
//   - Shard of Empowerment (REGALIA)
gaScenario('shard-of-empowerment-buff')
    ->standardDeck('Windslice')
    ->seed('myField', 'Shard of Empowerment')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Shard of Empowerment')
    ->register();

// --- Spurn to Ash: destroy target regalia with memory cost 1 or less (debug probe) ---
// Spurn to Ash's element is FIRE, matched by the default starting champion (Spirit of Fire),
// so no lineage patch is needed. Necklace of Foresight (memory cost 1, REGALIA) is seeded onto
// the opponent's field as the destroy target. Without a [Class Bonus] discount, the full
// 3-reserve cost is paid; the target choice then opens a reactive opponent-response window
// (declined via P2 PASS) before a second, real target MZCHOOSE resolves the destroy effect.
// The generated ability code moves the target to "theirGraveyard" (relative to the activating
// player), but since the target is REGALIA, GraveyardAddReplacement() transparently redirects
// any REGALIA graveyard-add into that same player's banish zone instead -- and MZMove()
// correctly resolves "that same player" as the object's actual owner (P2) throughout, not the
// activating player (P1), so the destroyed regalia lands in its owner's banish zone as
// expected. (A prior investigation reported this card's target vanishing entirely; that check
// only looked at both players' graveyards and fields, not banish zones -- not an engine bug.)
// Notes carried over from the original definition:
//   - Necklace of Foresight (memory cost 1) - opponent's regalia target
gaScenario('spurn-to-ash-destroy-regalia')
    ->standardDeck('Spurn to Ash')
    ->seed('theirField', 'Necklace of Foresight')
    ->seed('myHand', 'Spurn to Ash')
    ->play('Spurn to Ash')->pay(3)->target('theirField-1')->pass(2)->target('theirField-1')
    ->register();

// --- Surged Coordinator: [Class Bonus] On Enter static counters from LV + other statics - 3 ---
// Surged Coordinator's element is ARCANE and its [Class Bonus] requires a WARRIOR champion.
// The starting champion's CardID is patched directly to Lorraine, Arclight Saber (level 3,
// WARRIOR, ARCANE -- unlocks both the class bonus and the card's element) and is also given 1
// static counter directly so it counts as "another object with a static counter" for the
// ability's own count. On Enter puts max(0, LV + count - 3) static counters on itself
// (GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php, enterAbilities["6eWmfzAmWr:0"]); at
// champion level 3 with count=1 (the champion itself) that's 1 static counter.
gaScenario('surged-coordinator-class-bonus-counters')
    ->standardDeck('Surged Coordinator')
    ->patch('myField-0', ['CardID' => 'x9sSpjpP3G', 'Counters' => ['static' => 1]])
    ->seed('myHand', 'Surged Coordinator')
    ->castSpell('Surged Coordinator', pay: 2, window: 'me')
    ->expectProperty('myField-1', 'CardID', '6eWmfzAmWr', 'Surged Coordinator is on the field')
    ->expectProperty('myField-1', 'Counters', '{"static":1}', 'The Class Bonus grants LV+X-3 (3+1-3=1) static counters on enter, counting the champion\'s own static counter as X')
    ->expectDqEmpty()
    ->register();

// --- Shieldroid: Floating Memory pays a champion level-up's memory cost from graveyard ---
// Shieldroid's Floating Memory keyword (while paying a memory cost, you may banish this card
// from your graveyard to pay for 1 of that cost) is tested the same way as
// honorable-vanguard-floating-memory: pay Lorraine, Wandering Warrior's 1-memory champion
// level-up cost entirely from a Shieldroid seeded directly into the graveyard, with no
// myMemory filler seeded, so QueueMaterializeFloatingPaymentChoice
// (GrandArchiveSim/Custom/MaterializeLogic.php) offers it as the sole payment source. Taunt
// (the combat-targeting-priority half of Shieldroid's text) is a computed attack-declaration
// restriction with no stored counter or flag to assert and is out of scope here.
gaScenario('shieldroid-floating-memory')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myGraveyard', 'Shieldroid')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->target('myGraveyard-0')
    ->tested('Shieldroid')
    ->expectProperty('myField-0', 'CardID', 'DpHDGaX2Pn', 'The champion level-up completes -- Lorraine, Wandering Warrior takes the field')
    ->expectZoneCount('myGraveyard', 0, 1, 'Shieldroid leaves the graveyard, paying for the level-up\'s memory cost')
    ->expectZoneCount('myBanish', 1, 1, 'Floating Memory banishes Shieldroid rather than tapping a myMemory card')
    ->expectZoneCount('myMemory', 0, 1, 'No myMemory card was seeded or spent -- the cost was paid entirely from the graveyard')
    ->expectDqEmpty()
    ->register();

// --- Stalwart Shieldmate: Floating Memory pays a champion level-up's memory cost from graveyard ---
// Same Floating Memory keyword and same test shape as shieldroid-floating-memory and
// honorable-vanguard-floating-memory -- Stalwart Shieldmate shares identical printed text with
// Shieldroid (Taunt + Floating Memory), so this is a second, independent card exercising the
// same QueueMaterializeFloatingPaymentChoice payment path. Taunt is out of scope, same reason.
gaScenario('stalwart-shieldmate-floating-memory')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myGraveyard', 'Stalwart Shieldmate')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->target('myGraveyard-0')
    ->tested('Stalwart Shieldmate')
    ->expectProperty('myField-0', 'CardID', 'DpHDGaX2Pn', 'The champion level-up completes -- Lorraine, Wandering Warrior takes the field')
    ->expectZoneCount('myGraveyard', 0, 1, 'Stalwart Shieldmate leaves the graveyard, paying for the level-up\'s memory cost')
    ->expectZoneCount('myBanish', 1, 1, 'Floating Memory banishes Stalwart Shieldmate rather than tapping a myMemory card')
    ->expectZoneCount('myMemory', 0, 1, 'No myMemory card was seeded or spent -- the cost was paid entirely from the graveyard')
    ->expectDqEmpty()
    ->register();

// --- Stoked Slice: Class Bonus On Attack, banish two fire cards from graveyard to buff allies ---
// Notes carried over from the original definition:
//   - Same WARRIOR class-bonus override as emberslash-discard-draw. Stoked Slice is FIRE
//   - element, and the starting champion (Spirit of Fire) is already FIRE, so no lineage patch
//   - is needed for the element gate here (unlike conductive-strike-static-counters' ARCANE).
//   - Dungeon Guide (ALLY) - +1 power buff target
//   - Cell Handler (FIRE ally) #1 - banish fodder
//   - Cell Handler (FIRE ally) #2 - banish fodder
gaScenario('stoked-slice-rally-allies')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(classes: 'WARRIOR')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myGraveyard', 'Cell Handler')
    ->seed('myGraveyard', 'Cell Handler')
    ->seed('myHand', 'Stoked Slice')
    ->endTurn()
    ->endTurn(2)->answer(1, '-')
    ->play('Stoked Slice')->pay(2)->target('theirField-0')->answer(1, 'myGraveyard-0&myGraveyard-1')
    ->register();

// --- Spirit of Fire: On Enter, draw seven cards ---
// Spirit of Fire is the default starting champion for every fixture in this file -- its "On
// Enter: Draw seven cards" trigger fires during pregame setup, before any actions run, which
// is exactly why every other fixture's initial gamestate already shows a 7-card starting hand.
// This fixture makes that implicit, constantly-reconfirmed coverage explicit for the backlog
// tool. The assertion framework can't evaluate step 0 (RegressionEvaluateAssertion needs the
// full engine bootstrap an action triggers -- GetZoneObject() is undefined that early), so one
// harmless P1 Main-phase pass is included purely to produce an assertable step; it doesn't
// touch hand count.
gaScenario('spirit-of-fire-on-enter-draw')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->endTurn()
    ->tested('Spirit of Fire')
    ->expectProperty('myField-0', 'CardID', 'LMyKyVC2O9', 'Spirit of Fire is the starting champion')
    ->expectZoneCount('myHand', 7, 1, 'On Enter drew seven cards during pregame setup')
    ->register();

// --- Spirit of Water: On Enter, draw seven cards ---
// Same trigger and same reasoning as spirit-of-fire-on-enter-draw, swapping the deck's starting
// champion to Spirit of Water instead of patching CardID post-hoc -- a raw property patch
// wouldn't retroactively fire the On Enter trigger, since it only runs at actual placement time
// during pregame setup, not whenever a card happens to carry that CardID.
gaScenario('spirit-of-water-on-enter-draw')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK
    )
    ->endTurn()
    ->tested('Spirit of Water')
    ->expectProperty('myField-0', 'CardID', 'tafqldAGRF', 'Spirit of Water is the starting champion')
    ->expectZoneCount('myHand', 7, 1, 'On Enter drew seven cards during pregame setup')
    ->register();

// --- Spirited Neophyte: On Attack, if facing North, Empower 2 ---
// Same real attack-declaration sequence as ardent-cloudstriker-west-attack-champion-buff. Unlike
// Ardent Cloudstriker's transient computed-power bonus, Empower() tags the CHAMPION object with a
// TurnEffects entry that persists after combat resolves (it isn't cleared by the CombatAttacker/
// CombatTarget cleanup), so it's directly observable in the post-action snapshot.
// Notes carried over from the original definition:
//   - Shifting Currents facing North
//   - Spirited Neophyte
//   - awake, can attack
//   - decline materialize offer
//   - declare attack with Spirited Neophyte
//   - target opponent's champion
gaScenario('spirited-neophyte-north-attack-empower')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'NORTH'])
    ->seed('myField', 'Spirited Neophyte')
    ->patch('myField-1', ['Status' => 2])
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->action(1, 10002, 'myField-1!FSM!')->target('theirField-0')
    ->tested('Spirited Neophyte')
    ->register();

// --- Sweet Ambrosia: Banish self to recover 3 damage from the champion ---
// Field items with an activated ability are not clickable via a plain myField-N!FSM! action
// (ActionMap's "myField" case only handles attack declarations, GrandArchiveSim/Custom/
// GameLogic.php:1289-1296) -- their ability is offered as a fast-action MZMAYCHOOSE opportunity
// once the turn player attempts to pass (GetPlayableOpportunityChoices/GetPlayableFastAbilities,
// OpportunityLogic.php), answered with the encoded "{mzID}@Activate-{abilityIndex}@{label}"
// choice string (same shape as tariff-ring-attack-tax's response).
// Notes carried over from the original definition:
//   - Sweet Ambrosia
//   - pre-damage champion
gaScenario('sweet-ambrosia-banish-recover')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Sweet Ambrosia')
    ->champion(damage: 5)
    ->endTurn()->answer(1, '-')->answer(1, 'myField-1@Activate-0@Banish')
    ->tested('Sweet Ambrosia')
    ->expectDamage('myField-0', 2, 'Recover 3 reduces the champion\'s pre-existing 5 damage down to 2', step: 3)
    ->expectZoneCount('myBanish', 1, 1, 'Sweet Ambrosia banishes itself to pay its own activation cost', step: 3)
    ->expectDqEmpty(step: 3)
    ->register();

// --- Steel Halberd: [Class Bonus] +1 POWER ---
// [Class Bonus] scans ALL physical field objects, independent of the main champion (established
// technique, e.g. devoted-bloomweaver-class-bonus-empower): a physically-seeded WARRIOR champion
// (Jin, Fate Defiant) satisfies it. Base power 1 + 1 from the bonus.
// A step-0 assertion can't run (the engine's game state isn't parsed into memory until the
// first action executes, DevTools/RunIntegrationTests.php:158 vs 150/167) -- a harmless Pass
// forces that load so the computed-power assertion can run at step 1.
// Notes carried over from the original definition:
//   - Jin, Fate Defiant (WARRIOR), for Class Bonus
//   - Steel Halberd
gaScenario('steel-halberd-class-bonus-power')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Jin, Fate Defiant')
    ->seed('myField', 'Steel Halberd')
    ->endTurn()
    ->tested('Steel Halberd')
    ->register();

// --- Shuang Ji of Sacrifice: +1 POWER per five damage on your champion ---
// (Its own On Enter -- "may deal 5 unpreventable damage to your champion to draw a card" -- turned
// out to require a real hand-activation materialize sequence that vanishes the card instead of
// placing it on the field for this WEAPON regalia subtype, unlike ally/item materializes elsewhere
// in this file; that's a separate investigation and out of scope here. Only the always-on computed
// power bonus is covered, using the same direct-field-seed technique as steel-halberd-class-bonus-power.)
// Notes carried over from the original definition:
//   - 12 damage on champion
//   - Shuang Ji of Sacrifice
gaScenario('shuang-ji-sacrifice-champion-damage-power')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(damage: 12)
    ->seed('myField', 'Shuang Ji of Sacrifice')
    ->endTurn()
    ->tested('Shuang Ji of Sacrifice')
    ->register();

// --- Slate Whetstone: Banish - up to one target Polearm weapon you control gets +1 POWER
// until end of turn, draw a card ---
// Field items with an activated ability are offered as a fast-action MZMAYCHOOSE opportunity
// once the turn player attempts to pass (same technique as sweet-ambrosia-banish-recover),
// answered with the encoded "{mzID}@Activate-{abilityIndex}@{label}" choice string. Steel
// Halberd (WARRIOR/POLEARM) is seeded as the buff target.
// Notes carried over from the original definition:
//   - Slate Whetstone
//   - Steel Halberd (WARRIOR/POLEARM)
//   - choose Steel Halberd as the buff target (it slides down to myField-1 once the Whetstone banishes itself as the cost)
gaScenario('slate-whetstone-banish-polearm-power-draw')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Slate Whetstone')
    ->seed('myField', 'Steel Halberd')
    ->endTurn()->answer(1, 'myField-1@Activate-0@Banish')->target('myField-1')
    ->tested('Slate Whetstone')
    ->register();

// --- Safeguard Paladin: [Class Bonus] prevent 2 non-combat damage to itself ---
// Safeguard Paladin's Class Bonus (CombatLogic.php:4479-4484, inside OnDealDamage's non-combat
// prevention chain: "if(!$isCombat && ... $targetObj->CardID === 'ifmmvbm26h' ...) $amount =
// max(0, $amount - 2)") only fires when the champion's class matches (CLERIC or WARRIOR). The
// starting champion's CardID is patched directly to Jin, Fate Defiant (WARRIOR) to satisfy that
// condition -- but Jin's own element is NORM, not FIRE, so a FIRE-element damage spell (like
// Focused Flames, tried first and rejected by CanPlayerUseCardElement -- confirmed via a
// throwaway debug harness, DevTools/debug_safeguard.php/debug_safeguard2.php, now deleted --
// since the champion patch drops the default Spirit of Fire lineage's FIRE unlock) can't be
// activated. Nascent Blast (vajycopxgf, cardActivatedAbilities/CardActivated-1,
// GeneratedMacroCode.php ~22398-22410/37158-37166) is NORM element instead (always usable
// regardless of champion element) -- a 3-reserve ACTION spell that deals 3 non-combat damage to
// target unit via DealDamage -> OnDealDamage, activated from hand and targeted at Safeguard
// Paladin itself (same action shape as ignite-the-soul-damage: FSM click, pay reserve, both
// players decline the EffectStack response window with PASS, then choose the target) --
// confirmed WRONG via RunIntegrationTests --verbose: the target MZCHOOSE is queued directly
// after the 3rd payment with no decline step (same as trusty-steed-enter-ally-power-buff's
// ALLY On Enter target choice), so an extra PASS action gets consumed as the target choice
// itself instead. 3 damage - 2 prevented = 1 damage actually applied.
// Notes carried over from the original definition:
//   - Jin, Fate Defiant (WARRIOR) - Class Bonus precondition
//   - Safeguard Paladin
//   - Nascent Blast
gaScenario('safeguard-paladin-class-bonus-noncombat-prevent')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Jin, Fate Defiant
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK
    )
    ->champion(cardName: 'Jin, Fate Defiant')
    ->seed('myField', 'Safeguard Paladin')
    ->seed('myHand', 'Nascent Blast')
    ->castSpell('Nascent Blast', pay: 3, target: 'myField-1')
    ->tested('Safeguard Paladin')
    ->expectDamage('myField-1', 1, 'Nascent Blast\'s 3 damage minus Safeguard Paladin\'s Class Bonus 2 prevention = 1')
    ->expectDqEmpty()
    ->register();

// --- Savage Swing: [Class Bonus] Floating Memory ---
// Unlike Veteran Soldier's unconditional Floating Memory (veteran-soldier-floating-memory),
// Savage Swing's is gated by [Class Bonus] (its own class is WARRIOR). Two things ruled out
// Floating Memory's usual test shape: (1) Floating Memory only offers itself as a payment
// source for a MEMORY cost (a champion level-up), NOT a reserve cost -- confirmed by trying to
// pay 1 of Nascent Blast's reserve cast cost from the graveyard, which was rejected as "Invalid
// selection."; (2) patching the starting champion directly to Jin, Fate Defiant (level 1)
// to satisfy the Class Bonus makes leveling into another level-1 champion (Lorraine, Wandering
// Warrior) illegal -- level-up requires a strictly higher level than the current champion. Fix:
// patch the champion to Jin, Fate Defiant (level 1, WARRIOR) and level up from there into Jin,
// Zealous Maverick (level 2, WARRIOR, 2-memory cost) instead -- the Class Bonus condition checks
// the CURRENT champion (Jin, Fate Defiant, still WARRIOR) at payment time, before the level-up
// completes. Savage Swing pays 1 of the 2 memory via Floating Memory from the graveyard; the
// 2nd memory point is a filler card seeded directly into myMemory (no draw/recollection cycle
// needed to populate it).
// Notes carried over from the original definition:
//   - Jin, Fate Defiant (WARRIOR, level 1) - Class Bonus precondition + legal level-up base
//   - Savage Swing
//   - filler memory card, 2nd point of Jin Zealous Maverick's 2-memory level-up cost
//   - select Jin, Zealous Maverick as the level-up target
//   - pay 1 memory via Floating Memory
//   - pay the 2nd memory point
gaScenario('savage-swing-class-bonus-floating-memory')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Jin, Zealous Maverick
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK
    )
    ->champion(cardName: 'Jin, Fate Defiant')
    ->seed('myGraveyard', 'Savage Swing')
    ->seed('myMemory', 'Dungeon Guide')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->target('myGraveyard-0')->target('myMemory-0')
    ->tested('Savage Swing')
    ->register();

// --- Swift Recruit: Intercept, redirect an attack on your champion to this awake ally ---
// Intercept (GetAvailableInterceptRedirectTargets, CombatLogic.php:605-624) only offers a
// redirect when the attack's TARGET is a CHAMPION (checked via PropertyContains(...,
// "CHAMPION")), so this needs the OPPONENT attacking PLAYER 1's champion, not the usual
// player-1-attacks pattern used by every other real-attack fixture so far. Reaching player 2's
// own turn (rather than staying on player 1's turn 1, as every prior fixture did) uses the
// CustomInput.php "myHealth" Pass button as a genuine mid-game end-turn action (not just the
// pregame mulligan use) -- discovered while debugging wind-cutter-class-bonus-power-attack: it's
// the SAME action as the pregame health-pass, gated only by "only the turn player can pass," and
// reusable any time it's your turn with nothing left to do. After P1 formally ends turn 1 (with
// nothing to do -- Swift Recruit is placed directly on the field, not played from hand), P2
// declines their own MAT-phase materialize offer, then P2's champion (default Spirit of Fire)
// attacks P1's champion directly. GetAvailableInterceptRedirectTargets then offers Swift Recruit
// (awake, ALLY, HasIntercept -- all satisfied by a plain field seed with no extra patches) as a
// redirect target, queued as a "Choose_an_interceptor" MZMAYCHOOSE for the DEFENDER (player 1,
// in their own myField-1 perspective). Redirecting moves the attack's target to Swift Recruit
// itself, so the subsequent "Retaliate?" decision (still queued for the defender, player 1) and
// the resulting combat damage land on Swift Recruit (1 life... its actual life is 2, dealt 1
// damage from the champion's own printed POWER) instead of the champion.
// Notes carried over from the original definition:
//   - Swift Recruit, awake by default
//   - Executioner's Spear (1 POWER), P2's own field -- Spirit of Fire has no base POWER, so a weapon is needed for a legal attack
//   - P1 declines their own MAT-phase materialize offer first (the mid-game end-turn Pass button refuses while a decision is pending)
//   - P1 formally ends turn 1 (nothing to do)
//   - P2 declines their MAT-phase materialize offer
//   - P2's champion attacks
//   - choose Executioner's Spear as the weapon
//   - target P1's champion
//   - redirect to Swift Recruit
//   - decline Retaliate
gaScenario('swift-recruit-intercept-redirect')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Swift Recruit')
    ->seed('myField', 'Executioner\'s Spear', player: 2)
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->endTurn()->pass(2)
    ->action(2, 10002, 'myField-0!FSM!')->answer(2, 'myField-1')->answer(2, 'theirField-0')->target('myField-1')
    ->answer(1, '-')
    ->tested('Swift Recruit')
    ->expectDamage('myField-0', 0, 'the champion takes NO damage -- the attack was redirected away from it')
    ->expectDamage('myField-1', 1, 'Swift Recruit takes the redirected attack\'s 1 damage (Executioner\'s Spear\'s printed power) instead of the champion')
    ->expectDqEmpty()
    ->register();

// --- Supply Drone: [Class Bonus] at recollection phase, materialize a 0-cost Bullet ---
// The global turn counter only increments when play cycles back to the first player (EndPhase:
// "$turnPlayer = ($turnPlayer==1)?2:1; if($turnPlayer==$firstPlayer) ++$currentTurn;"), so
// "$currentTurn===1" actually covers BOTH player 1's AND player 2's first turns -- player 2's
// first turn does NOT unblock RecollectionPhase/BeforeRecollectionPhase (confirmed by directly
// instrumenting the turn/phase state this session). Reaching a real recollection phase for
// player 2 needs a full cycle back to player 2's own SECOND turn: P1 ends turn 1, P2 ends
// their (still turn-1) turn, P1 declines their own MAT-phase materialize offer and ends their
// turn 2 (this crosses back to player 1, incrementing the global counter to 2), then P2
// declines their own materialize offer before BREC's per-card recollection check queues
// Supply Drone's materialize choice.
// Notes carried over from the original definition:
//   - Diana, Keen Huntress (RANGER champion) - Class Bonus source
//   - Supply Drone
//   - Plated Bullet - 0-cost Bullet source
//   - ends turn 1
//   - ends player 2's turn (still global turn 1)
//   - decline player 1's own MAT-phase materialize offer
//   - ends player 1's turn 2 (crosses back to player 1 -> global turn increments to 2)
//   - decline player 2's own MAT-phase materialize offer -> reaches BREC
gaScenario('supply-drone-class-bonus-recollection-materialize')
    ->standardDeck('Supply Drone')
    ->seed('myField', 'Diana, Keen Huntress', player: 2)
    ->seed('myField', 'Supply Drone', player: 2)
    ->seed('myMaterial', 'Plated Bullet', player: 2)
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->endTurn()->pass(2)->answer(2, 'myMaterial-4')
    ->tested('Supply Drone')
    ->expectDqEmpty()
    ->expectProperty('p2Field-3', 'CardID', 'l75tlzsmw3', '[Class Bonus] at recollection phase, materialize a 0-cost Bullet from material deck')
    ->register();

// --- Sentinel Fabricator: (3), REST: Summon an Automaton Drone token with a buff counter ---
// The activated ability's prereq requires Status==2 (awake), so it's patched explicitly.
// The 3-reserve cost queues 3 reps of the myHand-0 reserve-payment decision before summoning
// the token.
// Notes carried over from the original definition:
//   - Sentinel Fabricator, awake
gaScenario('sentinel-fabricator-summon-buffed-drone')
    ->standardDeck('Sentinel Fabricator')
    ->seed('myField', 'Sentinel Fabricator', properties: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->pass(1)
    ->tested('Sentinel Fabricator')
    ->expectProperty('p1Field-2', 'CardID', 'mu6gvnta6q', 'Summon an Automaton Drone token')
    ->expectProperty('p1Field-2', 'Counters', '{"buff":1}', 'the token has a buff counter on it')
    ->register();

// --- Spirit of Wind: On Enter -- Draw seven cards ---
// Spirit of Wind is a level-0 champion used as the pregame starting-champion material choice
// in place of the usual Spirit of Fire. Its On Enter ability ("Draw seven cards") fires
// synchronously during pregame resolution (PREGAME_CHOOSE_STARTING_CHAMPION's unconditional
// Enter() call, GameLogic.php ~1190-1200) -- confirmed via a standalone debug script tracing
// hand count through the exact pregame sequence: hand goes from 0 to 7 the instant Enter()
// fires, BEFORE initial_gamestate.txt is even captured. Note Spirit of Fire (used by every
// other fixture's Material section) has the IDENTICAL "On Enter: Draw seven cards" text, so
// there is no separate/default opening-hand mechanic to diff against -- every single fixture
// in this suite already exercises this exact ability text via Spirit of Fire; this fixture
// exists purely to record dedicated coverage against pNiyaGlIe7's own card ID. A zone_count
// assertion of 7 on myHand (the deck has 0 other draw effects before this point) is therefore
// the correct and only meaningful check: if the On Enter ability failed to fire, hand would
// be 0, not 7.
// Notes carried over from the original definition:
//   - harmless no-op click
gaScenario('spirit-of-wind-enter-draw-seven')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK
    )
    ->target('myField-0')
    ->tested('Spirit of Wind')
    ->expectZoneCount('myHand', 7, 1, 'Spirit of Wind\'s On Enter draws seven cards, giving the opening hand')
    ->register();

// --- Smash with Obelisk (2kkvoqk1l7): mandatory domain sacrifice as additional cost; gets +X
// POWER where X is the sacrificed domain's reserve cost. Engine gap: the "Play prereq" (requires a
// domain on field, see activateCardPrereqs["2kkvoqk1l7:0"]) was wired via the schema/generator, but
// DoActivateCard's hand-written cost-declaration switch (GrandArchiveSim/Custom/GameLogic.php) never
// queued the pre-existing SmashWithObeliskSacrifice DQ handler, so playing the card skipped the
// sacrifice entirely and the ability macro's "smashObeliskBonus" read (GeneratedMacroCode.php) always
// saw an unset variable, resolving as +0 POWER. Fixed by wiring $hasSmashObeliskCost the same way as
// the other mandatory-sacrifice additional-cost cards (Undeniable Truth, Blazing Throw).
// Grant NEOS element access (Smash with Obelisk's element) by patching the starting champion's
// Subcards with a NEOS lineage card -- the harness's documented technique for reaching an
// advanced element without scripting a real level-up sequence (GetChampionLineage() walks
// $obj->Subcards; see GrandArchiveSim/Custom/GameLogic.php ~19370-19446). Then seed a plain,
// rules-text-free DOMAIN (Wool Brook, reserve cost 4) onto the field to sacrifice, and seed
// Smash with Obelisk plus 3 reserve-payment fodder cards directly into hand so the fixture only
// has to replay the actual activation, not deck/draw setup. Everything is seeded for PLAYER 2:
// CanActivateAttackCardNow()/IsFirstTurnAttackLocked() (GrandArchiveSim/Custom/GameLogic.php)
// forbid the true first player from playing an ATTACK card on turn 1, so player 1's turn is
// passed through first (below) and player 2 -- never "the first player" -- plays the card on
// their own first turn instead. The starting champion is always the first (and here, only)
// object placed on a fresh field, so it lands at myField-0.
// myHand-7 confirmed from the "Setup: added ..." mzID log (opening hand under seed=42 is
// myHand-0..6, so Smash with Obelisk lands at myHand-7 and the 3 fodder cards follow it).
// Notes carried over from the original definition:
//   - Tonoris, Creation's Will (NEOS)
//   - Wool Brook (DOMAIN, reserve cost 4, no rules text)
//   - Smash with Obelisk
//   - Recruitment Officer (reserve fodder)
//   - Recruitment Officer (reserve fodder)
//   - Recruitment Officer (reserve fodder)
//   - Pass through player 1's turn 1 (nothing to do), matching the pattern verified in
//   - wind-cutter-class-bonus-power-attack for reaching player 2's own turn.
//   - P1 declines their own MAT-phase materialize offer
//   - P1 formally ends turn 1
//   - P2 declines their MAT-phase materialize offer
//   - Free play: P2 plays Smash with Obelisk
//   - MZCHOOSE: sacrifice the domain (Wool Brook)
//   - reindexes the hand zone, so the next fodder card always shifts down into myHand-8.
//   - Pass fast action opportunities
gaScenario('smash-with-obelisk-domain-sacrifice-bonus')
    ->deck(<<<'DECK'
# Main
4 Recruitment Officer
4 Recruitment Officer
4 Recruitment Officer
4 Recruitment Officer
4 Recruitment Officer
# Material
1 Spirit of Wind
DECK
    )
    ->champion(lineage: ['Tonoris, Creation\'s Will'], player: 2)
    ->seed('myField', 'Wool Brook', player: 2)
    ->seed('myHand', 'Smash with Obelisk', player: 2)
    ->seed('myHand', 'Recruitment Officer', player: 2)
    ->seed('myHand', 'Recruitment Officer', player: 2)
    ->seed('myHand', 'Recruitment Officer', player: 2)
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->endTurn()->pass(2)
    ->play('Smash with Obelisk', 2)->answer(2, 'myField-1')->answer(2, 'myHand-8')->answer(2, 'myHand-8')->answer(2, 'myHand-8')->answer(2, 'theirField-0')->pass(1)
    ->expectDamage('p1Field-0', 10, 'The attack dealt 10: the printed 6 POWER plus 4, the reserve cost of the sacrificed Wool Brook')
    ->expectProperty('p2Graveyard-0', 'CardID', 'lcCGyyNGuM', 'Wool Brook was sacrificed as the additional cost')
    ->covers(['cost', 'additional-cost-sacrifice', 'power-modification'], 'As an additional cost to activate this card, sacrifice a domain you control.', 'CARDNAME gets +X [POWER] where X is the reserve cost of the sacrificed domain.')
    ->register();

// --- Synth Disrupter: banish, Automaton allies enter the field rested until end of turn ---
// Synth Disrupter's banish ability just sets a global effect flag (z1vdxi74wa_RESTED,
// GeneratedMacroCode.php); the actual effect (Automaton allies enter the field rested) is
// checked in FieldAfterAdd (GameLogic.php ~8256). ENGINE BUG FOUND AND FIXED: Synth Disrupter's
// "Banish [self]:" cost was missing from the hardcoded "always banish self" fallthrough switch
// in ActivatedAbilityCost (GameLogic.php ~6142-6194, alongside ~50 other banish-self cards) --
// selecting its fast-action opportunity choice fired the ability's effect but never actually
// removed the card, so it stayed on the field and kept re-offering itself. Fixed by adding
// z1vdxi74wa (and bHGUNMFLg9, Wind Resonance Bauble, found missing the same way) to that list.
// Materializing Automaton Beastkeeper (a plain AUTOMATON ally with an optional Class Bonus On
// Enter, out of scope) queues its own reserve payment then an EffectStackOpportunity BEFORE it
// actually resolves onto the field -- answering that opportunity with Synth Disrupter's banish
// choice (instead of declining) lets the global effect apply in time for Beastkeeper's own
// FieldAfterAdd check, confirmed by its Status landing rested (1) instead of the default awake
// (2). (Using "attempt to pass" to reach the opportunity instead would end player 1's turn
// outright once nothing else responds -- it only works standalone, as in sweet-ambrosia-banish-
// recover, not when a same-turn follow-up action is still needed.)
// Notes carried over from the original definition:
//   - Synth Disrupter
//   - materialize Automaton Beastkeeper
//   - pay reserve 1/4
//   - pay reserve 2/4
//   - pay reserve 3/4
//   - pay reserve 4/4
//   - banish Synth Disrupter during the pre-resolution opportunity
gaScenario('synth-disrupter-banish-automaton-rested')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Synth Disrupter')
    ->seed('myHand', 'Automaton Beastkeeper')
    ->play('Automaton Beastkeeper')->pay(4)->answer(1, 'myField-1@Activate-0@Banish')
    ->tested('Synth Disrupter')
    ->expectZoneCount('myField', 2, 1, 'Synth Disrupter banished, Automaton Beastkeeper resolved (champion + 1 ally)')
    ->expectProperty('p1Field-1', 'CardID', 'i5jnsl7ddc', 'Automaton Beastkeeper is the surviving field object')
    ->expectProperty('p1Field-1', 'Status', '1', 'Automaton allies enter the field rested until end of turn')
    ->register();

// --- Scale of Souls: (2), REST: Return a card from your memory to your hand ---
// ENGINE BUG FOUND AND FIXED: Scale of Souls' ability is registered in $cardActivatedAbilities
// (GeneratedMacroCode.php, the same dictionary used for reserve-cost cards played from
// hand/material via DoActivateCard/ActivateCard), NOT in $activateAbilityAbilities (the
// free/memory-cost-0 dictionary field items like Ingredient Pouch and Cosmic Astroscope use, invoked
// via ActivateAbility/DoActivatedAbility). But GrandArchiveSim/Custom/CustomInput.php's
// "myField"/"myIntent" case only ever called ActivateAbility() for a field-resident object's direct
// activate click -- DoActivatedAbility's $staticAbilityCount came back 0 for this card (it has no
// $activateAbilityAbilities entry), so ability index 0 was misclassified as a "dynamic" ability that
// matched nothing and the whole activation silently no-opped. Fixed by having CustomInput.php route
// to ActivateCard() instead whenever CardActivateAbilityCount() is 0 but CardCardActivatedCount() is
// nonzero for the target's CardID -- this affects every field-resident REGALIA/ITEM with a
// repeatable ability only reachable this way (not via the fast-opportunity MZMAYCHOOSE path the
// other myField@Activate fixtures in this suite use), so this fixture drives the direct,
// non-opportunity mode=10001 CustomInput click that was previously dead.
// (Superseded: the "(2)" cost IS now paid -- the ability is registered as a field activation in Custom/GameLogic.php and the fixture pays two reserve cards.)
// Earlier note, kept for history: the card's own "(2)" reserve cost was not wired up --
// CardCost_reserve('0z2snsdwmx') returns -1 (unset) in the CardEditor card-ability database, so
// CalculateActivationReserveCost() computes no reserve cost and DoActivateCard() charges nothing.
// This is a card-data gap in the CardEditor database (separate from the CustomInput.php routing
// fix), out of scope here -- this fixture reflects the ability's actual current (free) cost rather
// than fabricating a reserve payment that doesn't really happen; no myHand-N cost-payment action is
// needed before the MZCHOOSE.
// Notes carried over from the original definition:
//   - Scale of Souls
//   - Fairy Whispers, filler card to return from memory
//   - Direct field click via the normal client action path (not the fast-opportunity workaround).
//   - choose the filler card to return to hand
gaScenario('scale-of-souls-rest-return-memory')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Scale of Souls')
    ->seed('myMemory', 'Fairy Whispers')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->target('myMemory-0')
    ->tested('Scale of Souls')
    ->register();

// --- Stream of Consciousness: Draw a card into memory ---
// Stream of Consciousness is WATER -- confirmed (via CanPlayerMeetCardElementRequirements /
// GetPlayerEnabledElements, GameLogic.php ~19412-19442) that EVERY non-NORM element, not just
// the "advanced" ones (NEOS/ASTRA/TERA), requires a lineage unlock; only NORM is free. The
// starting champion's Subcards are patched with a real WATER champion (Nico, Rapture's
// Embrace) to unlock element access. Base case only: without a CLERIC Class Bonus champion, it
// unconditionally draws a card into memory. The [Class Bonus][Memory 4+] Glimpse 3 clause is
// out of scope (a separate, richer effect requiring both a class-bonus champion patch and 4
// memory cards seeded).
// Notes carried over from the original definition:
//   - WATER lineage/element unlock (Nico, Rapture's Embrace)
gaScenario('stream-of-consciousness-draw-into-memory')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Nico, Rapture\'s Embrace'])
    ->seed('myHand', 'Stream of Consciousness')
    ->castSpell('Stream of Consciousness', pay: 2)
    ->expectZoneCount('myMemory', 3, 1, 'Draw a card into your memory (base case, no Class Bonus)')
    ->register();

// --- Serum of Wisdom: Brew (Three Herbs); Sacrifice: Glimpse 3, draw a card into memory ---
// Serum of Wisdom's element is NORM. Brewing sacrifices three Herb-subtype objects instead of
// paying reserve; three Blightroot tokens are seeded onto the field to pay it. Sacrifice:
// Glimpse 3 (an MZREARRANGE decision, resolved keeping original order, same no-op default used
// in idle-thoughts-glimpse-4) then queues a draw into memory.
// Notes carried over from the original definition:
//   - Blightroot (HERB) #1 - brew ingredient
//   - Blightroot (HERB) #2 - brew ingredient
//   - Blightroot (HERB) #3 - brew ingredient
//   - MZREARRANGE response: keep original order (same no-op default as idle-thoughts-glimpse-4).
gaScenario('serum-of-wisdom-brew-sacrifice-glimpse-draw')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Blightroot')
    ->seed('myField', 'Blightroot')
    ->seed('myField', 'Blightroot')
    ->seed('myHand', 'Serum of Wisdom')
    ->play('Serum of Wisdom')->answer(1, 'YES')->target('myField-1')->target('myField-2')->target('myField-3')
    ->passBoth()
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->register();

// --- Scry the Skies: Glimpse LV. Draw a card into your memory ---
// Scry the Skies is NORM, no lineage patch needed. The starting champion's level is patched to
// 2 so Glimpse LV (0 by default, which would be an invisible no-op) is directly observable via
// a real 2-card MZREARRANGE decision.
// Notes carried over from the original definition:
//   - LV for Glimpse LV
//   - MZREARRANGE response: keep original order (same no-op default as idle-thoughts-glimpse-4).
gaScenario('scry-the-skies-glimpse-lv-draw')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(level: 2)
    ->seed('myHand', 'Scry the Skies')
    ->play('Scry the Skies')->pay(1)->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->expectZoneCount('myMemory', 2, 1, 'Glimpse LV (2) then draw a card into memory')
    ->register();

// --- Song of Nurturing: allies +2 LIFE, [Class Bonus] also +1 POWER, until end of turn ---
// Song of Nurturing is NORM. The starting champion's CardID is patched directly to Silvie,
// With the Pack (TAMER) so the Class Bonus applies. Gray Wolf is seeded onto the field as the
// ally that receives the buff. Confirmed via computed_life_equals (base 2 + 2 = 4) and
// computed_power_equals (base 2 + 1 Class Bonus = 3).
// Notes carried over from the original definition:
//   - Silvie, With the Pack (TAMER)
//   - Gray Wolf -- the ally that receives the buff
gaScenario('song-of-nurturing-class-bonus-life-power')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Silvie, With the Pack')
    ->seed('myField', 'Gray Wolf')
    ->seed('myHand', 'Song of Nurturing')
    ->castSpell('Song of Nurturing', pay: 2)
    ->register();

// --- Silvie, Loved by All: Animal and Beast allies get +1 LIFE and have intercept ---
// The starting champion's CardID is patched directly to Silvie, Loved by All itself (the card
// under test), bypassing Silvie Lineage's level-up gate the same way established for other
// champion fixtures this session. Gray Wolf (BEAST) is seeded onto the field. The +1 LIFE half
// of the static ability is confirmed via computed_life_equals (base 2 + 1 = 3). The "has
// intercept" half (HasIntercept(), GameLogic.php ~21762-21769, confirmed via direct code read
// to share the same Animal-or-Beast-ally + Silvie-on-field check) has no dedicated assertion
// type in this harness and would need a real combat interception sequence to observe -- out of
// scope here, matching this session's treatment of other keyword-only clauses (e.g. Pride).
// Notes carried over from the original definition:
//   - Silvie, Loved by All
//   - Gray Wolf (BEAST)
//   - harmless no-op click
gaScenario('silvie-loved-by-all-animal-beast-life-intercept')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Silvie, Loved by All')
    ->seed('myField', 'Gray Wolf')
    ->target('myField-0')
    ->tested('Silvie, Loved by All')
    ->register();

// --- Silvie, With the Pack: On Enter: draw if Animal ally, draw if Beast ally ---
// Silvie, With the Pack is level 2 (Silvie Lineage), but CanChampionLevelUpIntoCard only checks
// targetLevel === currentLevel + 1 -- lineage text is not enforced by the level-up gate itself
// (same technique established for Arisanna's champions). The starting champion is patched
// directly to Silvie, Wilds Whisperer (level 1) so a real level-up is legal. Champion-swap
// materialization is only offered through the material-phase MZMAYCHOOSE at the start of a
// turn, so both players end their first turn (P1 -> P2) to reach that prompt; its 2-memory cost
// is paid from two filler cards seeded into myMemory. Giant Tortoise (ANIMAL) and Gray Wolf
// (BEAST) are seeded onto the field beforehand so both On Enter draw clauses trigger.
// Notes carried over from the original definition:
//   - Silvie, Wilds Whisperer (level 1) - level-up precondition
//   - Giant Tortoise (ANIMAL)
//   - Gray Wolf (BEAST)
//   - filler card 1/2 in memory to pay the 2-memory level-up cost
//   - filler card 2/2
gaScenario('silvie-with-the-pack-on-enter-draw-animal-beast')
    ->deckTemplate('silvie-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Silvie, Wilds Whisperer')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myField', 'Gray Wolf')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')
    ->tested('Silvie, With the Pack')
    ->register();

// --- Silvie, Wilds Whisperer: On Enter: next Animal/Beast ally activated enters with a buff counter ---
// Silvie, Wilds Whisperer is level 1, one level above the default level-0 starting champion, so
// no lineage/level patch is needed -- 0+1 is already a legal level-up (same as arisanna-
// herbalist-prodigy-on-enter-gather-twice). Champion-swap materialization is only offered
// through the material-phase MZMAYCHOOSE at the start of a turn, so both players end their
// first turn (P1 -> P2) to reach that prompt; its 1-memory cost is paid from a filler card
// seeded into myMemory. Her On Enter sets a "next Animal/Beast ally" flag (AddGlobalEffects);
// Gray Wolf (BEAST) is then materialized from hand in the same turn to consume it, entering
// with an extra buff counter (GameLogic.php ~8009-8025).
// Notes carried over from the original definition:
//   - filler card in memory to pay the 1-memory level-up cost
gaScenario('silvie-wilds-whisperer-on-enter-buff-next-animal-beast')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Silvie, Wilds Whisperer
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK
    )
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myHand', 'Gray Wolf')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')
    ->castSpell('Gray Wolf', pay: 2)
    ->tested('Silvie, Wilds Whisperer')
    ->register();

// --- Smack with Flute: On Attack: champion +1 level until end of turn ---
// Smack with Flute is NORM, no lineage patch needed; its On Attack level bonus is unconditional
// ([Class Bonus] Floating Memory is a separate, unrelated ability on the same card, out of
// scope). Same real single-target ATTACK-card flow as wind-cutter-class-bonus-power-attack: the
// card is played from hand into myIntent, then the champion attacks with no weapon available
// (GetAttackWeaponChoices returns empty, so BeginCombatPhase skips straight to
// ChooseAttackTarget). On Attack fires as soon as the attack is declared, before damage
// resolves, so its effect (AddGlobalEffects "zpkcFs72Ah") is asserted right after choosing the
// target, before the defender's Retaliate response.
// Notes carried over from the original definition:
//   - Smack with Flute
//   - target opponent's champion
gaScenario('smack-with-flute-on-attack-level')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myHand', 'Smack with Flute')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->castSpell('Smack with Flute', pay: 2)
    ->action(1, 10002, 'myField-0!FSM!')->target('theirField-0')
    ->register();

// --- Surveillance Stone: opponent's 3rd attack each turn, may banish to draw ---
// Surveillance Stone is REGALIA -- seeded directly onto myField (P1's side). Its trigger
// (CombatLogic.php ~2742-2751) fires when the OPPONENT's OnAttackCallCount reaches exactly 3
// for their turn, offering the owner (P1) a YESNO to banish it and draw. 3 separate Dungeon
// Guide allies are seeded onto P2's field (each patched awake) so P2 can declare 3 real attacks
// in a single turn, same real attack-declaration sequence (P1 pass, P2 pass, P1 formally ends
// turn 1 via the mid-game Pass button, P2 declines their MAT offer) as swift-recruit-intercept-
// redirect for reaching P2's own attacking turn.
// Notes carried over from the original definition:
//   - Surveillance Stone
//   - Dungeon Guide attacker 1
//   - Dungeon Guide attacker 2
//   - Dungeon Guide attacker 3
//   - P1 declines their own MAT-phase materialize offer
//   - P1 formally ends turn 1 (nothing to do)
//   - P2 declines their MAT-phase materialize offer
//   - attack 1/3
//   - attack 2/3
//   - attack 3/3
//   - P1 banishes Surveillance Stone to draw
gaScenario('surveillance-stone-third-attack-banish-draw')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->seed('myField', 'Surveillance Stone')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->patch('myField-1', ['Status' => 2], player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->patch('myField-2', ['Status' => 2], player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->patch('myField-3', ['Status' => 2], player: 2)
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->endTurn()->pass(2)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')
    ->action(2, 10002, 'myField-2!FSM!')->answer(2, 'theirField-0')
    ->action(2, 10002, 'myField-3!FSM!')->answer(2, 'theirField-0')->answer(1, 'YES')
    ->tested('Surveillance Stone')
    ->expectDqEmpty()
    ->expectDamage('p1Field-0', 3)
    ->expectProperty('p1Field-0', 'Counters', '{"_champDamageThisTurn":3}')
    ->expectProperty('p1Field-0', 'TurnEffects', '["WAS_ATTACKED","DAMAGED_SINCE_LAST_TURN"]')
    ->expectProperty('p1Banish-0', 'CardID', 'kk46Whz7CJ', 'Surveillance Stone was banished after the third attack')
    ->expectProperty('p1Banish-0', 'Status', '2')
    ->expectDamage('p1Banish-0', 0)
    ->expectProperty('p1Banish-0', 'Counters', '[]')
    ->expectProperty('p1Banish-0', 'TurnEffects', '[]')
    ->expectProperty('p2Field-1', 'Status', '1')
    ->expectProperty('p2Field-1', 'TurnEffects', '[]')
    ->expectProperty('p2Field-2', 'Status', '1')
    ->expectProperty('p2Field-2', 'TurnEffects', '[]')
    ->expectProperty('p2Field-3', 'Status', '1')
    ->expectProperty('p2Field-3', 'TurnEffects', '[]')
    ->expectZoneCount('myHand', 9, 1)
    ->expectZoneCount('myField', 1, 1)
    ->expectZoneCount('theirHand', 9, 1)
    ->register();

// --- Spirit Blade: Ghost Strike: On Attack, may banish a material card for +1 POWER ---
// Spirit Blade: Ghost Strike is CRUX element (verified via CardElement(), not NORM as
// cardArrayCache.json's unreliable elements field would suggest) -- champion CardID-patched
// directly to Lorraine, Crux Knight (WARRIOR + CRUX) to unlock it, same technique as the
// Rai deck's ARCANE cards. 0 reserve cost, so played straight to attack (ATTACK cards go
// into myIntent via FSM, not through the DoActivateCard fast-action opportunity flow --
// confirmed via wind-cutter-class-bonus-power-attack). Its On Attack ability
// (onAttackAbilities["vcZSHNHvKX:0"]) offers a MZMAYCHOOSE to banish a material-deck card for
// +1 POWER on the champion's attacks (AddGlobalEffects "vcZSHNHvKX",
// doesGlobalEffectApply["vcZSHNHvKX"]); the deck's own default Material leftovers (Lorraine
// Wandering Warrior, Clarent, Backup Charger, Purifying Thurible) supply a real choice.
gaScenario('spirit-blade-ghost-strike-banish-material-power')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Crux Knight')
    ->seed('myHand', 'Spirit Blade: Ghost Strike')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->play('Spirit Blade: Ghost Strike')
    ->action(1, 10002, 'myField-0!FSM!')->target('theirField-0')->target('myMaterial-0')->answer(2, '-')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'Status', '1')
    ->expectProperty('p1Field-0', 'Counters', '{"_champCombatDamageDealtThisTurn":2}')
    ->expectProperty('p1Graveyard-0', 'CardID', 'vcZSHNHvKX')
    ->expectProperty('p1Banish-0', 'CardID', 'DpHDGaX2Pn', 'Lorraine, Wandering Warrior was banished from the material deck')
    ->expectProperty('p1Banish-0', 'Counters', '[]')
    ->expectProperty('p1Banish-0', 'TurnEffects', '[]')
    ->expectProperty('p1Material-0', 'CardID', 'm31WVJ9F04')
    ->expectProperty('p1Material-1', 'CardID', '9gv4vm4kj3')
    ->expectProperty('p1Material-2', 'CardID', 'LeyUk5auEP')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'vcZSHNHvKX')
    ->expectProperty('p1GlobalEffects-1', 'CardID', 'CHAMP_DEALT_COMBAT_DMG')
    ->expectDamage('p2Field-0', 2, 'Banishing a material card gave +1 POWER: 1 + 1 = 2 damage')
    ->expectProperty('p2Field-0', 'Counters', '{"_champDamageThisTurn":2}')
    ->expectProperty('p2Field-0', 'TurnEffects', '["WAS_ATTACKED","DAMAGED_SINCE_LAST_TURN","CHAMP_DMG_BY_P1"]')
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// --- Savage Slash: [Class Bonus] Floating Memory ---
// Savage Slash's only ability is [Class Bonus] Floating Memory, gated on WARRIOR Class
// Bonus (unlike honorable-vanguard-floating-memory's unconditional version). Floating Memory
// is a MEMORY-cost payment source (champion leveling / memory-cost REGALIA materialize) --
// NOT a reserve-cost payment source (that's the separate "Reservable" keyword, checked via
// GetReservablePaymentSources against field objects only; an initial draft of this fixture
// wrongly assumed Floating Memory applied to reserve costs too and failed with "Invalid
// selection" on the ReserveCard MZCHOOSE, whose Param turned out to be the literal zone name
// "myHand" with no graveyard entries). Champion CardID-patched to Lorraine, Wandering
// Warrior (level 1, WARRIOR -- already gives the Class Bonus), then leveled up for real
// (level 1 -> 2, legal since target == current + 1) into Lorraine, Blademaster on P1's turn 3
// material phase, paying its 2-memory cost with Savage Slash from myGraveyard (Floating
// Memory) plus 1 real myMemory filler card.
gaScenario('savage-slash-class-bonus-floating-memory')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Lorraine, Blademaster
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK
    )
    ->champion(cardName: 'Lorraine, Wandering Warrior')
    ->seed('myGraveyard', 'Savage Slash')
    ->seed('myMemory', 'Dungeon Guide')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-1')->target('myGraveyard-0')
    ->tested('Savage Slash')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'CardID', 'TJTeWcZnsQ', 'The champion levelled up into Lorraine, Blademaster')
    ->expectProperty('p1Field-0', 'TurnEffects', '["ENTERED_THIS_TURN","TJTeWcZnsQ"]')
    ->expectProperty('p1Banish-0', 'CardID', '4a7QLLouGk', 'Savage Slash was banished from the graveyard to pay 1 of the champion\'s memory cost')
    ->expectProperty('p1Banish-0', 'Counters', '[]')
    ->expectProperty('p1Banish-0', 'TurnEffects', '[]')
    ->expectProperty('p1Banish-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Banish-1', 'Counters', '[]')
    ->expectProperty('p1Banish-1', 'TurnEffects', '[]')
    ->expectProperty('p1Material-1', 'CardID', 'm31WVJ9F04')
    ->expectProperty('p1Material-2', 'CardID', '9gv4vm4kj3')
    ->expectProperty('p1Material-3', 'CardID', 'LeyUk5auEP')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'LEVELED_UP_THIS_TURN')
    ->expectZoneCount('myHand', 8, 1)
    ->expectZoneCount('myGraveyard', 0, 1)
    ->expectZoneCount('myMemory', 0, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// --- Spirit Blade: Ascension: additional cost return Sword to material; fetch a Sword to field ---
// Spirit Blade: Ascension is CRUX element (champion CardID-patched to Lorraine, Crux Knight
// to unlock it). Its ability (cardActivatedAbilities["N0ipz8UWwf:0"]) requires controlling a
// REGALIA,SWORD on the field to return to material, then lets you fetch a REGALIA,SWORD from
// material or banishment onto the field. Clarent, Sword of Peace is seeded directly onto the
// field as the Sword to return; Warrior's Longsword is seeded into material as the Sword to
// fetch back.
// Notes carried over from the original definition:
//   - Clarent, Sword of Peace -- the Sword to return
//   - Warrior's Longsword -- the Sword to fetch back
gaScenario('spirit-blade-ascension-swap-sword')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Crux Knight')
    ->seed('myField', 'Clarent, Sword of Peace')
    ->seed('myMaterial', 'Warrior\'s Longsword')
    ->seed('myHand', 'Spirit Blade: Ascension')
    ->play('Spirit Blade: Ascension')->pay(1)->pass(1)->target('myField-1')->target('myMaterial-4')
    ->expectDqEmpty(step: 5)
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]', step: 5)
    ->expectProperty('p1Field-1', 'CardID', 'jF1VuIR7a6', 'Warrior\'s Longsword was fetched from the material deck onto the field', step: 5)
    ->expectProperty('p1Graveyard-0', 'CardID', 'N0ipz8UWwf', step: 5)
    ->expectProperty('p1Material-4', 'CardID', 'm31WVJ9F04', step: 5)
    ->expectZoneCount('myHand', 6, 1, step: 5)
    ->expectZoneCount('myGraveyard', 1, 1, step: 5)
    ->expectZoneCount('myMemory', 1, 1, step: 5)
    ->register();

// --- Spirit Blade: Dispersion: strip durability from Swords, split damage among units ---
// Spirit Blade: Dispersion is CRUX element, 0 reserve (champion CardID-patched to Lorraine,
// Crux Knight to unlock it). Its ability (SpiritBladeDispersion, CardLogic.php) removes
// durability counters from a chosen Sword weapon, banishes it, and splits that much damage
// among chosen units via an MZSPLITASSIGN decision. Clarent, Sword of Peace is seeded onto
// the field with 2 durability counters; the answer format for MZSPLITASSIGN
// (ProcessSplitDamage, GameLogic.php) is "targetMZ:amount" regardless of how the decision's
// own Param field happens to be ordered (confirmed by reading ProcessSplitDamage directly --
// this card's own DQ handler builds Param as "targets|amount|tooltip", the reverse of every
// other MZSPLITASSIGN caller's "amount|targets" convention, which would even confuse
// GoldfishResolveDecisionInput's generic auto-resolver; a genuine minor engine inconsistency
// noted here but not fixed, since fixing engine behavior is out of scope for fixture
// authoring). All 2 damage assigned to P2's Dungeon Guide.
gaScenario('spirit-blade-dispersion-split-damage')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Crux Knight')
    ->seed('myField', 'Clarent, Sword of Peace', properties: ['Counters' => ['durability' => 2]])
    ->seed('myHand', 'Spirit Blade: Dispersion')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->play('Spirit Blade: Dispersion')->pass(1)->target('myField-1')->answer(1, 'theirField-1:2')
    ->expectDqEmpty(step: 4)
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]', step: 4)
    ->expectProperty('p1Graveyard-0', 'CardID', '7Rsid05Cf6', step: 4)
    ->expectProperty('p1Banish-0', 'CardID', 'm31WVJ9F04', 'Clarent, Sword of Peace was banished after its durability counters were removed', step: 4)
    ->expectProperty('p1Banish-0', 'Counters', '[]', step: 4)
    ->expectProperty('p1Banish-0', 'TurnEffects', '["ENTERED_THIS_TURN"]', step: 4)
    ->expectDamage('p2Field-1', 2, 'The 2 damage (Clarent\'s durability counters) was assigned to the Dungeon Guide', step: 4)
    ->expectProperty('p2Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DAMAGED_SINCE_LAST_TURN"]', step: 4)
    ->expectZoneCount('myHand', 7, 1, step: 4)
    ->expectZoneCount('myField', 1, 1, step: 4)
    ->expectZoneCount('myGraveyard', 1, 1, step: 4)
    ->register();

// --- Sword of Seeking: [Class Bonus] True Sight ---
// Sword of Seeking's only ability is [Class Bonus] True Sight (HasKeyword_TrueSight, gated
// on WARRIOR Class Bonus) -- champion CardID-patched directly to Lorraine, Blademaster
// (WARRIOR), Sword of Seeking seeded directly onto the field (its ability is a static
// wielded-weapon check, not an Enter trigger). Gildas, Faesworn Monarch (unconditional
// Stealth) is seeded onto P2's field as the attack target: AttackerHasTrueSight
// (CombatLogic.php) checks the wielded weapon for True Sight, which is what allows Stealth
// units to be legally targeted at all (GetChooseAttackTargets filters them out otherwise) --
// successfully targeting Gildas is itself the proof.
// Notes carried over from the original definition:
//   - Sword of Seeking
//   - Gildas, Faesworn Monarch (Stealth)
gaScenario('sword-of-seeking-class-bonus-true-sight')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Blademaster')
    ->seed('myField', 'Sword of Seeking')
    ->seed('myField', 'Gildas, Faesworn Monarch', player: 2)
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-1')->answer(2, '-')
    ->tested('Sword of Seeking')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'Status', '1')
    ->expectProperty('p1Field-0', 'Counters', '{"_champCombatDamageDealtThisTurn":1}')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":1}')
    ->expectProperty('p1Field-1', 'TurnEffects', '[]')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'CHAMP_DEALT_COMBAT_DMG')
    ->expectDamage('p2Field-1', 1, 'True Sight let the attack target the stealth unit, which took 1 damage')
    ->expectProperty('p2Field-1', 'TurnEffects', '["DAMAGED_SINCE_LAST_TURN","CHAMP_DMG_BY_P1"]')
    ->expectZoneCount('myHand', 8, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// --- Sudden Steel: [Class Bonus] Efficiency ---
// Sudden Steel's only ability is [Class Bonus] Efficiency (reduce reserve cost by champion's
// current level). Champion CardID-patched directly to Lorraine, Blademaster (WARRIOR, level
// 2), reducing the printed 6 reserve cost to 4. Played into myIntent via FSM (ATTACK cards
// don't go through the fast-action opportunity flow until after the attack is declared, per
// wind-cutter-class-bonus-power-attack), then attacks the opponent's champion directly.
gaScenario('sudden-steel-class-bonus-efficiency')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Blademaster')
    ->seed('myHand', 'Sudden Steel')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->castSpell('Sudden Steel', pay: 4)
    ->action(1, 10002, 'myField-0!FSM!')->target('theirField-0')->answer(2, '-')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'Status', '1')
    ->expectProperty('p1Field-0', 'Counters', '{"_champCombatDamageDealtThisTurn":5}')
    ->expectProperty('p1Graveyard-0', 'CardID', 'SSu2eQZFJV')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'CHAMP_DEALT_COMBAT_DMG')
    ->expectDamage('p2Field-0', 5, 'Sudden Steel deals its printed 5 POWER to the opposing champion')
    ->expectProperty('p2Field-0', 'Counters', '{"_champDamageThisTurn":5}')
    ->expectProperty('p2Field-0', 'TurnEffects', '["WAS_ATTACKED","DAMAGED_SINCE_LAST_TURN","CHAMP_DMG_BY_P1"]')
    ->expectZoneCount('myHand', 4, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 4, 1, 'Efficiency: at champion level 2 the printed 6 reserve cost takes 4 payments')
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// --- Sleety Retreat: target Ranger ally/champion becomes distant (j9fkuzgg9i) ---
// Notes carried over from the original definition:
//   - WATER lineage/element unlock
//   - Bertha, Spry Howitzer (RANGER ally)
gaScenario('sleety-retreat-target-distant')
    ->deck($fillerDeck)
    ->champion(lineage: ['Spirit of Water'])
    ->seed('myField', 'Bertha, Spry Howitzer', properties: ['TurnEffects' => []])
    ->seed('myHand', 'Sleety Retreat')
    ->castSpell('Sleety Retreat', pay: 2, target: 'myField-1')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Field-1', 'TurnEffects', '["DISTANT"]', 'Playing Sleety Retreat fires its on-play trigger (playCardAbilities["j9fkuzgg9i:0"]) and tags the target Ranger ally with DISTANT')
    ->expectProperty('p1Hand-2', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-3', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-4', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', 'j9fkuzgg9i')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'n8wyfG9hbY')
    ->expectZoneCount('myHand', 5, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 2, 1)
    ->register();

// --- Sacred Barrier: the next non-combat damage to each ally this turn is prevented by 4 ---
// Sacred Barrier's element is NORM, so no lineage patch is needed. Cunning Broker (an ALLY
// with no On Enter trigger of its own, unlike Fluffy Shopkeep/Dungeon Guide -- verified live
// that seeding either of those left a dangling MZMAYCHOOSE/CUSTOM Enter decision in the queue
// that blocked every subsequent FSM action) is seeded onto the field as the beneficiary of the
// "each ally" clause. Activating a card puts it on the EffectStack behind an Opportunity
// window (verified live via a throwaway EffectStack/DQ dump) -- both players must pass their
// response window (P1's own MZMAYCHOOSE offering to respond by activating Cunning Broker's
// Rest ability, then P2's) before Sacred Barrier actually resolves.
// Notes carried over from the original definition:
//   - Cunning Broker (ALLY, no On Enter trigger)
gaScenario('sacred-barrier-prevent-noncombat')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Cunning Broker')
    ->seed('myHand', 'Sacred Barrier')
    ->castSpell('Sacred Barrier', pay: 2, window: 'both')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","PREVENT_NONCOMBAT_4"]', 'Each ally gains the next-non-combat-damage-prevented-by-4 turn effect')
    ->expectProperty('p1Hand-2', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-3', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-4', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', 'hYDqthNDpB')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'n8wyfG9hbY')
    ->expectZoneCount('myHand', 5, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 2, 1)
    ->register();

// --- Shared Fervor: Each player draws a card into their memory. You gain the Crowd's Favor status ---
// Shared Fervor's element is NORM, so no lineage patch is needed.
gaScenario('shared-fervor-memory-crowds-favor')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myHand', 'Shared Fervor')
    ->castSpell('Shared Fervor', pay: 1)
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Hand-0', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-2', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-4', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-5', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', 'RnUpMoSb4w')
    ->expectProperty('p1Graveyard-0', 'Counters', '[]')
    ->expectProperty('p1Graveyard-0', 'TurnEffects', '[]')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'gpmJdGYqoC', 'Gains the Crowd\'s Favor status')
    ->expectProperty('p2Memory-0', 'CardID', 'em6eEh9q8y')
    ->expectZoneCount('myHand', 6, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 2, 1, 'Own player draws a card into memory (1 from reserve payment, +1 from the card\'s own effect)')
    ->expectZoneCount('theirMemory', 1, 1, 'Opponent also draws a card into their memory')
    ->register();

// --- Sable Remnant: [Class Bonus] +1 POWER ---
// Sable Remnant's element is NORM, so only the Class Bonus condition needs a precondition:
// the starting champion's CardID is patched directly to Zander, Deft Executor (ASSASSIN) so
// IsClassBonusActive($player, ["ASSASSIN"]) is true. Sable Remnant's printed POWER is 1
// (verified via CardPower()); the computed_power_equals assertion of 2 is only possible if the
// static +1 POWER case in GameLogic.php's power-modifier switch actually applied. A single
// harmless "end turn 1" action is included even though nothing about ending the turn matters
// to this static ability -- RunIntegrationTests.php's step-0 (pre-action) assertion check
// never calls ParseGamestate() itself, so with zero actions it reads stale runtime globals
// left over from whichever fixture ran immediately before this one in the same process
// (verified live: computed_power_equals came back as an unrelated leftover value), while the
// step-1 (post-action) check is always evaluated against this fixture's own freshly-parsed
// state.
// Notes carried over from the original definition:
//   - Zander, Deft Executor (ASSASSIN)
//   - Sable Remnant, seeded straight onto the field
//   - harmless: ends turn 1
gaScenario('sable-remnant-class-bonus-power')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Zander, Deft Executor')
    ->seed('myField', 'Sable Remnant')
    ->endTurn()
    ->tested('Sable Remnant')
    ->register();

// --- Scorching Imperilment: at each end phase, that player may discard a card to draw a card ---
// Scorching Imperilment's element is FIRE, matching the starting champion, so no lineage patch
// is needed. It is seeded directly onto the field (materialize flow, including its own Class
// Bonus cost discount, out of scope). Ending player 1's turn 1 (myHealth-0!CustomInput!Pass)
// passes through player 1's own end phase, where the unconditional end-phase check in
// GameLogic.php (~line 10817) queues the "discard a card to draw a card" MZMAYCHOOSE for the
// turn player (player 1) as long as any Scorching Imperilment is on either player's field.
// Notes carried over from the original definition:
//   - Scorching Imperilment
//   - ends turn 1, passing through end phase
//   - discard a card
gaScenario('scorching-imperilment-end-phase-discard-draw')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Scorching Imperilment')
    ->endTurn()->pay(1)
    ->tested('Scorching Imperilment')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'TurnEffects', '[]')
    ->expectProperty('p1Hand-0', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-2', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-4', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-5', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', 'px60u5n1do', 'End phase offers a discard (which pairs with drawing a replacement card)')
    ->expectProperty('p2Hand-7', 'CardID', 'em6eEh9q8y')
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// --- Scatter Essence: destroy target phantasia ---
// Scatter Essence (zi5h8asbie, WIND -- native, no champion patch needed) targets an Unstable
// Fractal (2o82fwl22v, PHANTASIA) seeded onto the opponent's field.
// cardActivatedAbilities["zi5h8asbie:0"] (GeneratedMacroCode.php) offers PHANTASIA objects as
// MZCHOOSE targets; its handler (customDQHandlers["zi5h8asbie:0:CardActivated-1"]) moves the
// chosen target to its owner's graveyard.
gaScenario('scatter-essence-destroy-phantasia')
    ->deck(GA_LORRAINE_PANTHEON_DECK)
    ->seed('myField', 'Unstable Fractal', player: 2)
    ->seed('myHand', 'Scatter Essence')
    ->castSpell('Scatter Essence', pay: 2, window: 'both', target: 'theirField-1')
    ->expectDqEmpty()
    ->expectZoneCount('theirField', 1, 1)
    ->expectZoneCount('theirGraveyard', 1, 1, 'The targeted phantasia leaves the opponent\'s field and lands in their graveyard.')
    ->register();

// --- Slay the King: [Class Bonus] On Attack, may banish a material card ("On Kill: play it") ---
// Slay the King (6v374coy34, CRUX ATTACK, WARRIOR [Class Bonus]) needs both CRUX and WARRIOR --
// the champion is patched to Lorraine, Spirit Ruler (n2TKqNaODR), which is itself WARRIOR-class
// CRUX-element, satisfying both at once. Attacking is locked on turn 1
// (CanActivateAttackCardNow), so both players end their first turns to reach player 1's second
// turn, materializing Sword of Seeking (Dz8I0eJzaf, 0-memory WARRIOR/SWORD weapon already in
// this deck's Material zone) so the champion has a nonzero attack power. Slay the King is then
// played as a real attack (reserve cost 2, paid before the attacker/weapon/target declaration,
// matching this card's own DQ ordering) against the opponent's champion.
// onAttackAbilities["6v374coy34:0"] (GeneratedMacroCode.php) offers a YES/NO to banish a
// material-deck card (SlayTheKingOnAttack, Custom/CardDQHandlers.php); answering YES and
// choosing myMaterial-0 (Lorraine, Wandering Warrior) banishes it, observable as the card
// moving from material to banishment. (The follow-up "On Kill: may play the banished card" is
// out of scope -- it requires a full combat kill, not just a hit.)
gaScenario('slay-the-king-banish-material')
    ->deck(GA_LORRAINE_PANTHEON_DECK)
    ->champion(cardName: 'Lorraine, Spirit Ruler')
    ->seed('myHand', 'Slay the King')
    ->endTurn()
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)->target('myMaterial-7')->answer(2, '-')
    ->play('Slay the King')->pay(2)->answer(2, '-')
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')->answer(2, '-')
    ->answer(1, 'YES')->target('myMaterial-0')
    ->expectProperty('p1Banish-0', 'CardID', 'DpHDGaX2Pn', 'The chosen material-deck card (Lorraine, Wandering Warrior) is banished as a direct result of Slay the King\'s own on-attack trigger.')
    ->expectZoneCount('myBanish', 1, 1)
    ->register();

// --- Savage Attack: Floating Memory pays a champion level-up's memory cost from graveyard ---
// Savage Attack's entire printed text is the Floating Memory keyword (while paying a memory
// cost, you may banish this card from your graveyard to pay for 1 of that cost) -- same test
// shape as shieldroid-floating-memory/stalwart-shieldmate-floating-memory/etc: seeded directly
// into the graveyard with no myMemory filler, then Lorraine, Wandering Warrior's 1-memory
// champion level-up (myMaterial-0, reached via a real Materialize opportunity on player 1's
// second turn) is paid entirely from Savage Attack via QueueMaterializeFloatingPaymentChoice
// (Custom/MaterializeLogic.php), observable as it moving from graveyard to banishment.
gaScenario('savage-attack-floating-memory')
    ->deck(GA_LORRAINE_PANTHEON_DECK)
    ->seed('myGraveyard', 'Savage Attack')
    ->endTurn()
    ->endTurn()
    ->endTurn(2)
    ->endTurn()
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn()
    ->endTurn(2)
    ->endTurn()->target('myMaterial-0')->target('myGraveyard-0')->target('myMaterial-6')
    ->tested('Savage Attack')
    ->expectProperty('p1Banish-0', 'CardID', '3ewnten2rn', 'Savage Attack is banished from the graveyard to pay the champion level-up\'s memory cost, instead of it happening any other way.')
    ->expectProperty('p1Field-0', 'CardID', 'DpHDGaX2Pn')
    ->register();

// --- Set Ablaze: deal 4 damage to target ally ---
// Set Ablaze (reserve 2, FIRE -- native to the Spirit of Fire starting champion, no element
// patch needed) deals a flat 4 damage to a chosen ally (GeneratedMacroCode.php
// customDQHandlers["d4z3tj2nu8:0:CardActivated-1"]); the [Class Bonus][Level 3+] extra
// champion-wide damage is intentionally not exercised (base deck has no MAGE class bonus and
// is level 1). A Dungeon Guide ally is seeded onto the opposing field as the target.
// Notes carried over from the original definition:
//   - Dungeon Guide (ALLY) target
gaScenario('set-ablaze-damage-ally')
    ->deck(GA_KONGMING_BASE_DECK)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Set Ablaze')
    ->castSpell('Set Ablaze', pay: 2, target: 'theirField-1')
    ->register();

// --- Samaritan's Reach: deal 3 damage to the current attacking ally (fast, played mid-combat) ---
// Samaritan's Reach (reserve 3, Fast/Speed=true) calls SamaritanReachResolve() directly
// (GeneratedMacroCode.php cardActivatedAbilities["MskPCrbv0L:0"]), which reads the
// CombatAttacker decision-queue variable set by a REAL in-progress attack
// (CombatLogic.php MarkCombatAttacker/StoreCombatAttackerState) rather than taking a chosen
// target -- a prior attempt at this fixture (see abandoned note further up this file) found
// that seeding those dqVariables via the 'setup' primitive doesn't survive to resolution, so
// this fixture instead scripts a real attack: player 1 passes turn 1 (first-player attack
// lock), player 2 declares an attack with Giant Tortoise (an L0RmNaDzhk vanilla ALLY, power 1/
// life 6 so it easily survives the 3 damage) against player 1's champion, then during the
// pre-damage-step Opportunity window (turn player/attacker gets priority first, per
// FinalizeAttackDeclaration -> GrantOpportunityWindow) player 2 passes and player 1 -- who
// does not control the attacker -- plays Samaritan's Reach as a fast response instead of
// passing. The Crowd's Favor sub-clause ("if that ally is attacking a unit you don't
// control") never triggers here since player 1 is both caster and defender of their own
// champion (they DO control what's being attacked) -- that half needs a 3+ player pod and is
// out of scope. The separate combat-perspective regression verifies that defending
// your own champion does not accidentally grant the status.
// Notes carried over from the original definition:
//   - Giant Tortoise (vanilla ALLY, power 1 / life 6), the attacker
//   - end player 1's turn 1 (first-player attack lock)
//   - Fully advancing from player 1's turn 1 to player 2's turn (MAIN phase, TurnPlayer==2)
//   - takes three more explicit passes -- one to close player 1's "play your fast card?"
//   - offer (TryPassFastOpportunityDecision, since they hold Samaritan's Reach), one from
//   - each player to close the BEOP (beginning/end-of-phase) window -- confirmed empirically
//   - by stepping through GetTurnPlayer()/GetCurrentPhase() one action at a time; without all
//   - three, ActionMap()'s "myField" case silently no-ops for player 2 ("Only turn player can
//   - declare attacks") and the FSM click below reports success without ever calling
//   - BeginCombatPhase, which is why an earlier version of this fixture had 0 damage land.
//   - Giant Tortoise declares an attack
//   - target player 1's champion
//   - turn player/attacker passes the pre-damage Opportunity first
//   - player 1 plays Samaritan's Reach instead of passing
gaScenario('samaritan-reach-attacking-ally-damage')
    ->deck(GA_KONGMING_BASE_DECK)
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Samaritan\'s Reach')
    ->endTurn()->passBoth()->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->pass(2)
    ->play('Samaritan\'s Reach')->target('myHand-7')->pay(3)
    ->expectDamage('theirField-1', 3, 'Samaritan\'s Reach deals 3 damage to the current attacking ally, read from the real CombatAttacker decision-queue state')
    ->register();

// --- Silver Soldier: Retort 2, Vigor (fixed root-cause parser gap) ---
// GeneratedKeywordCode.php used to have no entry at all for this card -- HasKeyword_Retort() and
// HasKeyword_Vigor() both returned false for it, because the generic keyword parser
// (Data/ProcessKeywordsGA.php) only ever recognized the FIRST bold "**Keyword**" span on a line,
// and also didn't split a single bold span naming multiple keywords on one comma-separated line
// ("Retort 2, Vigor"). The same class of gap is called out for other cards in GameLogic.php's own
// HasTaunt()/HasVigor()/HasRetort() whitelist comments (Neos Elemental, Genbu, Guan Yu, etc).
// The parser was fixed to scan every bold span on a line (not just the first) and to split a
// single span's comma-separated keyword list, gated by a "pure keyword line" check so an
// incidental bolded mid-sentence mention (e.g. "...with **floating memory** from your
// graveyard...") isn't misread as a real grant. GeneratedKeywordCode.php was regenerated and both
// keywords are now live for Silver Soldier (verified: HasKeyword_Retort=true value=2,
// HasKeyword_Vigor=true). This fixture proves both mechanically in one scenario: pre-resting
// Silver Soldier and ending its controller's turn shows Vigor waking it back up (Status 2) even
// as turn control passes to the opponent -- which is also what makes it eligible to retaliate at
// all (GetRetaliatorOptions requires Status==2) -- and then having the opponent attack it and
// choosing to retaliate shows Retort adding its printed +2 on top of Silver Soldier's base 3
// POWER (5 damage dealt to the attacker), confirmed via GetRetortValue().
// Silver Soldier is seeded directly onto player 1's field, pre-rested (Status 1) to construct
// the "Vigor wakes it up" precondition. Giant Tortoise (vanilla ALLY, power 1 / life 6, also
// used elsewhere in this file as a plain attacker) is seeded onto player 2's field to attack
// it next turn.
// Verified live via a standalone replay harness (dumping decision-queue contents after each
// action) that this exact 4-action sequence is what's needed: after the attack target is
// chosen, player 1's very next decision IS the "Retaliate?" MZMAYCHOOSE (single option,
// myField-1) -- there is no separate fast-opportunity PASS in front of it for either player
// in this deck/scenario (no fast cards in either starting hand), so answering it directly
// with the retaliator's own mzID resolves the rest of combat (attacker damage + retaliation
// damage + cleanup) in one action. An earlier version of this fixture inserted two PASS
// actions here on the assumption the flow would mirror the (unrelated) champion-attack
// Samaritan's Reach fixture's extra opportunity passes -- but with nothing pending for player
// 2 and no opportunity decision queued ahead of player 1's MZMAYCHOOSE, that second PASS was
// instead consumed as declining the MZMAYCHOOSE itself (lastDecision "-"), which skipped
// retaliation entirely.
// Notes carried over from the original definition:
//   - Silver Soldier
//   - pre-rest it so Vigor's wake-up is observable
//   - Giant Tortoise, the attacker
//   - End player 1's turn: triggers Vigor's "wakes up at the beginning of your end phase" on
//   - Silver Soldier even as turn control passes to player 2.
//   - Player 2 (now turn player) declares Giant Tortoise's attack against Silver Soldier.
//   - Defender (p1) chooses Silver Soldier as the retaliator, which resolves the whole combat.
gaScenario('silver-soldier-retort-vigor')
    ->standardDeck('Windslice')
    ->seed('myField', 'Silver Soldier')
    ->patch('myField-1', ['Status' => 1])
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-1')->target('myField-1')
    ->tested('Silver Soldier')
    ->register();

// Shade Striker (hVvsKqWsMl, printed "Ambush") -- root-cause keyword-parser fix.
// Ambush (Comprehensive Rules): "This unit may retaliate against attackers while it isn't
// defending." CombatLogic.php's retaliation-eligibility check (GetRetaliatorOptions, ~line 1095)
// used to hardcode a short whitelist of CardIDs that got generic-AMBUSH retaliator treatment
// (jozihslnhz, 0oyxjld8jh, itwys9kf4r, 8tYVFYnK0T, TScoOwz80U) plus a TurnEffect "AMBUSH" check
// for temporary grants -- Shade Striker's CardID was in neither list. The real root cause: "Ambush"
// was entirely missing from Data/ProcessKeywordsGA.php's recognized keyword list, so the generic
// keyword parser never emitted a HasKeyword_Ambush() entry for ANY card, printed or not -- the
// whitelist was a workaround for that gap, not a card-specific issue. Fixed by adding "Ambush" to
// the recognized keyword list and regenerating GeneratedKeywordCode.php (confirmed: all five
// previously-whitelisted cards AND Shade Striker now get a HasKeyword_Ambush() entry from the
// existing generic parser -- including the comma-separated "Ambush, Retort 2" / "Ambush, Retort 2,
// Stealth" lines on Guan Yu and Aquaveil Ambusher, already handled by the sibling multi-keyword
// parser fix). CombatLogic.php's whitelist was replaced with a generic HasAmbush() helper that
// checks HasKeyword_Ambush() first (covering every printed-Ambush card, including future ones),
// then falls back to the TurnEffect grant and the two conditional static grants (Gloamspire
// Mantle, Changban) that were already generic. This fixture proves Shade Striker's Ambush actually
// lets it retaliate against an attack made on a DIFFERENT ally it controls: player 1 controls
// Giant Tortoise (the attack's actual target) and Shade Striker (power 2, not itself being
// attacked); when player 2's Giant Tortoise attacks player 1's Giant Tortoise, the Retaliate
// MZMAYCHOOSE offers both the actual defender (myField-0) and Shade Striker (myField-1, via
// Ambush) -- choosing Shade Striker deals its printed 2 POWER to the attacker (instead of Giant
// Tortoise's 1 POWER) and rests Shade Striker as the cost, while the actually-attacked Giant
// Tortoise still takes the attacker's combat damage and is untouched by the retaliation cost.
// Giant Tortoise (vanilla ALLY, power 1 / life 6) is seeded onto player 1's field as the unit
// that will actually be attacked (myField-0). Shade Striker (power 2 / life 3, printed Ambush)
// is seeded right after it (myField-1) -- ready, not the attack's target -- to prove Ambush
// lets a different ready ally retaliate on the defender's behalf. Player 2 gets its own Giant
// Tortoise as the attacker.
// Verified live via a standalone replay harness (dumping decision-queue contents after each
// action): both players' pregame starting champion auto-occupies myField-0, so the seeded
// Giant Tortoise/Shade Striker land at myField-1/myField-2 (p1) and myField-1 (p2). After
// player 2's attack target is chosen, player 1's very next decision is the "Retaliate?"
// MZMAYCHOOSE offering BOTH myField-1 (the actual defender, Giant Tortoise) and myField-2
// (Shade Striker, via Ambush) -- there is no separate fast-opportunity PASS in front of it for
// either player in this deck/scenario (no fast cards in either starting hand). Answering it
// with Shade Striker's own mzID (myField-2) resolves the rest of combat in one action.
// Notes carried over from the original definition:
//   - Giant Tortoise, the actual defender/target
//   - Shade Striker, the Ambush retaliator
//   - Giant Tortoise, the attacker
//   - End player 1's turn so turn control passes to player 2, who can then declare an attack.
//   - Player 2 (now turn player) declares its Giant Tortoise's (myField-1, not the champion at
//   - myField-0) attack against player 1's Giant Tortoise.
//   - Defender (p1) chooses Shade Striker (myField-2), NOT the actual defender, as the retaliator.
gaScenario('shade-striker-ambush')
    ->standardDeck('Windslice')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myField', 'Shade Striker')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-1')->target('myField-2')
    ->tested('Shade Striker')
    ->register();

// --- Starlit Apothecary: [Arisanna Bonus] at recollection, summon a token copy of a Potion/Herb unless opponent pays (4) ---
// Starlit Apothecary is seeded directly onto the field. Its recollection trigger is a plain
// CardID switch-case inside ResolveBeforeRecollectionPhaseStart (GameLogic.php ~25278, the
// same dispatcher used for Foster/Waterveil Apostle/Domain upkeep) gated on
// IsArisannaBonusActive($player) -- the champion is CardID-patched to Arisanna, Astral Zenith
// (q3huqj5bba, in the Arisanna lineage whitelist, GameLogic.php ~24072). A single Herb token
// (Blightroot -- a plain token with no activatable ability of its own, unlike a real Potion,
// which would otherwise intercept the turn-end transition with its own Sacrifice
// fast-opportunity) is seeded onto the field as the only Potion/Herb target;
// StarlitApothecaryRecollection (PotionLogic.php ~544) sees exactly one target and skips
// straight to StarlitApothecaryQueueCopy, which offers the OPPONENT a YESNO to pay (4) and
// prevent the copy. Declining (NO) lets StarlitApothecaryResolveCopy summon a token copy of
// the Herb onto the field. Reaching player 1's own recollection phase uses the established
// 3-action shortcut (tonoris-genesis-aegis-recollection-obelisk / waterveil-apostle
// -recollection-gather).
// Notes carried over from the original definition:
//   - Arisanna, Astral Zenith - Arisanna Bonus unlock
//   - Starlit Apothecary - the recollection trigger source
//   - Blightroot (HERB token, no own ability) - the only copy target
//   - Recollection fires: Starlit Apothecary offers the opponent a YESNO to pay (4) and
//   - prevent the copy. Decline it so the copy actually resolves.
gaScenario('starlit-apothecary-recollection-copy-potion')
    ->deck(GA_ARISANNA_BASE_DECK)
    ->champion(cardName: 'Arisanna, Astral Zenith')
    ->seed('myField', 'Starlit Apothecary')
    ->seed('myField', 'Blightroot')
    ->endTurn()->answer(1, '-')->answer(1, '-')
    ->endTurn(2)->answer(1, '-')->answer(1, '-')->pass(1)->answer(1, '-')->answer(2, 'NO')
    ->tested('Starlit Apothecary')
    ->expectZoneCount('myField', 4, 1, 'A token copy is added to the field (champion, Starlit Apothecary, original Herb, and the new copy)')
    ->expectProperty('p1Field-3', 'CardID', 'i0a5uhjxhk', 'The new token is a copy of the targeted Herb')
    ->register();

// --- Sanctified Paladin: Foster keyword makes it eligible for the recollection-phase Foster
// check, firing its onFosterAbilities closure (draw two cards) ---
// Bug fix under test: HasFoster() (GrandArchiveSim/Custom/CardLogic.php) gates the Foster
// keyword behind a hardcoded per-card-ID whitelist that was missing Sanctified Paladin's ID
// (ioLmt0S7op), even though its printed text ("**Foster**<br>...<br>**On Foster:** Draw two
// cards.") and its onFosterAbilities closure are both correctly in place. Seed it onto the
// field freshly (no FOSTERED TurnEffect, no DAMAGED_SINCE_LAST_TURN) so the recollection-phase
// Foster check (GameLogic.php ~9852-9892) sees it transition to newly-fostered for the first
// time -- only a genuine wasFostered===false -> true transition fires OnFoster(), matching the
// same P1->P2->P1 recollection-phase cycle as weaponsmith-class-bonus-durability /
// berserker-plate-recollection-damage-draw (turn 1's recollection early-returns; turn 2 fires
// it).
// Notes carried over from the original definition:
//   - Sanctified Paladin
gaScenario('sanctified-paladin-foster-draw')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Sanctified Paladin')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->tested('Sanctified Paladin')
    ->expectProperty('p1Field-1', 'TurnEffects', '["FOSTERED"]', 'Sanctified Paladin is now recognized as fosterable and becomes fostered')
    ->expectZoneCount('myHand', 10, 1, 'On Foster: Draw two cards fires (7 starting + 1 normal draw + 2 from On Foster)')
    ->register();

// --- Shred to Ribbons: [Class Bonus] +3 POWER while attacking an ally with 5+ LIFE ---
// Shred to Ribbons's [Class Bonus] (GameLogic.php ~11658, inside the per-CardID POWER switch)
// adds +3 POWER to the ATTACK card's intent-loaded power when [Class Bonus] (GUARDIAN or
// WARRIOR) is active and the declared combat target is an ALLY with 5+ current LIFE
// (ObjectCurrentHP). P2 is patched to a WARRIOR champion (Lorraine, Wandering Warrior) for the
// class bonus; a Dungeon Guide ALLY is seeded onto P1's (the opponent's) field with its
// Counters overridden to potion_animate_life=5 (same "potion_animate_power" style override as
// blistering-insurgent-attack-buff, but for LIFE) since Dungeon Guide's printed LIFE is only 3.
// Same real attack-card-activation shape as heavy-swing-class-bonus-discount: P1 passes turn 1
// (Rule 1.h forbids the opening player from activating ATTACK cards turn 1), then P2 activates
// Shred to Ribbons on their own turn 1, paying its full 3 reserve (no [Class Bonus] cost
// discount on this card), then targets the seeded ally (theirField-1, not the champion at
// theirField-0) to satisfy the "attacking an ally with 5+ LIFE" condition.
// Notes carried over from the original definition:
//   - Lorraine, Wandering Warrior (WARRIOR) - Class Bonus source
//   - Dungeon Guide ALLY w/ LIFE overridden to 5 - attack target
//   - Shred to Ribbons, seeded directly into Intent (bypassing the hand-play reserve/rearrange flow); BridgeAddToZone only auto-sets Controller/Owner for */Field zones, so it's set explicitly here for the [Class Bonus] IsClassBonusActive($obj->Controller, ...) check to resolve the correct player
//   - ends turn 1
//   - champion declares the real attack
//   - target the seeded ally (LIFE 5), not P1's champion
//   - decline Retaliate?
gaScenario('shred-to-ribbons-class-bonus-attack-ally-5-life-power')
    ->standardDeck('Shred to Ribbons')
    ->champion(cardName: 'Lorraine, Wandering Warrior', player: 2)
    ->seed('myField', 'Dungeon Guide', properties: ['Counters' => ['potion_animate_life' => 5]])
    ->seed('myIntent', 'Shred to Ribbons', player: 2, properties: ['Controller' => 2, 'Owner' => 2])
    ->endTurn()
    ->action(2, 10002, 'myField-0!FSM!')->answer(2, 'theirField-1')->answer(1, '-')
    ->tested('Shred to Ribbons')
    ->expectDqEmpty()
    ->expectProperty('p1Graveyard-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'Status', '2')
    ->expectDamage('p1Graveyard-0', 5, 'Shred to Ribbons\'s [Class Bonus] (GameLogic.php ~11658, per-CardID POWER switch) adds +3 POWER to its printed 2 while attacking an ally with 5+ LIFE, landing 5 total damage -- exactly lethal to the LIFE-5 Dungeon Guide (which then dies and moves to the graveyard), vs. only 2 damage (and survival) if the Class Bonus never applied')
    ->expectProperty('p1Graveyard-0', 'Counters', '{"potion_animate_life":5}')
    ->expectProperty('p1Graveyard-0', 'TurnEffects', '["DAMAGED_SINCE_LAST_TURN"]')
    ->expectProperty('p2Field-0', 'Status', '1')
    ->expectProperty('p2Hand-7', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Graveyard-0', 'CardID', '5j36gn1b2s')
    ->expectProperty('p2Graveyard-0', 'TurnEffects', '[]')
    ->expectZoneCount('myField', 1, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->expectZoneCount('theirGraveyard', 1, 1)
    ->register();

// --- Strike of Singularity: [Class Bonus] attacking a unit whose controller has an empty hand ->
// this attack deals double damage ---
// Regression fixture for a genuine engine bug: onAttackAbilities["AMv1u54B2s:0"] (GeneratedCode/
// GeneratedMacroCode.php) tagged the intent card with "soO3hjaVfN_DOUBLE" -- Rending Flames'
// (soO3hjaVfN) own CardID -- instead of its own, apparently copy-pasted from Rending Flames'
// implementation. The consumer, AttackHasRendingFlamesDouble() (GrandArchiveSim/Custom/
// CombatLogic.php), hardcoded a check for CardID === "soO3hjaVfN" in the intent zone, so Strike
// of Singularity's doubling could never fire no matter the game state. Fixed by renaming the
// consumer to the generic AttackHasDamageDoubleEffect(), which checks each intent card for a
// TurnEffect of the form "{$intentObj->CardID}_DOUBLE" (self-referential, matching every other
// card's own closure), and by correcting this card's own AddTurnEffect call to tag itself
// ("AMv1u54B2s_DOUBLE"). Verified this doesn't change Rending Flames' own behavior (its tag
// already matches its own CardID) via its existing rending-flames-onattack-banish-double-damage
// fixture, which still passes.
//
// Strike of Singularity's element is LUXEM (verified via CardElement()), so the starting
// champion's CardID is patched directly to Zander, Blinding Steel (UAF6Nr7GUE) -- LUXEM AND
// ASSASSIN on the same printed card (verified via CardElement()/CardClasses()) -- rather than
// Zander, Deft Executor (NORM/ASSASSIN, used for the other ASSASSIN-gated Zander Pantheon
// fixtures above): patching to a NORM champion left CanPlayerUseCardElement() blocking the LUXEM
// card, and the FSM play click silently no-op'd (reported success, changed nothing, same
// element-gating trap documented on insignia-of-corhazi-rest-prepare above -- confirmed live by
// instrumenting the replay: hand/field/DQ were byte-identical before and after the FSM click
// until the champion's element was corrected). Rule 1.h blocks the game's first player from
// attacking on turn 1, so player 1 ends turn 1 and player 2 plays Strike of Singularity on their
// own turn 1 instead. Player 1's starting hand is fully emptied (via the 'emptyZone' setup
// helper -- see its own comment above for why this doesn't script a real hand-depletion
// sequence) so their champion satisfies "attacking a unit controlled by a player with no cards
// in their hand." Strike of Singularity's printed POWER is 4; doubled damage (8) on the champion
// is only possible if the ability's "deals double that damage instead" effect actually applied.
// Notes carried over from the original definition:
//   - Zander, Blinding Steel (LUXEM, ASSASSIN) - element + Class Bonus precondition
//   - empty defender's hand - "no cards in their hand" precondition
//   - ends turn 1 (first-player attack lock)
//   - reserve cost 1/4
//   - reserve cost 2/4
//   - reserve cost 3/4
//   - reserve cost 4/4
//   - target opponent's (empty-handed) champion
//   - decline Retaliate so CombatApplyAttackerDamage actually lands
gaScenario('strike-of-singularity-onattack-empty-hand-double-damage')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Zander, Blinding Steel', player: 2)
    ->emptyZone('myHand', destZone: 'myBanish')
    ->seed('myHand', 'Strike of Singularity', player: 2)
    ->endTurn()
    ->play('Strike of Singularity', 2)->pay(4, 2)->answer(2, 'theirField-0')->answer(1, '-')
    ->expectDqEmpty()
    ->expectDamage('p1Field-0', 8, 'Champion takes double damage (4 printed POWER x2 = 8) attacking a unit whose controller has an empty hand')
    ->expectProperty('p1Field-0', 'Counters', '{"_champDamageThisTurn":8}')
    ->expectProperty('p1Field-0', 'TurnEffects', '["WAS_ATTACKED","DAMAGED_SINCE_LAST_TURN","CHAMP_DMG_BY_P2"]')
    ->expectProperty('p2Field-0', 'Status', '1')
    ->expectProperty('p2Field-0', 'Counters', '{"_champCombatDamageDealtThisTurn":8}')
    ->expectProperty('p2Field-0', 'TurnEffects', '["AMv1u54B2s_DOUBLE"]', 'Self-referential DOUBLE tag lands on the attacking champion\'s own TurnEffects (the ambient onAttack mzID, not the intent card) -- the consumer must check both locations')
    ->expectProperty('p2Hand-0', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p2Hand-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Hand-3', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Graveyard-0', 'CardID', 'AMv1u54B2s')
    ->expectProperty('p2Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Memory-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Memory-2', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Memory-3', 'CardID', 'px60u5n1do')
    ->expectProperty('p2GlobalEffects-0', 'CardID', 'CHAMP_DEALT_COMBAT_DMG')
    ->expectZoneCount('theirHand', 4, 1)
    ->expectZoneCount('theirGraveyard', 1, 1)
    ->expectZoneCount('theirMemory', 4, 1)
    ->register();

// --- Slice and Dice: Prepare 3, On Hit may declare additional attack with +3 POWER copy if prepared ---
// Regression fixture for a genuine engine bug: the CardEditor ability database has no
// CardActivated row for Slice and Dice at all (unlike every other Prepare-cost ATTACK card,
// e.g. Thieving Cut/7t9m4muq2r just above), so nothing ever offered or paid its printed
// "Prepare 3" additional cost -- its onHitAbilities["3jg01o26b4:0"] "if prepared" branch
// (GeneratedCode/GeneratedMacroCode.php) was therefore permanently unreachable. No database
// access (local MySQL or a configured remote CardCodeService) was reachable in the environment
// this fix was developed in to author the missing row and regenerate, so the fix instead adds
// $cardActivatedAbilities["3jg01o26b4:0"] by hand in GrandArchiveSim/Custom/GameLogic.php,
// mirroring Thieving Cut's generated entry exactly (same DeclarePrepareCost/wasPrepared/
// PREPARED-TurnEffect mechanism, which is itself hand-written tracked code, not generated).
// This is additive and safe: GeneratedMacroCode.php has no competing entry for this key, so
// nothing is clobbered on regeneration; if the database row is ever authored, this hand-written
// entry becomes redundant and should be removed in favor of the generated one.
//
// Player 2's champion is pre-seeded with 3 preparation counters directly (same pattern as
// thieving-cut-prepare-onhit-draw, scaled to this card's Prepare 3 cost) so the cost can
// actually be paid. Answering YES to "Declare additional attack with copy?" is deliberately
// NOT exercised here (that path creates a whole second attack instance, out of scope for this
// fixture) -- the assertion is that the prompt appears at all, which only happens if the
// PREPARED TurnEffect was actually applied.
// Notes carried over from the original definition:
//   - Prepare-ability cost fuel
//   - ends turn 1 (first-player attack lock)
//   - reserve cost 1/2
//   - reserve cost 2/2
//   - pay Prepare 3
//   - target opponent's champion
//   - decline Retaliate so the hit actually lands
//   - decline the "declare additional attack" offer -- its appearance alone proves PREPARED applied
gaScenario('slice-and-dice-prepare-onhit-additional-attack')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->patch('myField-0', ['Counters' => ['preparation' => 3]], player: 2)
    ->seed('myHand', 'Slice and Dice', player: 2)
    ->endTurn()
    ->play('Slice and Dice', 2)->pay(2, 2)->answer(2, 'YES')->answer(2, 'theirField-0')->answer(1, '-')
    ->answer(2, 'NO')
    ->expectDqEmpty()
    ->expectDamage('p1Field-0', 3)
    ->expectProperty('p1Field-0', 'Counters', '{"_champDamageThisTurn":3}')
    ->expectProperty('p1Field-0', 'TurnEffects', '["WAS_ATTACKED","DAMAGED_SINCE_LAST_TURN","CHAMP_DMG_BY_P2"]')
    ->expectProperty('p2Field-0', 'Status', '1')
    ->expectProperty('p2Field-0', 'Counters', '{"_champCombatDamageDealtThisTurn":3}', 'Prepare 3\'s cost consumed all 3 of the champion\'s preparation counters (no longer present); this and the \'Declare additional attack with copy?\' offer at step 6 (answered NO) only appear at all because cardActivatedAbilities["3jg01o26b4:0"] now exists to pay the cost and set PREPARED')
    ->expectProperty('p2Hand-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-2', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p2Hand-3', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-4', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Graveyard-0', 'CardID', '3jg01o26b4')
    ->expectProperty('p2Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Memory-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p2GlobalEffects-0', 'CardID', 'CHAMP_DEALT_COMBAT_DMG')
    ->expectZoneCount('theirHand', 6, 1)
    ->expectZoneCount('theirGraveyard', 1, 1)
    ->expectZoneCount('theirMemory', 2, 1)
    ->register();

// --- Shimmering Refraction: Deal X damage to target unit, X = phantasias you control ---
// Shimmering Refraction (1k2jb8mau1, ACTION/CLERIC/SPELL/REACTION, element NORM, FAST speed) is
// activated from hand via ActivateCard, going onto the EffectStack like Escharotomy/Water
// Barrier -- both players must pass the resulting fast-action opportunity window before it
// resolves. Two Protective Fractal cards (1lw9n0wpbh, PHANTASIA) are seeded directly onto my
// field (bypassing their own On Enter/activation, irrelevant here) purely to make
// ZoneSearch("myField", ["PHANTASIA"]) return a real count of 2. cardActivatedAbilities
// ["1k2jb8mau1:0"] (GeneratedMacroCode.php) records activationSourceZone (here "myHand", so
// ShimmeringRefractionFromMemory is stored "NO") and queues an MZCHOOSE over ALLY/CHAMPION
// units on both fields. customDQHandlers["1k2jb8mau1:0:CardActivated-1"] then computes
// $phantasiaCount = count(ZoneSearch("myField", ["PHANTASIA"])) = 2 and calls
// DealDamage($player, $mzID, $target, $phantasiaCount) -- dealing exactly 2 damage to the
// targeted opponent's champion, a real non-trivial number (not a trivial 0/1) that only makes
// sense if the phantasia count was actually read and threaded through. The champion is patched
// to Diao Chan, Enchantress (00xbh8oc00) so IsDiaoChanBonus($player) is true -- proving the
// [Diao Chan Bonus] draw specifically requires activation FROM MEMORY, not just having the
// Diao Chan Bonus active: since this copy is activated from hand (not memory), no memory draw
// happens despite the Bonus condition being met.
// Notes carried over from the original definition:
//   - Diao Chan, Enchantress -- Diao Chan Bonus source
//   - Protective Fractal (PHANTASIA) #1
//   - Protective Fractal (PHANTASIA) #2
//   - activate Shimmering Refraction from hand
//   - pay reserve 1/2
//   - pay reserve 2/2
//   - pass fast action opportunities
//   - target the opponent's champion
gaScenario('shimmering-refraction-phantasia-count-damage')
    ->deckTemplate('diao-awakened-deacon', 'Shimmering Refraction')
    ->champion(cardName: 'Diao Chan, Enchantress')
    ->seed('myField', 'Protective Fractal')
    ->seed('myField', 'Protective Fractal')
    ->seed('myHand', 'Shimmering Refraction')
    ->castSpell('Shimmering Refraction', pay: 2, window: 'both', target: 'theirField-0')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Hand-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Hand-2', 'CardID', 'x7u6wzh973')
    ->expectProperty('p1Hand-4', 'CardID', 'c9p4lpnvx7')
    ->expectProperty('p1Graveyard-0', 'CardID', '1k2jb8mau1')
    ->expectProperty('p1Memory-0', 'CardID', 'x7u6wzh973')
    ->expectProperty('p1Memory-1', 'CardID', '1k2jb8mau1')
    ->expectDamage('p2Field-0', 2, 'Damage dealt equals exactly 2, matching the 2 Protective Fractal (PHANTASIA) objects seeded onto my field -- proving X is read from the real phantasia count, not a fixed or trivial 0/1 value')
    ->expectProperty('p2Field-0', 'Counters', '{"_champDamageThisTurn":2}')
    ->expectProperty('p2Field-0', 'TurnEffects', '["DAMAGED_SINCE_LAST_TURN"]')
    ->expectZoneCount('myHand', 5, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 2, 1, 'Memory holds exactly the 2 cards paid as this activation\'s reserve cost -- no extra card was drawn into memory, proving the [Diao Chan Bonus] draw did NOT fire even though IsDiaoChanBonus is true, because activationSourceZone was myHand, not myMemory (would be 3 if the bonus incorrectly fired on a hand activation)')
    ->register();

// --- Season's End: [Diao Chan Bonus] costs 1 less per wither counter on objects on the field.
// Destroy each object with a wither counter on it -- negative-path proof (clean object survives) ---
// Champion patched to Diao Chan, Idyll Corsage (d7l6i5thdy) for IsDiaoChanBonus and TERA
// unlock, same as ripples-of-atrophy-two-wither-counters. Two objects are pre-seeded with
// wither counters directly via setProperties (Counters: {wither:N}) -- one on each side of the
// field, so the destroy isn't scoped to only the caster's own side -- and a third, clean
// Dungeon Guide with NO wither counters is seeded onto the caster's own field as the negative-
// path control. activationCostModifierAbilities["ddggqvxw8f:0"] (GeneratedMacroCode.php ~8429)
// reduces the printed 10-reserve cost by 1 per withered object found via
// array_merge(GetField(1), GetField(2)) -- with exactly 2 withered objects seeded, net cost is
// 10-2=8. Four extra filler cards are seeded into hand (on top of the natural starting hand)
// so the 8-reserve cost can actually be paid in full. cardActivatedAbilities["ddggqvxw8f:0"]
// (~17528, "Wither Purge") then iterates both fields and calls DoSacrificeFighter on every
// non-champion object with a wither counter -- no player-chosen targeting exists for this
// card, so the "only withered objects die" outcome IS the semantic proof (GrandArchiveSimSemanticFixtureGuide.md's
// guidance for implicit-targeting negative paths). Both withered objects move to their
// owners' graveyards (a real destroy, not just a silent counter/removal), while the clean
// Dungeon Guide remains alive on the field untouched. Destroying the opponent's non-token
// object also triggers Diao Chan, Idyll Corsage's own passive ("Whenever a non-token object an
// opponent controls is destroyed, you may banish it...", DiaoChanIdyllBanish decision) as a
// side effect confirming a real destroy event fired; that optional banish offer is declined
// (NO) to keep this fixture scoped to Season's End itself.
// Notes carried over from the original definition:
//   - Diao Chan, Idyll Corsage (Diao Chan Bonus + TERA unlock)
//   - withered Dungeon Guide (opponent's side) -- to be destroyed
//   - withered Fluffy Shopkeep (caster's own side) -- to be destroyed
//   - CLEAN Dungeon Guide (caster's own side), no wither counter -- negative-path control, must survive
//   - extra filler for the 8-reserve cost
//   - play Season's End
//   - pay reserve 1/8
//   - pay reserve 2/8
//   - pay reserve 3/8
//   - pay reserve 4/8
//   - pay reserve 5/8
//   - pay reserve 6/8
//   - pay reserve 7/8
//   - pay reserve 8/8 -- stack auto-resolves (no fast cards), ability fires immediately
//   - decline Diao Chan, Idyll Corsage's optional banish-on-destroy offer
gaScenario('seasons-end-destroy-withered-only')
    ->deck(GA_DIAOCHAN_WITHER_DECK)
    ->champion(cardName: 'Diao Chan, Idyll Corsage')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Counters' => ['wither' => 3]])
    ->seed('myField', 'Fluffy Shopkeep', properties: ['Counters' => ['wither' => 1]])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Season\'s End')
    ->play('Season\'s End')->pay(8)->answer(1, 'NO')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Field-1', 'CardID', 'em6eEh9q8y', 'The clean Dungeon Guide (no wither counter) survives on the caster\'s own field -- it is NOT destroyed even though Season\'s End\'s own controller cast it')
    ->expectProperty('p1Field-1', 'Counters', '[]')
    ->expectProperty('p1Hand-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Graveyard-0', 'CardID', 'ddggqvxw8f')
    ->expectProperty('p1Graveyard-1', 'CardID', 'px60u5n1do', 'The withered Fluffy Shopkeep on the caster\'s OWN field was destroyed (moved to graveyard) -- proving Season\'s End destroys withered objects regardless of controller, not just the opponent\'s')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-2', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-3', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-4', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-5', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-6', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-7', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Graveyard-0', 'CardID', 'em6eEh9q8y', 'The withered Dungeon Guide on the OPPONENT\'s field was destroyed (moved to graveyard)')
    ->expectZoneCount('myHand', 3, 1)
    ->expectZoneCount('myField', 2, 1, 'Caster\'s field: champion + surviving clean Dungeon Guide only -- the withered Fluffy Shopkeep is gone')
    ->expectZoneCount('myGraveyard', 2, 1)
    ->expectZoneCount('myMemory', 8, 1)
    ->expectZoneCount('theirField', 1, 1, 'Opponent\'s field: champion only -- their withered Dungeon Guide is gone')
    ->expectZoneCount('theirGraveyard', 1, 1)
    ->register();

// --- Shriveling Vines: [Class Bonus] Costs 2 less. At the beginning of your end phase, put two
// wither counters on target non-champion non-token object you don't control ---
// Champion patched to Diao Chan, Idyll Corsage (d7l6i5thdy) for IsClassBonusActive(["CLERIC"])
// (the printed "[Class Bonus] Costs 2 less" clause) and TERA unlock (Shriveling Vines's own
// element is TERA). Shriveling Vines's own trigger logic lives in
// GrandArchiveSim/Custom/GameLogic.php EndPhase() (~10860, NOT in GeneratedMacroCode.php --
// confirmed 0 hits there), so the card is seeded directly onto the field via setup rather than
// scripting its own materialize/cast sequence (which is out of scope for this fixture; only
// the field-resident end-phase trigger itself is under test). Two valid non-champion non-token
// opponent objects are seeded on the opponent's field; a single ending of turn 1
// ('myHealth-0!CustomInput!Pass', the standard "reach end phase" idiom used elsewhere in this
// file) is enough to trigger EndPhase() for the turn player (player 1) directly -- turn 1 needs
// no opponent PASS or materialize-offer decline first, unlike some later-turn end-phase
// fixtures. Because more than 1 valid target exists, the engine queues an MZCHOOSE
// ("Put_2_wither_counters_on_opponent_object_(Shriveling_Vines)"); choosing one applies
// AddCounters($turnPlayer, $validTargets[0], "wither", 2) to it, while the other seeded
// opponent object is left untouched -- proving the effect targets exactly the object chosen.
// Notes carried over from the original definition:
//   - Diao Chan, Idyll Corsage (Class Bonus CLERIC + TERA unlock)
//   - Shriveling Vines, seeded directly onto the field
//   - opponent Dungeon Guide, chosen target
//   - opponent Fluffy Shopkeep, eligible but deliberately left UNCHOSEN
//   - end turn 1 -> reaches player 1's own end phase, firing Shriveling Vines's trigger
//   - choose the opponent's Dungeon Guide as the wither target
gaScenario('shriveling-vines-end-phase-two-wither-counters')
    ->deck(GA_DIAOCHAN_WITHER_DECK)
    ->champion(cardName: 'Diao Chan, Idyll Corsage')
    ->seed('myField', 'Shriveling Vines')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myField', 'Fluffy Shopkeep', player: 2)
    ->endTurn()->target('theirField-1')
    ->tested('Shriveling Vines')
    ->register();

// --- Scepter of Fascination: On Enter draw + [Diao Chan Bonus] banish wakes champion, +2 glimmer ---
// NOTE: an earlier version of this fixture seeded the Scepter directly onto the field via the
// 'zone' setup primitive. BridgeAddToZone/MZAddZone/AddField/FieldAfterAdd never call Enter() for a
// raw zone-seed (confirmed by reading ZoneAccessors.php/GameLogic.php -- FieldAfterAdd only has
// hand-written hooks for a handful of unrelated specific cards). The initial_gamestate.txt this
// produced already showed the post-draw deck count, meaning the "draw" assertion was checking a
// pre-baked snapshot, not a replayed effect -- confirmed via break/restore: disabling
// enterAbilities["4864k12no2:0"]'s Draw() call and re-running the OLD fixture still passed. Fixed
// using the technique verified working for staff-of-blossoming-will-enter-draw-and-flowerbud:
// REGALIA cards seeded via 'zone'=>'myHand' get silently redirected to myMaterial by
// HandAddReplacement (GameLogic.php ~17777) -- confirmed empirically -- so 'patchMzId'=>'myHand-6'
// overwrites an already-hand-resident filler card's CardID instead (patchMzId never calls
// AddHand(), bypassing the redirect), then it's played for real via mode 10002 FSM, firing the
// genuine Enter(). Re-verified break/restore on THIS version for BOTH clauses (On Enter draw and
// the [Diao Chan Bonus] banish ability): each disabled independently now correctly fails the
// fixture, and restoring either passes again.
// Notes carried over from the original definition:
//   - patch the pregame-selected Spirit of Water into Diao Chan, Enchantress directly (not testing HER On Enter here), rested, no glimmer counters
//   - Scepter of Fascination, overwriting the last opening-hand slot -- patchMzId bypasses HandAddReplacement's REGALIA->myMaterial redirect (same technique as staff-of-blossoming-will-enter-draw-and-flowerbud)
//   - play Scepter of Fascination from hand -- fires its real On Enter draw via DoMaterialize()
//   - activate the [Diao Chan Bonus] banish ability
gaScenario('scepter-of-fascination-enter-draw-and-diao-chan-wake')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Diao Chan, Enchantress
# Main
10 Dungeon Guide
10 Fluffy Shopkeep
DECK
    )
    ->patch('myField-0', ['CardID' => '00xbh8oc00', 'Status' => 1, 'Counters' => []])
    ->patch('myHand-6', ['CardID' => '4864k12no2'])
    ->playSlot(6)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Scepter of Fascination')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'Status', '2', 'The champion (seeded rested, Status 1) is now awake (Status 2) -- proving the [Diao Chan Bonus] banish ability\'s \'Wake up your champion\' clause (WakeupCard call, activateAbilityAbilities[\'4864k12no2:0\']) ran; independently confirmed by disabling that closure and observing this fixture then fails with Status staying 1')
    ->expectProperty('p1Field-0', 'Counters', '{"glimmer":2}', 'The champion (seeded with Counters drained to []) now has 2 glimmer counters -- proving the same ability\'s \'Put two glimmer counters on your champion\' clause ran')
    ->expectProperty('p1Hand-6', 'CardID', 'px60u5n1do', 'A genuinely NEW card now occupies the hand slot the Scepter itself was overwritten into and then left (by being played) -- proving Scepter of Fascination\'s own \'On Enter: Draw a card\' fired as a real replayed action (enterAbilities[\'4864k12no2:0\'], Draw($player,1)), not merely baked into a pre-captured snapshot. Independently confirmed by disabling that closure and observing this fixture then fails with the slot staying empty.')
    ->expectProperty('p1Banish-0', 'CardID', '4864k12no2')
    ->expectProperty('p1Banish-0', 'Status', '2')
    ->expectDamage('p1Banish-0', 0)
    ->expectProperty('p1Banish-0', 'Counters', '[]')
    ->expectProperty('p1Banish-0', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->register();

// --- Staff of Blossoming Will: On Enter -- draw a card; [Diao Chan Bonus] (1), [REST]:
// target player summons a Flowerbud token. ---
// Staff of Blossoming Will is a TERA (advanced element) REGALIA,ITEM card, so myField-0's
// Subcards are patched to include Diao Chan, Idyll Corsage (d7l6i5thdy, TERA) -- this grants
// TERA element access via lineage AND satisfies IsDiaoChanBonus (IsDiaoChanBonus checks
// ChampionHasInLineage for any of the 3 Diao Chan card IDs) in one patch, since patchMzId
// never triggers Enter() (so nothing about Idyll Corsage's own On Enter/passive fires).
// REGALIA cards can't be seeded directly into myHand via the normal setup zone-add primitive
// -- HandAddReplacement (GameLogic.php:17777) unconditionally redirects any card whose
// CardType contains REGALIA into myMaterial instead (confirmed empirically), which is where
// REGALIA cards are actually meant to live and would only leave via the (turn>=2-only)
// Materialize Phase, not a hand FSM click. To exercise the REAL On-Enter-draw as an observable
// ACTION (not baked silently into setup -- seeding a card with an $enterAbilities entry
// directly onto myField via setup DOES synchronously fire Enter(), confirmed empirically,
// which would make the draw invisible as a setup-to-actions delta), myHand-6 (the last opening
// hand slot) is patched directly to Staff of Blossoming Will's own CardID via patchMzId --
// patchMzId mutates an object already in the hand array, bypassing HandAddReplacement
// entirely since it never calls AddHand(). It can then be played for real via a normal mode
// 10002 FSM click. enterAbilities["4moumzcx9z:0"] (GeneratedMacroCode.php:9648) is a plain
// Draw(player, 1) -- proven by the deck shrinking by 1 and a genuinely new card landing in the
// vacated hand slot. activateAbilityPrereqs["4moumzcx9z:0"] (:2047) requires
// IsDiaoChanBonus($player), the source object awake (Status==2), and at least 1 CHAMPION on
// either field; its ability body (:2035) queues an MZCHOOSE over all CHAMPIONs (both mine and
// theirs -- "target player" is represented by picking that player's champion), then
// customDQHandlers["4moumzcx9z:0:ActivateAbility-1"] (:27077) resolves it and adds a Flowerbud
// token (yn78t73w1p) to the chosen champion's controller's own field. Targeting theirField-0
// (the opponent's champion) proves the token lands on the CORRECT (targeted) player's field,
// not always the activator's.
// Notes carried over from the original definition:
//   - Diao Chan, Idyll Corsage lineage -- TERA access + Diao Chan Bonus
//   - Staff of Blossoming Will, overwriting the last opening-hand slot (bypasses the REGALIA->myMaterial redirect)
//   - materialize Staff of Blossoming Will for real -- fires its On Enter draw
//   - (1), REST: activate the Diao Chan Bonus ability
//   - pay its (1) reserve cost
//   - target the opponent's champion -- their player summons the Flowerbud
gaScenario('staff-of-blossoming-will-enter-draw-and-flowerbud')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep
DECK
    )
    ->champion(lineage: ['Diao Chan, Idyll Corsage'])
    ->patch('myHand-6', ['CardID' => '4moumzcx9z'])
    ->playSlot(6)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(1)->target('theirField-0')
    ->tested('Staff of Blossoming Will')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'CardID', '4moumzcx9z')
    ->expectProperty('p1Field-1', 'Status', '1')
    ->expectDamage('p1Field-1', 0)
    ->expectProperty('p1Field-1', 'Counters', '[]')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1Hand-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-2', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Hand-3', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-5', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Memory-0', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Field-1', 'CardID', 'yn78t73w1p', 'The Flowerbud token landed on player 2\'s field (the targeted player), not player 1\'s -- customDQHandlers["4moumzcx9z:0:ActivateAbility-1"] resolved the target champion pick (theirField-0) to its CONTROLLER and added the token to that player\'s own field')
    ->expectProperty('p2Field-1', 'Status', '2')
    ->expectDamage('p2Field-1', 0)
    ->expectProperty('p2Field-1', 'Counters', '[]')
    ->expectProperty('p2Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectZoneCount('myHand', 6, 1)
    ->expectZoneCount('myField', 2, 1)
    ->expectZoneCount('myMemory', 1, 1)
    ->expectZoneCount('theirField', 2, 1)
    ->expectZoneCount('myDeck', 12, 1, 'Deck shrank from 13 to 12: Staff of Blossoming Will\'s On Enter (enterAbilities["4moumzcx9z:0"]) drew a real card off the top of the deck when it materialized')
    ->register();

// Strengthen the Bonds: put a buff counter on up to two target Fatestone or Fatebound objects.
// Notes carried over from the original definition:
//   - Two Fatestone items on the field as the up-to-two targets.
//   - Pay the 2-reserve cost with Dungeon Guide from hand.
//   - MZMULTICHOOSE: select both Craggy Fatestones (myField-1 and myField-2) as the up-to-two targets.
gaScenario('strengthen-the-bonds-buff-two-targets')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
4 Strengthen the Bonds
6 Dungeon Guide
DECK
    )
    ->seed('myField', 'Craggy Fatestone')
    ->seed('myField', 'Craggy Fatestone')
    ->playSlot(1)->pay(2)->passBoth()->answer(1, 'myField-1&myField-2')
    ->tested('Strengthen the Bonds')
    ->expectProperty('myField-1', 'Counters', '{"buff":1}', 'First selected Fatestone item gets a buff counter')
    ->expectProperty('myField-2', 'Counters', '{"buff":1}', 'Second selected Fatestone item gets a buff counter (up to two targets)')
    ->register();

// Shatter the Brittle: activate only if you control a Fatestone/Fatebound object; destroy target
// cheap item/weapon; its controller draws a card into their memory.
// Notes carried over from the original definition:
//   - Craggy Fatestone: satisfies both the activation prereq (a Fatestone object) and doubles
//   - as the destroy target (reserve cost 4 <= 5).
//   - MZCHOOSE: destroy the Craggy Fatestone.
gaScenario('shatter-the-brittle-destroy-draw-memory')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
4 Shatter the Brittle
6 Dungeon Guide
DECK
    )
    ->seed('myField', 'Craggy Fatestone')
    ->playSlot(0)->pay(2)->passBoth()->target('myField-1')
    ->tested('Shatter the Brittle')
    ->register();

// --- Shardforged Blade (Y34Imzlr0n): You may sacrifice a Memorite object while paying for this
// card's memory cost to pay for 1 of that cost. ---
// NOTE: the FSM play resolved the materialize in a single action with no MZMAYCHOOSE decision
// point ever reaching the player, defaulting to NOT sacrificing the seeded Memorite Blade even
// though one was available -- this fixture documents that observed (default/no-sacrifice) path;
// the alternative-cost branch itself was not independently exercised.
// Notes carried over from the original definition:
//   - A Memorite object on the field -- the potential (but, per the note above, unused)
//   - alternative-cost sacrifice target.
gaScenario('shardforged-blade-materialize-with-memorite-present')
    ->deckTemplate('spirit-dungeon-guide', 'Shardforged Blade')
    ->seed('myField', 'Memorite Blade')
    ->playSlot(4)
    ->tested('Shardforged Blade')
    ->register();

// --- Shattered Hope (XOevViFTB3): Glimpse 1, then draw. Until end of turn, allies enter with
// an additional sheen counter. Reserve cost 3 (three hand-card payments) -- cast for real via
// the standard hand-play path (FSM) to prove the 3-hand-payment reserve sequencing resolves the
// card's own cardActivatedAbilities closure (not some unrelated pending trigger). ---
// Notes carried over from the original definition:
//   - Free play: play Shattered Hope (mode 10002 FSM) -- myHand-5 with this deck/seed.
//   - Pay reserve cost 3 from hand (indices shift down after each removal, so myHand-0 three times).
//   - Both players decline the EffectStack Opportunity window so the card resolves.
//   - Glimpse 1: put the single glimpsed card back on top (MZREARRANGE echoes it back).
gaScenario('shattered-hope-glimpse-draw-sheen')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Shattered Hope
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
4 Windslice
4 Windslice
4 Windslice
4 Windslice
DECK
    )
    ->playSlot(5)->pay(3)->passBoth()->answer(1, 'Top=XOevViFTB3;Bottom=')
    ->tested('Shattered Hope')
    ->expectZoneCount('myGraveyard', 1, 1, 'Shattered Hope resolved off the EffectStack into the graveyard')
    ->expectProperty('myGraveyard-0', 'CardID', 'XOevViFTB3', 'The graveyard card is Shattered Hope itself, not a stranded/unrelated entry')
    ->expectZoneCount('myMemory', 3, 1, 'Glimpse 1 then draw a card added a card to memory')
    ->expectZoneCount('myGlobalEffects', 1, 1, 'The until-end-of-turn sheen-on-enter global effect was applied')
    ->expectProperty('myGlobalEffects-0', 'CardID', 'SHATTERED_HOPE_SHEEN', 'The applied global effect is Shattered Hope\'s own sheen-on-enter effect')
    ->expectZoneCount('myDeck', 8, 1, 'Glimpse 1 only looks and reorders (answered with the offered card), then one card is drawn: 9 - 1 = 8 cards left in the deck')
    ->register();

// --- Seep Into the Mind (7mHiO4YySz): Target opponent puts three sheen counters on a unit they
// control. Reserve cost 3 -- cast for real and target an opponent ally. ---
// Notes carried over from the original definition:
//   - Target ally, seeded onto the opponent's field (a supporting/target card, not the
//   - tested card's own Enter -- raw zone seeding is fine here).
//   - Guarantee Seep Into the Mind is in hand to play for real (the opening hand draw is
//   - not guaranteed to include it with only 4 copies in the deck).
//   - The "put 3 sheen counters" choice belongs to the TARGETED opponent (player 2), who
//   - must choose among their own units (their myField-1 == our theirField-1).
gaScenario('seep-into-the-mind-target-sheen')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Seep Into the Mind
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
4 Windslice
4 Windslice
4 Windslice
4 Windslice
DECK
    )
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Seep Into the Mind')
    ->play('Seep Into the Mind')->pay(3)->answer(2, 'myField-1')
    ->expectZoneCount('myGraveyard', 1, 1, 'Seep Into the Mind resolved off the EffectStack into the graveyard')
    ->expectProperty('myGraveyard-0', 'CardID', '7mHiO4YySz', 'The graveyard card is Seep Into the Mind itself, not a stranded/unrelated entry')
    ->expectProperty('theirField-1', 'Counters', '{"sheen":3}', 'The targeted opponent unit received three sheen counters')
    ->register();

// --- Stand Before the Queen (v9SJgS6z40): Prevent the next 2 damage that would be dealt to
// target unit this turn. Reserve cost 2 -- cast for real, targeting our own champion. ---
// Notes carried over from the original definition:
//   - Decline the EffectStack Opportunity window so the card resolves to its targeting step.
//   - Target our own champion (myField-0) to receive the damage-prevention effect.
gaScenario('stand-before-the-queen-prevent-damage')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Stand Before the Queen
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
4 Windslice
4 Windslice
4 Windslice
4 Windslice
DECK
    )
    ->playSlot(6)->pay(2)->passBoth()->target('myField-0')
    ->tested('Stand Before the Queen')
    ->expectZoneCount('myGraveyard', 1, 1, 'Stand Before the Queen resolved off the EffectStack into the graveyard')
    ->expectProperty('myGraveyard-0', 'CardID', 'v9SJgS6z40', 'The graveyard card is Stand Before the Queen itself, not a stranded/unrelated entry')
    ->expectProperty('myField-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","STAND_BEFORE_THE_QUEEN_2"]', 'The targeted champion carries the prevent-next-2-damage turn effect')
    ->register();

// --- Spirit Blade: Terminus (XsxmnGZxKz): Prepare 1. On Hit: If Terminus was prepared, double the
// sheen counters on Fractured Memories and put Terminus into its owner's memory. ---
// Spirit Blade: Terminus's element is CRUX (an advanced element), so the champion is patched
// directly to Merlin, Brilliant Vestige (2TCyILvBYa, itself CRUX) -- an already-leveled
// precondition, not retesting that card's own Enter here -- to satisfy the element requirement.
// A preparation counter is seeded directly onto the champion so the printed "Prepare 1"
// additional cost can actually be paid, and Fractured Memories (UAJGQFbXjs) is seeded into
// myMastery with 3 sheen counters -- a nonzero amount below the 4-sheen threshold for
// Terminus's own +1 POWER/4-sheen static bonus, so the POWER math stays simple -- for the On
// Hit "double the sheen counters" branch to actually double. ATTACK cards can't be activated by
// the game's first player on turn 1 (CanActivateAttackCardNow/IsFirstTurnAttackLocked in
// GameLogic.php), so player 1 ends turn 1 first and player 2 plays Terminus on their own turn 1
// instead (same shape as thieving-cut-prepare-onhit-draw). Answering YES to "Pay Prepare 1?"
// removes the preparation counter and stores wasPrepared=YES; the On Hit macro
// (onHitAbilities["XsxmnGZxKz:0"] in GeneratedMacroCode.php) reads that variable back and only
// then calls AddSheenToMastery()/MZMove()-to-memory -- an attack that was never marked prepared
// would do neither (this is the same wasPrepared/PREPARED-tag machinery hardened by the
// GA_TagPreparedAttack fix, exercised directly here rather than via the generic PREPARED
// TurnEffect since XsxmnGZxKz:0's own On Hit macro reads wasPrepared itself).
gaScenario('spirit-blade-terminus-onhit-double-sheen-memory')
    ->deckTemplate('spirit-dungeon-guide', 'Spirit Blade: Terminus')
    ->patch('myField-0', ['CardID' => '2TCyILvBYa', 'Counters' => ['preparation' => 1]], player: 2)
    ->seed('myMastery', 'Fractured Memories', player: 2, properties: ['Counters' => ['sheen' => 3]])
    ->seed('myHand', 'Spirit Blade: Terminus', player: 2)
    ->endTurn()
    ->play('Spirit Blade: Terminus', 2)->pay(2, 2)->answer(2, 'YES')->answer(2, 'theirField-0')
    ->register();

// --- Slime Nexus: Banish: The next Slime ally card you activate this turn costs 3 less ---
// Slime Nexus' ability is a correctly-categorized $activateAbilityAbilities['emoydelro8:0']
// entry (GeneratedCode/GeneratedMacroCode.php), offered through the fast-opportunity
// MZMAYCHOOSE flow. Activating it banishes Slime Nexus (MZMove to myBanish) and sets a
// per-player global effect ('emoydelro8'); $activationCostModifierAbilities['emoydelro8:0']
// then reads that global effect and applies a one-time, self-consuming -3 reserve-cost delta
// (delta/consume/applied shape) to the next Slime ally card activated. Red Slime
// (mttsvbgl6f, reserve cost 3) is seeded to a known hand slot and played for real via FSM
// immediately afterward in the same turn -- no myHand-N reserve-payment clicks follow the
// FSM play, proving the cost actually dropped to 0 rather than merely leaving a flag unread.
// Notes carried over from the original definition:
//   - Slime Nexus -> myField-1
gaScenario('slime-nexus-banish-next-slime-ally-discount')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->seed('myField', 'Slime Nexus')
    ->seed('myHand', 'Red Slime')
    ->endTurn()->answer(1, 'myField-1@Activate-0@Banish')
    ->play('Red Slime')
    ->tested('Slime Nexus')
    ->register();

// --- Slimeshield: Prevent the next 3 damage to target unit; buff it if a Slime ally ---
// Slimeshield's ability (cardActivatedAbilities['hcpetipurz:0'] ->
// customDQHandlers['hcpetipurz:0:CardActivated-1'], GeneratedMacroCode.php) is a plain
// reserve-cost ACTION card, correctly played through the normal hand-activation pipeline
// (unlike the since-fixed field-resident-ally-ability categorization bug of Baby Blue Slime, see the baby-blue-slime-* fixtures):
// AddTurnEffect($target, 'PREVENT_ALL_3'), then an extra AddCounters(..., 'buff', 1) only if
// the target is a Slime ally. Its element is WIND, not native to the 'Spirit of Fire'
// starting champion, so the champion's Subcards are patched with a real WIND champion
// (Spirit of Wind) purely to unlock element access. Red Slime (mttsvbgl6f, ALLY/SLIME) is
// seeded onto the field as the target, satisfying the "also a Slime ally" buff-counter
// clause. Slimeshield is played for real from hand via FSM so its effect genuinely fires.
// Only the base effect is covered; the separate [Class Bonus][Level 2+] cost discount is not
// reached (default champion is level 0, no class bonus).
// Notes carried over from the original definition:
//   - WIND lineage/element unlock
//   - Red Slime (Slime ally target) -> myField-1
//   - Decline the ambient mid-resolution opportunity (a hand-card response) before the
//   - spell's own MZCHOOSE target prompt appears (same technique as barter-herbs-sacrifice-summon).
//   - target Red Slime
gaScenario('slimeshield-prevent-3-buff-slime')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Spirit of Wind'])
    ->seed('myField', 'Red Slime')
    ->seed('myHand', 'Slimeshield')
    ->castSpell('Slimeshield', pay: 2, window: 'me', target: 'myField-1')
    ->expectProperty('myField-1', 'TurnEffects', '["ENTERED_THIS_TURN","PREVENT_ALL_3"]', 'Red Slime is protected by a PREVENT_ALL_3 shield')
    ->expectProperty('myField-1', 'Counters', '{"buff":1}', 'Red Slime, being a Slime ally, also gets a buff counter')
    ->expectDqEmpty()
    ->register();

// --- Spirit of Slime: On Enter: Draw seven cards ---
// Spirit of Slime's On Enter ability (enterAbilities['0xp4xq07vv:0'], GeneratedCode/
// GeneratedMacroCode.php) is a plain Draw($player, 7) -- identical text to Spirit of Fire
// (used as the Material-section starting champion in every other fixture in this suite) and
// to Spirit of Wind (dedicated coverage: spirit-of-wind-enter-draw-seven). It fires
// synchronously inside pregame resolution (PREGAME_CHOOSE_STARTING_CHAMPION's unconditional
// Enter() call, Custom/GameLogic.php), so simply swapping the deck's sole Material-section
// level-0 champion to Spirit of Slime (instead of Spirit of Fire) and letting the harness's
// standard pregame-choice step pick the only legal myMaterial-0 option is enough to fire it
// for real. A single harmless click on the resulting champion (myField-0) is kept as the
// fixture's one action, mirroring spirit-of-wind-enter-draw-seven's shape so this fixture has
// its own dedicated post-action gamestate snapshot.
gaScenario('spirit-of-slime-enter-draw-seven')
    ->deck(<<<'DECK'
# Material
1 Spirit of Slime
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK
    )
    ->target('myField-0')
    ->tested('Spirit of Slime')
    ->expectZoneCount('myHand', 7, 1, 'Spirit of Slime\'s On Enter draws seven cards, giving the opening hand')
    ->register();

// --- Storm Slime: On Enter, as a Spell, deal damage to target unit equal to Slime cards banished ---
// Storm Slime's On Enter ability (enterAbilities['blqryebvwj:0'] ->
// customDQHandlers['blqryebvwj:0:Enter-1'], GeneratedCode/GeneratedMacroCode.php) counts Slime
// subtype cards currently in the controller's own banishment zone and deals that much damage
// to a chosen unit (an MZCHOOSE target prompt whenever more than one legal unit exists -- both
// players' champions always count). Two Red Slime copies (mttsvbgl6f, SLIME subtype) are
// seeded directly into myBanish as the damage-count fodder (banishment, not graveyard/field, so
// no concern about their own On Death trigger). Storm Slime's element is ARCANE, not native to
// the 'Spirit of Fire' starting champion, so the champion's Subcards are patched with a real
// ARCANE champion (Lorraine, Arclight Saber) purely to unlock element access. Storm Slime is
// played for real from hand via FSM so its Enter trigger genuinely fires; the MZCHOOSE targets
// player 2's champion (theirField-0).
// Notes carried over from the original definition:
//   - ARCANE lineage/element unlock
//   - Red Slime, banished #1
//   - Red Slime, banished #2
//   - target player 2's champion
gaScenario('storm-slime-enter-damage-equal-banished-slimes')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Lorraine, Arclight Saber'])
    ->seed('myBanish', 'Red Slime')
    ->seed('myBanish', 'Red Slime')
    ->seed('myHand', 'Storm Slime')
    ->castSpell('Storm Slime', pay: 2, target: 'theirField-0')
    ->expectDamage('p2Field-0', 2, 'Storm Slime\'s On Enter deals damage equal to the 2 Slime cards in the controller\'s own banishment')
    ->expectDqEmpty()
    ->register();

// --- Silvie, Slime Sovereign: champion On Enter -- next Slime ally costs 2 less, enters with 2 extra buff counters ---
// Silvie, Slime Sovereign is level 3, one level above a level-2 champion. Rather than grinding
// out two real prior level-ups, the starting champion's CardID is patched directly to Silvie,
// With the Pack (nllCALIXDT, level 2, TAMER) -- CanChampionLevelUpIntoCard only checks the
// CURRENT champion's own printed CardLevel, not lineage (same technique as
// lorraine-arclight-saber-levelup), so this satisfies the "current+1" legality gate for one
// real level-up at the start of player 1's second turn. Her On Enter ability
// (enterAbilities['mdwbkuhtjm:0'], GeneratedCode/GeneratedMacroCode.php) just calls
// AddGlobalEffects($player, 'mdwbkuhtjm'); Custom/GameLogic.php's HandAddToField() reads that
// global effect whenever a Slime-subtype ally enters the controller's field, adding 2 extra
// buff counters and consuming (removing) the effect, while the SEPARATE
// activationCostModifierAbilities['mdwbkuhtjm:0'] entry reads the same global effect's mere
// presence to apply a one-time -2 reserve-cost delta to the next Slime ally card activated.
// Her 3-memory level-up cost is paid from 3 filler memory-zone cards. Red Slime (mttsvbgl6f,
// printed reserve cost 3) is seeded to a known hand slot and played for real via FSM
// immediately after the level-up, in the same turn -- only ONE reserve-payment click follows
// (not the printed 3), proving the cost actually dropped to 1, and the resulting Red Slime
// carries 2 buff counters it has no other way to acquire, proving the "enters with two
// additional buff counters" clause. The separate "ignore the elemental requirements of
// advanced element Slime cards" static clause is a computed-effect clause with no stored flag
// to assert and is out of scope here.
// Notes carried over from the original definition:
//   - Silvie, With the Pack (level 2, TAMER) - level-up precondition + Spirit of Fire (FIRE unlock, since patching CardID directly erases the real lineage a genuine level-up would carry)
//   - real level-up into Silvie, Slime Sovereign
//   - the ONE discounted reserve click (printed cost 3, now 1)
gaScenario('silvie-slime-sovereign-levelup-next-slime-discount-counters')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Silvie, Slime Sovereign
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK
    )
    ->champion(cardName: 'Silvie, With the Pack', lineage: ['Spirit of Fire'])
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myHand', 'Red Slime')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')
    ->castSpell('Red Slime', pay: 1)
    ->tested('Silvie, Slime Sovereign')
    ->expectZoneCount('myHand', 7, 1, 'Only ONE reserve-payment click (not the printed 3) was needed to finish playing Red Slime, proving the -2 reserve-cost discount actually applied')
    ->expectProperty('p1Field-0', 'CardID', 'mdwbkuhtjm', 'The real champion level-up into Silvie, Slime Sovereign completed')
    ->expectProperty('p1Field-1', 'CardID', 'mttsvbgl6f', 'Red Slime actually entered the field')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":2}', 'Red Slime entered with 2 additional buff counters from Silvie, Slime Sovereign\'s On Enter clause')
    ->expectDqEmpty()
    ->register();

// --- Slime's Blessing: choose up to three units, level counter on champions / buff counter on Slime allies ---
// Slime's Blessing's ability (cardActivatedAbilities['ioxgugw9r9:0'] -> SlimesBlessingStart() ->
// SlimesBlessingAskTarget()/SlimesBlessingResolve(), Custom/GameLogic.php) repeatedly offers an
// MZMAYCHOOSE among not-yet-chosen units (both players' allies and champions) up to 3 times,
// then for each chosen unit applies a 'level' counter if it's a champion or a 'buff' counter if
// it's a Slime ally. Red Slime (mttsvbgl6f, ALLY/SLIME) is seeded onto the field as the
// buff-counter target; player 1's own champion (myField-0) is chosen as the level-counter
// target. Slime's Blessing's element is WIND, not native to the 'Spirit of Fire' starting
// champion, so the champion's Subcards are patched with a real WIND champion (Spirit of Wind)
// purely to unlock element access. Slime's Blessing is played for real from hand via FSM so its
// effect genuinely fires; the third optional pick is declined with PASS ("up to three" - the
// player may stop early) and the two counters earned by picks 1-2 must still be applied.
// Regression history: SlimesBlessingAskTarget() used to queue the paired CUSTOM
// 'SlimesBlessingChoose' decision without dontSkipOnPass, so ExecuteStaticMethods() skipped the
// handler on the decline and silently lost both counters (fixed: dontSkipOnPass:1). The separate
// [Class Bonus] "costs 1 less" discount clause is not reached (default champion has no class
// bonus) and is out of scope here.
// Notes carried over from the original definition:
//   - WIND lineage/element unlock
//   - Red Slime (Slime ally target) -> myField-1
//   - choose own champion (level counter)
//   - choose Red Slime (buff counter)
//   - decline the optional third pick
gaScenario('slimes-blessing-level-and-buff-counters')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Spirit of Wind'])
    ->seed('myField', 'Red Slime')
    ->seed('myHand', 'Slime\'s Blessing')
    ->play('Slime\'s Blessing')->pay(3)->target('myField-0')->target('myField-1')->pass(1)
    ->expectProperty('p1Field-0', 'Counters', '{"level":1}', 'Declining the 3rd pick still applies the level counter to the chosen champion (pick 1)')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'Declining the 3rd pick still applies the buff counter to the chosen Slime ally (pick 2)')
    ->expectDqEmpty()
    ->register();

// --- Slime King: additional cost -- banish three Slime allies with different elements from the graveyard ---
// Slime King (f0ymeslfpw, UNIQUE,ALLY): "As an additional cost to activate this card, banish three
// Slime ally cards each with different elements from your graveyard. Pride 4, Taunt. [Element
// Bonus] On Leave: ..." FIXED (was: the mandatory cost was never charged, $hasSlimeKingCost never
// set true in DoActivateCard's cost declaration). Slime King's element is TERA, not native to the
// 'Spirit of Fire' starting champion, so the champion's Subcards are patched with a real TERA
// champion (Kongming, Fel Eidolon) purely to unlock element access.
// Fixture: the graveyard holds Red Slime (FIRE), Storm Slime (ARCANE), Lustrous Slime (LUXEM) and a
// SECOND Red Slime (a duplicate FIRE element). Slime King is played for real (10002 FSM); the three
// banish picks are answered one by one (the duplicate-element Red Slime is rejected once Red is
// used), then the printed 3 reserve cost is paid.
// Notes carried over from the original definition:
//   - TERA lineage/element unlock
//   - Red Slime (FIRE) -> gy-0
//   - Storm Slime (ARCANE) -> gy-1
//   - Lustrous Slime (LUXEM) -> gy-2
//   - second Red Slime (duplicate FIRE) -> gy-3
//   - play Slime King: the banish cost is declared first
//   - pick 1 of 3: Red Slime (FIRE)
//   - pick 2 of 3: Storm Slime (ARCANE) -- the graveyard is already re-indexed after the first banish
//   - pick 3 of 3: Lustrous Slime (LUXEM)
//   - the printed (3) reserve cost
gaScenario('slime-king-additional-cost-banishes-three-distinct-slimes')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Kongming, Fel Eidolon'])
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myGraveyard', 'Storm Slime')
    ->seed('myGraveyard', 'Lustrous Slime')
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myHand', 'Slime King')
    ->castSpell('Slime King', target: 'myGraveyard-0')
    ->rejects(1, 'myGraveyard-2', 'The second Red Slime (now graveyard slot 2) repeats the FIRE element, so it is not a legal second banish choice')
    ->target('myGraveyard-0')->target('myGraveyard-0')->pay(3)
    ->register();

// --- Slime King: [Element Bonus] On Leave -- put the Slimes it banished as its cost back onto the field ---
// Slime King (f0ymeslfpw): "[Element Bonus] On Leave: You may put any number of the Slime ally
// cards banished by CARDNAME onto the field under your control." Before the cost fix nothing was ever
// banished BY Slime King, so this clause could never do anything. Slime King is played for real
// (three Slimes banished as the additional cost, (3) paid); player 2 then attacks it with a buffed
// Dungeon Guide (Taunt forces the attack onto Slime King), it is destroyed, and its On Leave offers
// the three banished Slimes one at a time: two are put onto the field and the offer is then declined.
// Notes carried over from the original definition:
//   - TERA lineage/element unlock (Element Bonus active: the champion's element must match Slime King's)
//   - Red Slime (FIRE)
//   - Storm Slime (ARCANE)
//   - Lustrous Slime (LUXEM)
//   - Slime King -> hand-7
//   - Dungeon Guide with 9 buff counters (10 power) -> p2 field-1
//   - play Slime King: banish cost first
//   - pick 1 of 3
//   - pick 2 of 3
//   - pick 3 of 3
//   - the printed (3) reserve cost
//   - end player 1 turn 1
//   - p1 passes the end-of-main fast window
//   - p1 passes the beginning-of-opponent-turn window
//   - player 2 attacks with the buffed Dungeon Guide
//   - Slime King (Taunt) is the target
//   - the interceptor prompt: Slime King takes the hit (it is the only candidate)
//   - On Leave: put the first banished Slime (Red Slime) onto the field
//   - On Leave: put the next one (Storm Slime) onto the field
//   - decline the third banished Slime (Lustrous Slime stays banished)
//   - an entering Slime's On Enter asks for a unit: the opposing champion
gaScenario('slime-king-on-leave-returns-banished-slimes')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->patch('myField-0', ['Subcards' => ['7x2v4tdop1'], 'Counters' => ['_overrides' => ['element' => 'TERA']]])
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myGraveyard', 'Storm Slime')
    ->seed('myGraveyard', 'Lustrous Slime')
    ->seed('myHand', 'Slime King')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Counters' => ['buff' => 9]])
    ->play('Slime King')->target('myGraveyard-0')->target('myGraveyard-0')->target('myGraveyard-0')->pay(3)
    ->endTurn()->pass(1)->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-1')->target('myField-1')->target('myBanish-0')
    ->target('myBanish-0')->pass(1)->target('theirField-0')
    ->register();

// --- Slime King: On Leave resolves when the defender declines the 'Retaliate?' prompt (engine bug FIXED) ---
// Same line-up as slime-king-on-leave-returns-banished-slimes, but the defending player answers the
// 'Retaliate?' MZMAYCHOOSE with PASS instead of picking Slime King. Slime King is still destroyed by
// the 10-power Dungeon Guide and its LEAVE_FIELD trigger is pushed on the EffectStack. Before the fix
// the push's EffectStackOpportunity CUSTOM was queued (unflagged) while the defender's decision loop
// still carried lastDecision == "PASS", so the loop silently skipped it and the trigger sat on the
// EffectStack forever (no 'put a banished Slime onto the field?' prompt). Fixed in
// Custom/OpportunityLogic.php (EffectStackOpportunity queued with dontSkipOnPass:1). Generic: see
// zephyr-assistant-on-leave-resolves-when-retaliation-declined.
// Notes carried over from the original definition:
//   - TERA lineage/element unlock (Element Bonus active: the champion's element must match Slime King's)
//   - Red Slime (FIRE)
//   - Storm Slime (ARCANE)
//   - Lustrous Slime (LUXEM)
//   - Slime King -> hand-7
//   - Dungeon Guide with 9 buff counters (10 power) -> p2 field-1
//   - play Slime King: banish cost first
//   - pick 1 of 3
//   - pick 2 of 3
//   - pick 3 of 3
//   - the printed (3) reserve cost
//   - end player 1 turn 1
//   - p1 passes the end-of-main fast window
//   - p1 passes the beginning-of-opponent-turn window
//   - player 2 attacks with the buffed Dungeon Guide
//   - Slime King (Taunt) is the target
//   - decline the 'Retaliate?' prompt: Slime King dies, its On Leave must still resolve
//   - On Leave: put the first banished Slime (Red Slime) onto the field
//   - decline the remaining banished Slimes
//   - Red Slime's On Enter asks for a unit: the opposing champion
gaScenario('slime-king-on-leave-resolves-when-retaliation-declined')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->patch('myField-0', ['Subcards' => ['7x2v4tdop1'], 'Counters' => ['_overrides' => ['element' => 'TERA']]])
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myGraveyard', 'Storm Slime')
    ->seed('myGraveyard', 'Lustrous Slime')
    ->seed('myHand', 'Slime King')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Counters' => ['buff' => 9]])
    ->play('Slime King')->target('myGraveyard-0')->target('myGraveyard-0')->target('myGraveyard-0')->pay(3)
    ->endTurn()->pass(1)->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-1')->pass(1)->target('myBanish-0')->pass(1)
    ->target('theirField-0')
    ->register();

// --- Slime King: cannot be played without three Slime allies with DIFFERENT elements in the graveyard ---
// Slime King (f0ymeslfpw): the mandatory banish-three-different-elements cost must be payable. The
// graveyard holds three Slime allies but only two distinct elements (two Red Slimes + Storm Slime),
// so the activation is refused up front (nothing banished, no reserve paid, Slime King stays in
// hand). Control: the same attempt with the third element present is covered by
// slime-king-additional-cost-banishes-three-distinct-slimes.
// Notes carried over from the original definition:
//   - TERA lineage/element unlock
//   - Red Slime (FIRE)
//   - second Red Slime (FIRE)
//   - Storm Slime (ARCANE)
//   - Slime King -> hand-7
gaScenario('slime-king-unplayable-with-only-two-distinct-slime-elements')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Kongming, Fel Eidolon'])
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myGraveyard', 'Storm Slime')
    ->seed('myHand', 'Slime King')
    ->play('Slime King')
    ->register();

// --- Slime Totem: Slime allies you control have vigor (wake at the controller's own end phase) ---
// Slime Totem's static ability (HasVigor(), Custom/GameLogic.php ~22070-22080) checks, for any
// ALLY/SLIME-subtype object, whether its controller has a live, unsuppressed Slime Totem
// (jwanjcy453) anywhere on their own field, and if so grants it Vigor. EndPhase()
// (Custom/GameLogic.php ~10730, specifically ~11364-11368) reads HasVigor() for every object on
// the TURN PLAYER's field and sets Status=2 (ready) for any that have it -- "Vigor units ready
// themselves at end of turn" -- which is the mechanical difference from a normal rested unit
// (which would stay rested until the START of its controller's OWN next turn, not the END of
// the current one). Red Slime (mttsvbgl6f, ALLY/SLIME) is seeded directly onto the field and
// its Status is set to 1 (rested) via setProperties -- a precondition no in-game action
// naturally produces at game start, so seeding it directly is the only way to reach it; Slime
// Totem itself has no activatable ability of its own to exercise (REGALIA/ITEM with purely
// static text), so seeding it directly onto the field (same "can't sit in hand" REGALIA
// technique as bauble-of-mending-banish-draw-class-bonus-life) is the correct way to establish
// its precondition too. Player 1 ends their own turn 1 (CustomInput Pass) -- confirmed live,
// Red Slime's Status flips from 1 (rested) to 2 (ready) as part of player 1's OWN EndPhase()
// processing, BEFORE the turn player even changes to player 2, proving the Vigor keyword is
// actually granted (a non-Vigor rested ally would stay rested through the opponent's entire
// turn, only recovering at the start of its own controller's next turn).
// Notes carried over from the original definition:
//   - Slime Totem -> myField-1
//   - Red Slime, rested -> myField-2
gaScenario('slime-totem-slime-allies-have-vigor')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->seed('myField', 'Slime Totem')
    ->seed('myField', 'Red Slime', properties: ['Status' => 1])
    ->endTurn()
    ->tested('Slime Totem')
    ->expectProperty('p1Field-2', 'Status', '2', 'Red Slime wakes at the controller\'s own end phase (Vigor), rather than staying rested until their next turn')
    ->expectDqEmpty()
    ->register();

// --- Slime Eruption: declining further banishes still deals the damage already earned (dontSkipOnPass regression) ---
// Slime Eruption: "You may banish any amount of fire element Slime ally cards and up to two
// non-fire element Slime ally cards from your graveyard. For each card banished this way, choose
// a unit and deal 1 damage to it." Declining a further banish offer must stop the banishing but
// still deal the damage earned for every card already banished.
//
// Regression history (FIXED, same class as Slime's Blessing): SlimeEruptionBanishLoop() queued the
// paired CUSTOM 'SlimeEruptionBanishPick' decision without dontSkipOnPass, and Core's
// ExecuteStaticMethods() skips a CUSTOM handler outright when the preceding answer was "PASS"
// unless the decision carries dontSkipOnPass -- so the handler's own PASS branch
// (SlimeEruptionDamageStep with the banished count) was dead code and declining lost ALL earned
// damage. The AddDecision call now passes dontSkipOnPass:1.
//
// Two Red Slimes (mttsvbgl6f, FIRE) sit in the graveyard; banish one for real, decline the second
// offer via PASS, then choose the opposing champion for the 1 earned damage. Slime Eruption's
// element is FIRE, native to 'Spirit of Fire', so no Subcards lineage patch is needed. The
// separate [Class Bonus] Floating Memory clause is not independently re-tested here.
// Notes carried over from the original definition:
//   - Red Slime (FIRE) #1
//   - Red Slime (FIRE) #2
//   - banish the first Red Slime for real
//   - decline the second banish offer
//   - the 1 damage earned for the 1 banished card: choose the opposing champion
gaScenario('slime-eruption-decline-keeps-earned-damage')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myHand', 'Slime Eruption')
    ->play('Slime Eruption')->pay(1)->target('myGraveyard-0')->pass(1)->target('theirField-0')
    ->register();

// --- Shifting Mirage: [Class Bonus] makes it cost exactly 2 (printed 3 - 1); opponent declines to pay (2); [Tristan Bonus] summons an Ominous Shadow ---
// Shifting Mirage (hmjr33ijq6, UMBRA REACTION action, printed reserve 3): "[Class Bonus] This card
// costs 1 less to activate. Your champion gains stealth until end of turn unless an opponent pays
// (2). [Tristan Bonus] Summon an Ominous Shadow token." Player 1's champion is patched to Tristan,
// Shadowdancer so UMBRA, the Class Bonus and the Tristan Bonus are all active. The card is played
// for real; with the Class Bonus it needs exactly TWO reserve payments (3 - 1 = 2) -- the third
// would be rejected as a hand-count change, so the assertions pin memory = 2 and hand = 5 (8 - the
// played card - 2 paid). ShiftingMirageResolve() (Custom/GameLogic.php) then queues a YES/NO for
// the opponent (who holds 7+ cards) and, regardless of the answer, summons the Ominous Shadow for
// the Tristan Bonus. ENGINE BUGS (both FIXED): (1) the Class Bonus discount used to be applied
// TWICE (the generated activationCostModifierAbilities['hmjr33ijq6:0'] AND the hand-maintained
// ClassBonusActivateCostReduction() table), so the card cost 1 instead of 2 -- the table entry was
// removed. (2) Stealth used to land on the wrong champion when the opponent declines
// (customDQHandlers["ShiftingMiragePay"] runs in the OPPONENT's action but the champion mzID was
// stored relative to player 1): ShiftingMirageResolve() now stores it relative to the opponent via
// NormalizeMzIDForController(), so the CASTER's champion gains STEALTH.
// Notes carried over from the original definition:
//   - Tristan, Shadowdancer (UMBRA/ASSASSIN, Tristan lineage): Class Bonus and Tristan Bonus active
//   - Shifting Mirage -> p1 myHand-7
//   - play Shifting Mirage from hand
//   - the opponent declines to pay (2) to stop the stealth
gaScenario('shifting-mirage-class-bonus-costs-two-opponent-declines-stealth-shadow')
    ->deck($tristanDeck)
    ->champion(cardName: 'Tristan, Shadowdancer', lineage: ['Tristan, Hired Blade', 'Tristan, Underhanded', 'Spirit of Wind'])
    ->seed('myHand', 'Shifting Mirage')
    ->play('Shifting Mirage')->pay(2)->answer(2, 'NO')
    ->expectZoneCount('myMemory', 1, 1, 'After the FIRST reserve payment only one card is in memory', step: 2)
    ->expectZoneCount('myGraveyard', 0, 1, 'After ONE payment Shifting Mirage has NOT resolved: printed 3 minus a single Class Bonus discount is 2, not 1', step: 2)
    ->expectZoneCount('myMemory', 2, 1, 'Exactly TWO reserve cards were paid: printed 3 minus the one [Class Bonus] discount', step: 3)
    ->expectProperty('p1Graveyard-0', 'CardID', 'hmjr33ijq6', 'Shifting Mirage resolved only after the second payment', step: 3)
    ->expectProperty('p1Field-1', 'CardID', 'gveirpdm44', '[Tristan Bonus] summoned an Ominous Shadow token', step: 3)
    ->expectZoneCount('myHand', 5, 1, 'Hand 8 - the played card - 2 reserve payments = 5')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","STEALTH"]', 'The opponent declined to pay (2): the CASTER\'s champion gains STEALTH until end of turn')
    ->expectProperty('p2Field-0', 'TurnEffects', '[]', 'The opponent\'s champion gains nothing (stealth must not land on the opponent\'s own champion)')
    ->expectZoneCount('theirMemory', 0, 1, 'The opponent declined, so paid nothing')
    ->expectDqEmpty()
    ->register();

// --- Shifting Mirage: opponent pays (2), so the champion does not gain stealth; [Tristan Bonus] still summons an Ominous Shadow ---
// Shifting Mirage (hmjr33ijq6): "Your champion gains stealth until end of turn unless an opponent
// pays (2)." Same setup as shifting-mirage-class-bonus-costs-two-opponent-declines-stealth-shadow
// (caster pays exactly 2 with the Class Bonus), but the opponent answers YES and pays two reserve
// cards (ReserveCard x2 inside customDQHandlers["ShiftingMiragePay"]): neither champion gains
// stealth, the opponent's memory grows by exactly 2 cards, and the Tristan Bonus Ominous Shadow is
// still summoned.
// Notes carried over from the original definition:
//   - Tristan, Shadowdancer (UMBRA/ASSASSIN, Tristan lineage): Class Bonus and Tristan Bonus active
//   - Shifting Mirage -> p1 myHand-7
//   - play Shifting Mirage from hand
//   - the opponent pays (2) to stop the stealth
//   - opponent payment 1/2
//   - opponent payment 2/2
gaScenario('shifting-mirage-class-bonus-costs-two-opponent-pays-no-stealth-shadow')
    ->deck($tristanDeck)
    ->champion(cardName: 'Tristan, Shadowdancer', lineage: ['Tristan, Hired Blade', 'Tristan, Underhanded', 'Spirit of Wind'])
    ->seed('myHand', 'Shifting Mirage')
    ->play('Shifting Mirage')->pay(2)->answer(2, 'YES')->pay(2, 2)
    ->expectZoneCount('myGraveyard', 0, 1, 'After ONE payment Shifting Mirage has NOT resolved: printed 3 minus a single Class Bonus discount is 2, not 1', step: 2)
    ->expectZoneCount('myMemory', 2, 1, 'Exactly TWO reserve cards were paid by the caster: printed 3 minus the one [Class Bonus] discount', step: 3)
    ->expectProperty('p1Graveyard-0', 'CardID', 'hmjr33ijq6', 'Shifting Mirage resolved only after the second payment', step: 3)
    ->expectZoneCount('theirMemory', 2, 1, 'The opponent paid exactly (2): two cards moved from their hand to their memory')
    ->expectZoneCount('theirHand', 5, 1, 'The opponent\'s hand dropped from 7 to 5')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]', 'The caster\'s champion gains no stealth because the opponent paid')
    ->expectProperty('p2Field-0', 'TurnEffects', '[]', 'The opponent\'s champion gained no stealth either')
    ->expectProperty('p1Field-1', 'CardID', 'gveirpdm44', '[Tristan Bonus] summoned an Ominous Shadow token')
    ->expectZoneCount('myMemory', 2, 1, 'The caster\'s memory is still exactly 2 (the opponent\'s payment went to the opponent\'s own memory)')
    ->expectDqEmpty()
    ->register();

// --- Shifting Mirage: champion gains stealth until end of turn when the opponent cannot pay (2) ---
// Shifting Mirage (hmjr33ijq6): when the opponent holds fewer than 2 cards ShiftingMirageResolve()
// skips the YES/NO and applies the stealth turn effect directly while still under player 1's own
// perspective, so the CASTER's champion gains STEALTH (the declined-payment path, which resolves
// it inside the opponent's action, is covered by shifting-mirage-class-bonus-costs-two-opponent-
// declines-stealth-shadow). The opponent's hand is emptied through the generator's emptyZone setup
// step. The Tristan Bonus Ominous Shadow is summoned as well. The caster pays exactly 2 reserve
// cards (printed 3 minus the one Class Bonus discount).
// Notes carried over from the original definition:
//   - Tristan, Shadowdancer (UMBRA/ASSASSIN, Tristan lineage): Class Bonus and Tristan Bonus active
//   - opponent hand emptied: cannot pay (2)
//   - Shifting Mirage -> p1 myHand-7
//   - play Shifting Mirage from hand
gaScenario('shifting-mirage-class-bonus-costs-two-opponent-cannot-pay-stealth')
    ->deck($tristanDeck)
    ->champion(cardName: 'Tristan, Shadowdancer', lineage: ['Tristan, Hired Blade', 'Tristan, Underhanded', 'Spirit of Wind'])
    ->emptyZone('myHand', player: 2)
    ->seed('myHand', 'Shifting Mirage')
    ->castSpell('Shifting Mirage', pay: 2)
    ->expectZoneCount('myGraveyard', 0, 1, 'After ONE payment Shifting Mirage has NOT resolved: printed 3 minus a single Class Bonus discount is 2, not 1', step: 2)
    ->expectZoneCount('myMemory', 2, 1, 'Exactly TWO reserve cards were paid: printed 3 minus the one [Class Bonus] discount')
    ->expectProperty('p1Graveyard-0', 'CardID', 'hmjr33ijq6', 'Shifting Mirage resolved only after the second payment')
    ->expectProperty('p1Field-0', 'TurnEffects', '["STEALTH","vz4kc558yx-ACTION_ACTIVATED"]', 'The caster\'s champion gains stealth until end of turn (the opponent cannot pay (2))')
    ->expectProperty('p2Field-0', 'TurnEffects', '[]', 'The opponent\'s champion gains nothing')
    ->expectProperty('p1Field-1', 'CardID', 'gveirpdm44', '[Tristan Bonus] summoned an Ominous Shadow token')
    ->expectDqEmpty()
    ->register();

// --- Shifting Mirage: negative case -- WITHOUT the Class Bonus it costs its full printed 3 ---
// Shifting Mirage (hmjr33ijq6, printed reserve 3): "[Class Bonus] This card costs 1 less to
// activate." The champion is patched to Diana, Duskstalker (UMBRA so the card is playable, but class
// RANGER, not ASSASSIN, and no Tristan lineage), so neither the Class Bonus discount nor the
// [Tristan Bonus] Ominous Shadow applies: the caster must pay all THREE reserve cards (memory 3,
// hand 8 - 1 - 3 = 4), the opponent declines the (2) payment and Diana gains STEALTH, and no token
// is summoned.
// Notes carried over from the original definition:
//   - Diana, Duskstalker (UMBRA, class RANGER - NOT ASSASSIN): UMBRA enabled so the card is playable, Class Bonus and Tristan Bonus inactive
//   - Shifting Mirage -> p1 myHand-7
//   - play Shifting Mirage from hand
//   - the opponent declines to pay (2)
gaScenario('shifting-mirage-no-class-bonus-costs-printed-three')
    ->deck($tristanDeck)
    ->champion(cardName: 'Diana, Duskstalker', lineage: ['Spirit of Wind'])
    ->seed('myHand', 'Shifting Mirage')
    ->play('Shifting Mirage')->pay(3)->answer(2, 'NO')
    ->expectZoneCount('myMemory', 2, 1, 'After TWO payments the card still has not resolved without the Class Bonus (cost stays at the printed 3)', step: 3)
    ->expectZoneCount('myGraveyard', 0, 1, 'Shifting Mirage is still unresolved after two payments (no discount without the Class Bonus)', step: 3)
    ->expectZoneCount('myMemory', 3, 1, 'Exactly THREE reserve cards were paid: the full printed cost with the Class Bonus inactive', step: 4)
    ->expectProperty('p1Graveyard-0', 'CardID', 'hmjr33ijq6', 'Shifting Mirage resolved after the third payment', step: 4)
    ->expectZoneCount('myHand', 4, 1, 'Hand 8 - the played card - 3 reserve payments = 4')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","STEALTH"]', 'The opponent declined to pay (2): the caster\'s champion gains STEALTH until end of turn')
    ->expectZoneCount('myField', 1, 1, 'No Ominous Shadow token: the [Tristan Bonus] is inactive')
    ->expectProperty('p2Field-0', 'TurnEffects', '[]', 'The opponent\'s champion gains nothing')
    ->expectDqEmpty()
    ->register();

// --- Shadow Resonance: Recover 2, then draw a card; [Tristan Bonus] with  four preparation counters summons an Ominous Shadow ---
// Shadow Resonance (10rsagp9m8, UMBRA action, reserve 2): "Recover 2, then draw a card. [Tristan
// Bonus] If your champion has four or more preparation counters on them, summon an Ominous Shadow
// token." Tristan, Shadowdancer (UMBRA, Tristan lineage) is patched in with 5 damage and 4
// preparation counters. Played for real: RecoverChampion(2) takes Damage from 5 to 3, Draw(1) adds
// a card (hand 8 -> 7 after playing it -> 5 after paying 2 -> 6 after the draw), and with exactly
// 4 counters the Tristan Bonus summons an Ominous Shadow.
// Notes carried over from the original definition:
//   - Tristan, Shadowdancer with 5 damage and 4 preparation counters
//   - Shadow Resonance -> p1 myHand-7
//   - play Shadow Resonance
gaScenario('shadow-resonance-recover-draw-tristan-bonus-shadow-at-four-preparation')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7'], 'Damage' => 5, 'Counters' => ['preparation' => 4]])
    ->seed('myHand', 'Shadow Resonance')
    ->castSpell('Shadow Resonance', pay: 2)
    ->expectDamage('p1Field-0', 3, 'Recover 2 removed two of the five damage counters')
    ->expectZoneCount('myHand', 6, 1, 'Hand: 8 - 1 (played) - 2 (reserve) + 1 (drawn) = 6')
    ->expectProperty('p1Graveyard-0', 'CardID', '10rsagp9m8', 'Shadow Resonance resolved')
    ->expectProperty('p1Field-1', 'CardID', 'gveirpdm44', 'Four preparation counters + Tristan lineage summoned an Ominous Shadow')
    ->expectZoneCount('myField', 2, 1, 'Tristan plus the summoned token')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":4}', 'Shadow Resonance does not consume preparation counters')
    ->expectDqEmpty()
    ->register();

// --- Shadow Resonance: Recover 2, then draw a card; [Tristan Bonus] with only three (fewer than) four preparation counters summons nothing ---
// Shadow Resonance (10rsagp9m8, UMBRA action, reserve 2): "Recover 2, then draw a card. [Tristan
// Bonus] If your champion has four or more preparation counters on them, summon an Ominous Shadow
// token." Tristan, Shadowdancer (UMBRA, Tristan lineage) is patched in with 5 damage and 3
// preparation counters. Played for real: RecoverChampion(2) takes Damage from 5 to 3, Draw(1) adds
// a card (hand 8 -> 7 after playing it -> 5 after paying 2 -> 6 after the draw), and with only 3
// counters (below the threshold) no token is summoned.
// Notes carried over from the original definition:
//   - Tristan, Shadowdancer with 5 damage and 3 preparation counters
//   - Shadow Resonance -> p1 myHand-7
//   - play Shadow Resonance
gaScenario('shadow-resonance-no-shadow-below-four-preparation')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7'], 'Damage' => 5, 'Counters' => ['preparation' => 3]])
    ->seed('myHand', 'Shadow Resonance')
    ->castSpell('Shadow Resonance', pay: 2)
    ->expectDamage('p1Field-0', 3, 'Recover 2 removed two of the five damage counters')
    ->expectZoneCount('myHand', 6, 1, 'Hand: 8 - 1 (played) - 2 (reserve) + 1 (drawn) = 6')
    ->expectProperty('p1Graveyard-0', 'CardID', '10rsagp9m8', 'Shadow Resonance resolved')
    ->expectZoneCount('myField', 1, 1, 'Only Tristan on the field: no Ominous Shadow with three preparation counters')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":3}', 'Shadow Resonance does not consume preparation counters')
    ->expectDqEmpty()
    ->register();

// --- Stifling Trap: deal 2 damage to target ally, then negate its on enter triggers ---
// Stifling Trap (z5exbwdp7q, WIND action, REACTION, reserve 2): "Deal 2 damage to target ally,
// then negate all on enter triggers from that ally." Played for real with two reserve payments (no
// Class Bonus alternate cost involved); StiflingTrapStart() offers the opposing allies as targets
// and customDQHandlers['StiflingTrapApply'] deals 2 damage (DealDamage) and tags the target with
// the NO_ABILITIES turn effect, which is how this engine models negating the ally's on enter
// triggers: an on enter ability resolves as soon as the ally enters (observed: after passing
// priority on an ally activation, its On Enter prompt appears immediately with no separate
// priority window for the trigger), so the card can only be aimed at an ally already on the field
// and the engine suppresses that ally's abilities for the turn instead. The Class Bonus alternate
// cost clause is covered by the stifling-trap-memory-alt-cost-* fixtures.
// Notes carried over from the original definition:
//   - Tristan, Hired Blade (ASSASSIN)
//   - Stifling Trap -> p1 myHand-7
//   - Dungeon Guide (the target ally) -> p2 field-1
//   - play Stifling Trap (a Reaction may be activated in the main phase)
//   - target the opposing Dungeon Guide
gaScenario('stifling-trap-damage-and-negate-on-enter')
    ->deck($tristanDeck)
    ->champion(cardName: 'Tristan, Hired Blade', lineage: ['Tristan, Underhanded', 'Spirit of Wind'])
    ->seed('myHand', 'Stifling Trap')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->castSpell('Stifling Trap', pay: 2, target: 'theirField-1')
    ->expectDamage('p2Field-1', 2, 'The target ally takes exactly 2 damage')
    ->expectProperty('p2Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DAMAGED_SINCE_LAST_TURN","NO_ABILITIES"]', 'The target ally is tagged NO_ABILITIES, the engine\'s negation of its triggers')
    ->expectZoneCount('myMemory', 2, 1, 'Two reserve payments (printed reserve 2, no discount)')
    ->expectProperty('p1Graveyard-0', 'CardID', 'z5exbwdp7q', 'Stifling Trap resolved')
    ->expectDqEmpty()
    ->register();

// Alternate cost PAID: the reserve cost is skipped entirely (no reserve payment is ever asked of player 1).
// Notes carried over from the original definition:
//   - player 1 activates Stifling Trap FROM MEMORY (alternate cost: remove two preparation counters)
//   - target the opposing seeded Dungeon Guide
//   - player 2 declines the newly played Dungeon Guide's On Enter
gaScenario('stifling-trap-memory-alt-cost-paid-no-reserve')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'gt7lh9v221', 'Subcards' => ['bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 2]])
    ->seed('myMemory', 'Stifling Trap')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->playSlot(0, 2)->pay(3, 2)->target('myMemory-0')->target('theirField-1')->answer(2, 'NO')
    ->tested('Stifling Trap')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":2}', 'Before answering the window: both preparation counters are still on the champion', step: 5)
    ->expectProperty('p1Memory-0', 'CardID', 'z5exbwdp7q', 'Before answering the window: Stifling Trap is in memory', step: 5)
    ->expectProperty('p1Field-0', 'Counters', '[]', 'Alternate cost paid: exactly the two preparation counters were removed from the champion', step: 6)
    ->expectZoneCount('myMemory', 0, 1, 'Stifling Trap left memory and NO reserve cards were paid into memory (a reserve payment would leave 2)', step: 6)
    ->expectZoneCount('myHand', 7, 1, 'No reserve payment: player 1\'s hand is untouched', step: 6)
    ->expectProperty('p1Field-0', 'Counters', '[]', 'Counters still 0 after the whole sequence')
    ->expectProperty('p1Graveyard-0', 'CardID', 'z5exbwdp7q', 'Stifling Trap resolved and went to the graveyard')
    ->expectZoneCount('myGraveyard', 1, 1, 'Exactly one card (the Trap) is in player 1\'s graveyard')
    ->expectZoneCount('myMemory', 0, 1, 'Memory is still empty (never paid reserve)')
    ->expectZoneCount('myHand', 7, 1, 'Hand is still 7 (never paid reserve)')
    ->expectDamage('p2Field-1', 2, 'The targeted seeded Dungeon Guide took exactly 2 damage')
    ->expectProperty('p2Field-1', 'TurnEffects', '["DAMAGED_SINCE_LAST_TURN","NO_ABILITIES"]', 'The target is tagged NO_ABILITIES (the engine\'s negation of its on enter triggers)')
    ->expectDqEmpty()
    ->register();

// Normal reserve payment AND choosing between the two payments: Stifling Trap sits in hand AND in memory,
// player 1 has the 2 counters; the window offers both; player 1 picks the HAND copy and pays reserve,
// so the counters and the memory copy are untouched.
// Notes carried over from the original definition:
//   - player 1 chooses the HAND copy (pays reserve normally)
//   - p1 keeps priority on its own Stifling Trap (the memory copy is still an offered option) and passes; the opponent has nothing, so it resolves
//   - target the opposing seeded Dungeon Guide
//   - Stifling Trap resolved; p1 passes priority again on the Dungeon Guide (the memory copy is still an offered option)
//   - player 2 declines the newly played Dungeon Guide's On Enter
gaScenario('stifling-trap-memory-alt-cost-hand-copy-pays-reserve-keeps-counters')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'gt7lh9v221', 'Subcards' => ['bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 2]])
    ->seed('myMemory', 'Stifling Trap')
    ->seed('myHand', 'Stifling Trap')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->playSlot(0, 2)->pay(3, 2)->target('myHand-7')->pay(2)->pass(1)->target('theirField-1')->pass(1)->answer(2, 'NO')
    ->tested('Stifling Trap')
    ->register();

// DECLINE: the alternate cost is optional ("you may"); passing the window leaves the card and counters alone.
// Notes carried over from the original definition:
//   - player 1 declines the window (does not use the alternate cost)
//   - player 2 declines the newly played Dungeon Guide's On Enter
gaScenario('stifling-trap-memory-alt-cost-declined-keeps-counters')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'gt7lh9v221', 'Subcards' => ['bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 2]])
    ->seed('myMemory', 'Stifling Trap')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->playSlot(0, 2)->pay(3, 2)->pass(1)->answer(2, 'NO')
    ->tested('Stifling Trap')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":2}', 'Declined: both preparation counters are still on the champion')
    ->expectProperty('p1Memory-0', 'CardID', 'z5exbwdp7q', 'Declined: Stifling Trap is still in memory')
    ->expectZoneCount('myMemory', 1, 1, 'Declined: memory untouched')
    ->expectZoneCount('myGraveyard', 0, 1, 'Declined: nothing was activated')
    ->expectZoneCount('myHand', 7, 1, 'Declined: hand untouched')
    ->expectDamage('p2Field-1', 0, 'Declined: the seeded Dungeon Guide was never targeted')
    ->expectDqEmpty()
    ->register();

// CLASS BONUS INACTIVE: Vanitas, Obliviate Schemer is a CLERIC champion with the WIND element (so the
// WIND card itself is still activatable) and 2 preparation counters, but the [Class Bonus] (ASSASSIN) is
// off, so the alternate cost is NOT offered. Fairy Whispers keeps windows open. Expected rejection.
// Notes carried over from the original definition:
//   - Vanitas, Obliviate Schemer (CLERIC, WIND) with 2 preparation counters: Class Bonus (ASSASSIN) inactive
//   - p1 passes the window
//   - player 2 declines the newly played Dungeon Guide's On Enter
gaScenario('stifling-trap-memory-alt-cost-class-bonus-inactive-not-offered')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'x8bd7ozuj6', 'Subcards' => ['pNiyaGlIe7'], 'Counters' => ['preparation' => 2]])
    ->seed('myMemory', 'Stifling Trap')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->playSlot(0, 2)->pay(3, 2)
    ->rejects(1, 'myMemory-0', 'Class Bonus inactive (CLERIC champion): the memory Stifling Trap is not an offered option, selecting it is rejected')
    ->pass(1)->answer(2, 'NO')
    ->tested('Stifling Trap')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":2}', 'Class Bonus inactive: the preparation counters were never spent')
    ->expectProperty('p1Field-0', 'CardID', 'x8bd7ozuj6', 'Player 1\'s champion is the CLERIC Vanitas (no ASSASSIN class bonus)')
    ->expectProperty('p1Memory-0', 'CardID', 'z5exbwdp7q', 'Class Bonus inactive: Stifling Trap is still in memory')
    ->expectZoneCount('myMemory', 1, 1, 'Class Bonus inactive: memory untouched')
    ->expectZoneCount('myGraveyard', 0, 1, 'Class Bonus inactive: nothing was activated')
    ->expectZoneCount('myHand', 8, 1, 'Class Bonus inactive: hand untouched (Fairy Whispers still held)')
    ->expectDamage('p2Field-1', 0, 'Class Bonus inactive: the seeded Dungeon Guide was never targeted')
    ->expectDqEmpty()
    ->register();

// CAN'T AFFORD EITHER: Class Bonus active but only ONE preparation counter (needs two), and no Stifling
// Trap in hand to pay reserve with. A rejected click may be a no-op, so the assertions pin state.
// Notes carried over from the original definition:
//   - Tristan, Hired Blade (ASSASSIN) with only 1 preparation counter
//   - p1 passes the window
//   - player 2 declines the newly played Dungeon Guide's On Enter
gaScenario('stifling-trap-memory-alt-cost-one-counter-not-offered')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'gt7lh9v221', 'Subcards' => ['bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 1]])
    ->seed('myMemory', 'Stifling Trap')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->playSlot(0, 2)->pay(3, 2)
    ->rejects(1, 'myMemory-0', 'Only one preparation counter: the alternate cost is unaffordable, the memory Stifling Trap is not offered and selecting it is rejected')
    ->pass(1)->answer(2, 'NO')
    ->tested('Stifling Trap')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'Only one counter: it was not spent and the card was not activated')
    ->expectProperty('p1Memory-0', 'CardID', 'z5exbwdp7q', 'Only one counter: Stifling Trap is still in memory')
    ->expectZoneCount('myMemory', 1, 1, 'Only one counter: memory untouched')
    ->expectZoneCount('myGraveyard', 0, 1, 'Only one counter: nothing was activated')
    ->expectZoneCount('myHand', 8, 1, 'Only one counter: hand untouched (Fairy Whispers still held)')
    ->expectDamage('p2Field-1', 0, 'Only one counter: the seeded Dungeon Guide was never targeted')
    ->expectDqEmpty()
    ->register();

// "IF IT'S NOT YOUR TURN": on player 1's OWN turn (its end-of-turn window) the alternate cost is not offered.
// Notes carried over from the original definition:
//   - end player 1 turn -> own end-of-turn window (Fairy Whispers keeps it open)
//   - p1 passes the window
//   - p1 passes the second window
gaScenario('stifling-trap-memory-alt-cost-own-turn-not-offered')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'gt7lh9v221', 'Subcards' => ['bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 2]])
    ->seed('myMemory', 'Stifling Trap')
    ->seed('myHand', 'Fairy Whispers')
    ->endTurn()
    ->rejects(1, 'myMemory-0', 'It is player 1\'s own turn: the memory Stifling Trap is not an offered option, selecting it is rejected')
    ->pass(1)->pass(1)
    ->tested('Stifling Trap')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":2}', 'Own turn: the preparation counters were never spent')
    ->expectProperty('p1Memory-0', 'CardID', 'z5exbwdp7q', 'Own turn: Stifling Trap is still in memory')
    ->expectZoneCount('myMemory', 1, 1, 'Own turn: memory untouched')
    ->expectZoneCount('myGraveyard', 0, 1, 'Own turn: nothing was activated')
    ->expectZoneCount('myHand', 8, 1, 'Own turn: hand untouched (Fairy Whispers still held)')
    ->expectDqEmpty()
    ->register();

// --- Stand Before the Queen (v9SJgS6z40): [Sheen 8+] first prevented damage grants stealth unless an opponent pays (2); opponent declines ---
// Stand Before the Queen: "Prevent the next 2 damage that would be dealt to target unit this turn.
// [Sheen 8+] The first time damage is prevented this way, the unit gains stealth until end of turn
// unless an opponent pays (2)." (CombatLogic.php damage-prevention + customDQHandlers
// ["StandBeforeTheQueenPay"].) Player 1 holds a Fractured Memories mastery seeded with 8 sheen
// counters and a Stand Before the Queen in hand (water-element deck, no other fast cards). Player 2
// attacks player 1's Dungeon Guide with its own Dungeon Guide; in the attack's priority window
// player 1 casts Stand Before the Queen for real targeting their attacked Dungeon Guide, declines to
// retaliate, and the attack's 1 damage is prevented. Player 2 (the unit's opponent, 7+ cards) is
// asked whether to pay (2) and declines. The STEALTH must land on the PROTECTED unit (player 1's
// Dungeon Guide), not on the same slot of the paying opponent's own field. NOTE: the target mzID is
// now stored relative to the paying opponent (NormalizeMzIDForController) like Shifting Mirage's
// champion, but this scenario also passes WITHOUT that hardening (the prevention fires under the
// damage source's perspective, which is the paying opponent here), so it is correct-path coverage,
// not a regression guard for the perspective fix.
// Notes carried over from the original definition:
//   - Fractured Memories with 8 sheen
//   - Dungeon Guide (the protected unit) -> p1 field-1
//   - Dungeon Guide (attacker) -> p2 field-1
//   - Stand Before the Queen -> p1 myHand-7
//   - end player 1 turn
//   - p1 passes its window
//   - p1 passes its window
//   - p2 attacks with its Dungeon Guide
//   - targeting player 1's Dungeon Guide
//   - p1 casts Stand Before the Queen in the attack window
//   - p1 targets its own (attacked) Dungeon Guide
//   - p1 declines to retaliate
//   - p2 declines to pay (2)
gaScenario('stand-before-the-queen-sheen-stealth-opponent-declines')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
15 Dungeon Guide
15 Fairy Whispers
15 Fluffy Shopkeep
DECK
    )
    ->seed('myMastery', 'Fractured Memories', properties: ['Counters' => ['sheen' => 8]])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Stand Before the Queen')
    ->endTurn()->pass(1)->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-1')->target('myHand-7')->pay(2)->target('myField-1')
    ->pass(1)->answer(2, 'NO')
    ->tested('Stand Before the Queen')
    ->expectProperty('p1Field-1', 'TurnEffects', '["STAND_BEFORE_THE_QUEEN_1","STEALTH"]', '[Sheen 8+] The opponent declined to pay (2): the PROTECTED unit (player 1\'s Dungeon Guide) gained STEALTH after the first damage was prevented (2 -> 1 prevention left)')
    ->expectDamage('p1Field-1', 0, 'The attack\'s damage was prevented: the protected Dungeon Guide took 0')
    ->expectProperty('p2Field-1', 'TurnEffects', '[]', 'STEALTH did NOT land on the paying opponent\'s own Dungeon Guide at the same slot')
    ->expectZoneCount('myMemory', 0, 2, 'Declining cost the opponent nothing: no card moved into their memory')
    ->expectDqEmpty()
    ->register();

// --- Shadeblood Coating: Banish: up to three target units you control gain "On Hit: Put a preparation counter on your champion" until end of turn ---
// Shadeblood Coating (nd8dy77ikm, UMBRA REGALIA/ACCESSORY, memory 0): "Banish CARDNAME: Up to
// three target units you control gain 'On Hit: Put a preparation counter on your champion' until
// end of turn." The champion's Subcards are patched with Tristan, Shadowdancer for UMBRA access;
// two Dungeon Guides are seeded on player 1's field. Shadeblood is materialized for real in the
// materialize phase; its banish ability (the opportunity window offers
// 'myField-3@Activate-0@nd8dy77ikm') asks for up to three targets in a chain of MZMAYCHOOSEs
// (champion, first Dungeon Guide, then PASS) and tags each with the nd8dy77ikm_ONHIT turn effect,
// which CombatLogic.php's on-hit processing converts into AddPrepCounter(). Then the CHOSEN
// Dungeon Guide attacks the opposing champion and its hit puts exactly one preparation counter on
// player 1's champion; the NON-chosen Dungeon Guide's later hit adds nothing.
// Notes carried over from the original definition:
//   - UMBRA lineage unlock (Tristan, Shadowdancer)
//   - Dungeon Guide (chosen: gains On Hit) -> p1 field-1
//   - Dungeon Guide (control, NOT chosen) -> p1 field-2
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - materialize Shadeblood Coating
//   - banish Shadeblood Coating
//   - first unit: the champion
//   - second unit: the first Dungeon Guide
//   - decline the optional third unit
//   - the chosen Dungeon Guide attacks
//   - target the opposing champion
//   - the NON-chosen Dungeon Guide attacks
//   - target the opposing champion
gaScenario('shadeblood-coating-banish-grants-on-hit-preparation')
    ->deck($tristanDeck)
    ->champion(lineage: ['Tristan, Shadowdancer', 'Spirit of Wind'])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-6')->answer(1, 'myField-3@Activate-0@nd8dy77ikm')->target('myField-0')
    ->target('myField-1')->pass(1)
    ->action(1, 10002, 'myField-1!FSM!')->target('theirField-0')
    ->action(1, 10002, 'myField-2!FSM!')->target('theirField-0')
    ->tested('Shadeblood Coating')
    ->expectProperty('p1Field-3', 'CardID', 'nd8dy77ikm', 'Shadeblood Coating entered the field via a real materialize', step: 3)
    ->expectProperty('p1Field-0', 'TurnEffects', '["nd8dy77ikm_ONHIT"]', 'The champion (first target) gained the granted On Hit', step: 7)
    ->expectProperty('p1Field-1', 'TurnEffects', '["nd8dy77ikm_ONHIT"]', 'The first Dungeon Guide (second target) gained the granted On Hit', step: 7)
    ->expectProperty('p1Field-2', 'TurnEffects', '[]', 'The third unit was declined (PASS), so the second Dungeon Guide gained nothing', step: 7)
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'The chosen Dungeon Guide\'s hit put exactly one preparation counter on the champion', step: 9)
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'The non-chosen Dungeon Guide\'s hit added no further preparation counter')
    ->expectDamage('p2Field-0', 2, 'Both Dungeon Guides hit the opposing champion for 1 each (so both hits really landed)')
    ->expectProperty('p1Banish-0', 'CardID', 'nd8dy77ikm', 'Shadeblood Coating was banished as the ability cost')
    ->expectDqEmpty()
    ->register();

// --- Sirocco Operative: [Class Bonus] On Enter draw a card into memory; On Enter if you control another Automaton ally, put a preparation counter on your champion ---
// Sirocco Operative (t7ru41pzgg, WIND ALLY, ASSASSIN/AUTOMATON, reserve 3): "[Class Bonus] On
// Enter: Draw a card into your memory. On Enter: If you control another Automaton ally, put a
// preparation counter on your champion." The champion is patched to Tristan, Hired Blade
// (ASSASSIN) for the Class Bonus and a Shimmercloak Assassin (an Automaton) is seeded as the
// 'other Automaton'. Sirocco is played for real (3 reserve payments), so
// enterAbilities['t7ru41pzgg:0'] genuinely fires: DrawIntoMemory(1) adds a 4th card to memory and
// AddPrepCounter() puts one preparation counter on the champion.
// Notes carried over from the original definition:
//   - Tristan, Hired Blade (ASSASSIN) -> Class Bonus active
//   - Shimmercloak Assassin (an Automaton ally) = the "another Automaton" -> p1 field-1
//   - Sirocco Operative -> p1 myHand-7
//   - play Sirocco Operative
gaScenario('sirocco-operative-enter-class-bonus-draw-memory-and-automaton-prep')
    ->deck($tristanDeck)
    ->champion(cardName: 'Tristan, Hired Blade', lineage: ['Tristan, Underhanded', 'Spirit of Wind'])
    ->seed('myField', 'Shimmercloak Assassin')
    ->seed('myHand', 'Sirocco Operative')
    ->castSpell('Sirocco Operative', pay: 3)
    ->expectProperty('p1Field-2', 'CardID', 't7ru41pzgg', 'Sirocco Operative entered the field')
    ->expectZoneCount('myMemory', 4, 1, 'Memory holds 4 cards: 3 reserve payments plus the card drawn into memory by the Class Bonus On Enter')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'Another Automaton ally exists, so one preparation counter was put on the champion')
    ->expectDqEmpty()
    ->register();

// --- Sirocco Operative: neither On Enter clause applies without the Class Bonus and without another Automaton ally ---
// Sirocco Operative (t7ru41pzgg): both On Enter clauses are conditional. With the starting
// champion Spirit of Wind (no Assassin class, so no Class Bonus) and no other Automaton ally,
// playing Sirocco draws nothing into memory (memory holds only the 3 reserve payments) and puts no
// preparation counter on the champion.
// Notes carried over from the original definition:
//   - Sirocco Operative -> p1 myHand-7 (champion Spirit of Wind is not an Assassin; no other Automaton ally)
//   - play Sirocco Operative
gaScenario('sirocco-operative-enter-without-class-bonus-or-other-automaton')
    ->deck($tristanDeck)
    ->seed('myHand', 'Sirocco Operative')
    ->castSpell('Sirocco Operative', pay: 3)
    ->expectProperty('p1Field-1', 'CardID', 't7ru41pzgg', 'Sirocco Operative entered the field')
    ->expectZoneCount('myMemory', 3, 1, 'Memory holds only the 3 reserve payments: nothing was drawn into memory without the Class Bonus')
    ->expectProperty('p1Field-0', 'Counters', '[]', 'No other Automaton ally, so no preparation counter')
    ->expectDqEmpty()
    ->register();

// --- Shimmercloak Assassin and Gloamspire Headhunter: Stealth (this unit can't be targeted by attacks) ---
// Shimmercloak Assassin (wklzjmwuir, WIND ALLY) and Gloamspire Headhunter (r278evrcdz, UMBRA ALLY)
// both print "Stealth (This unit can't be targeted by attacks unless permitted by true sight.)",
// handled by the generic HasKeyword_Stealth() path of HasStealth(). Both are seeded awake on
// player 1's field next to a vanilla Dungeon Guide (stealth is a static keyword, so seeding is
// faithful; their enter/on-hit abilities are covered elsewhere); player 2 declares a real attack
// with a Dungeon Guide. The target prompt only offers the champion and the vanilla Dungeon Guide:
// choosing either stealthed ally is rejected by the engine (expected rejection), and the legal
// Dungeon Guide target takes the hit.
// Notes carried over from the original definition:
//   - Shimmercloak Assassin (Stealth) -> p1 field-1
//   - Gloamspire Headhunter (Stealth) -> p1 field-2
//   - Dungeon Guide (no stealth; legal target) -> p1 field-3
//   - Dungeon Guide (attacker) -> p2 field-1
//   - end player 1 turn 1
//   - player 2 declares an attack with its Dungeon Guide
//   - the legal target: the unstealthed Dungeon Guide
//   - player 1 declines to retaliate
gaScenario('shimmercloak-assassin-and-gloamspire-headhunter-stealth-untargetable-by-attacks')
    ->deck($tristanDeck)
    ->seed('myField', 'Shimmercloak Assassin')
    ->seed('myField', 'Gloamspire Headhunter')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-1', 'Cannot attack a stealthed, awake Shimmercloak Assassin without True Sight')
    ->rejects(2, 'theirField-2', 'Cannot attack a stealthed, awake Gloamspire Headhunter without True Sight')
    ->answer(2, 'theirField-3')->pass(1)
    ->tested('Shimmercloak Assassin', 'Gloamspire Headhunter')
    ->expectDamage('p1Field-3', 1, 'The only legal ally target (the unstealthed Dungeon Guide) took the attacker\'s 1 damage')
    ->expectDamage('p1Field-1', 0, 'Shimmercloak Assassin was never a legal target')
    ->expectDamage('p1Field-2', 0, 'Gloamspire Headhunter was never a legal target')
    ->expectDqEmpty()
    ->register();

// Notes carried over from the original definition:
//   - pay Prepare X
//   - X = 3 (all counters)
//   - attack player 1's champion (unblockable: no interceptor prompt)
gaScenario('shadowstrike-prepare-x-3-power-plus-3-unblockable')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 3]], player: 2)
    ->seed('myHand', 'Shadowstrike', player: 2)
    ->seed('myField', 'Swift Recruit')
    ->endTurn()
    ->play('Shadowstrike', 2)->pay(2, 2)->answer(2, 'YES')->answer(2, '3')->answer(2, 'theirField-0')
    ->register();

// Notes carried over from the original definition:
//   - pay Prepare X
//   - X = 1 (2 counters stay)
//   - attack player 1's champion
gaScenario('shadowstrike-prepare-x-1-power-plus-1-unblockable')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 3]], player: 2)
    ->seed('myHand', 'Shadowstrike', player: 2)
    ->seed('myField', 'Swift Recruit')
    ->endTurn()
    ->play('Shadowstrike', 2)->pay(2, 2)->answer(2, 'YES')->answer(2, '1')->answer(2, 'theirField-0')
    ->register();

// Notes carried over from the original definition:
//   - pay Prepare X
//   - X = 0 is illegal: the X prompt is asked again
//   - X = 9 > 3 counters is illegal: asked again
//   - attack player 1's champion
gaScenario('shadowstrike-prepare-x-zero-refused-then-x-2')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 3]], player: 2)
    ->seed('myHand', 'Shadowstrike', player: 2)
    ->seed('myField', 'Swift Recruit')
    ->endTurn()
    ->play('Shadowstrike', 2)->pay(2, 2)->answer(2, 'YES')->answer(2, '0')->answer(2, '9')->answer(2, '2')
    ->answer(2, 'theirField-0')
    ->register();

// Notes carried over from the original definition:
//   - decline Prepare X
//   - attack player 1's champion (not prepared: blockable)
//   - player 1 is offered Swift Recruit as an interceptor (attack is blockable) and declines
gaScenario('shadowstrike-prepare-x-declined-base-power-blockable')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 3]], player: 2)
    ->seed('myHand', 'Shadowstrike', player: 2)
    ->seed('myField', 'Swift Recruit')
    ->endTurn()
    ->play('Shadowstrike', 2)->pay(2, 2)->answer(2, 'NO')->answer(2, 'theirField-0')->pass(1)
    ->register();

// Notes carried over from the original definition:
//   - attack directly: no Prepare prompt without a counter
//   - player 1 is offered Swift Recruit as an interceptor (not prepared: blockable) and declines
gaScenario('shadowstrike-no-preparation-counters-no-prepare-prompt')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 0]], player: 2)
    ->seed('myHand', 'Shadowstrike', player: 2)
    ->seed('myField', 'Swift Recruit')
    ->endTurn()
    ->play('Shadowstrike', 2)->pay(2, 2)->answer(2, 'theirField-0')->pass(1)
    ->register();

// --- Surveil the Winds: [Class Bonus] Fast Activation (playable on the opponent's turn); draw a card, then put a preparation counter on your champion ---
// Surveil the Winds (xt6uaz6a7g, WIND slow action, reserve 2): "[Class Bonus] Fast Activation (You
// may activate this card at fast speed.) Draw a card, then put a preparation counter on your
// champion." With an ASSASSIN champion (Tristan, Hired Blade) the card is listed in
// $cbFastActivationCards (Custom/OpportunityLogic.php), so when player 2 plays a Dungeon Guide on
// its own turn player 1 is offered Surveil in the priority window and plays it for real (2 reserve
// payments): Draw(1) then AddPrepCounter(). It resolves before the Dungeon Guide's own On Enter
// prompt, which player 2 then declines.
// Notes carried over from the original definition:
//   - Tristan, Hired Blade (ASSASSIN) -> Class Bonus active
//   - Surveil the Winds -> p1 myHand-7
//   - end player 1 turn
//   - p1 passes a window
//   - p1 passes a window
//   - player 2 plays Dungeon Guide (opens a window for player 1)
//   - reserve 1/3
//   - reserve 2/3
//   - reserve 3/3
//   - player 1 activates Surveil the Winds at fast speed on the opponent's turn
//   - player 2 declines Dungeon Guide's On Enter
gaScenario('surveil-the-winds-class-bonus-fast-activation-draw-prepare')
    ->deck($tristanDeck)
    ->champion(cardName: 'Tristan, Hired Blade', lineage: ['Tristan, Underhanded', 'Spirit of Wind'])
    ->seed('myHand', 'Surveil the Winds')
    ->endTurn()->pass(1)->pass(1)
    ->playSlot(0, 2)->pay(3, 2)->target('myHand-7')->pay(2)->answer(2, 'NO')
    ->tested('Surveil the Winds')
    ->register();

// --- Surveil the Winds: without the Class Bonus it is a slow action and cannot be activated on the opponent's turn ---
// Surveil the Winds (xt6uaz6a7g): Fast Activation is a [Class Bonus] clause. With the starting
// champion Spirit of Wind (no Assassin) Surveil is a plain slow action: player 1 is never offered
// a priority window for it on player 2's turn, and trying to play it from hand during player 2's
// main phase does nothing (the card stays in hand, no draw, no preparation counter).
// Notes carried over from the original definition:
//   - Surveil the Winds -> p1 myHand-7 (champion Spirit of Wind is not an Assassin)
//   - end player 1 turn (no fast cards, so no priority windows)
//   - player 2 plays Dungeon Guide
//   - reserve 1/3
//   - reserve 2/3
//   - reserve 3/3
//   - player 2 declines Dungeon Guide's On Enter
//   - player 1 tries to activate the slow Surveil during the opponent's turn -- nothing happens
gaScenario('surveil-the-winds-slow-without-class-bonus')
    ->deck($tristanDeck)
    ->seed('myHand', 'Surveil the Winds')
    ->endTurn()
    ->playSlot(0, 2)->pay(3, 2)->answer(2, 'NO')
    ->play('Surveil the Winds')
    ->register();

// --- Banishment activation: a click blocked by the first-turn attack lock does not cost 2 damage or the permission ---
// Seething Intercession (5Xfg69S1XX): "you may activate [the banished cards]. As an additional cost to
// activate each of those cards, deal 2 unpreventable damage to your champion." ENGINE BUG (fixed): the
// "myBanish" branch dealt the 2 damage and moved the card into hand BEFORE the activation's gates ran;
// an attack card clicked on the opening player's turn 1 ("Attack cards cannot be activated by the
// opening player on turn 1") was refused AFTER the champion had already taken 2 damage and the
// permission tag was stripped. A Striking Tides tagged _seethingIntercession is seeded in banishment
// (the tag is seeded, not produced by playing Seething Intercession -- caveat), and player 1 clicks it
// on turn 1: no damage, card still banished with its tag, hand unchanged.
// Notes carried over from the original definition:
//   - Striking Tides banished by Seething Intercession -> p1 banish-0
//   - 1: click the banished attack card on turn 1 (the opening player may not activate attacks): accepted as a no-op, refusal asserted on state
gaScenario('seething-intercession-banished-attack-first-turn-click-refused-no-self-damage')
    ->deck($mordredDeck)
    ->seed('myBanish', 'Striking Tides', properties: ['TurnEffects' => ['_seethingIntercession']])
    ->action(1, 10002, 'myBanish-0!FSM!')
    ->tested('Seething Intercession')
    ->register();

// --- Banishment activation (positive pin): the same banished attack IS activatable when legal, and the 2 damage is paid ---
// Seething Intercession (5Xfg69S1XX): the same seeded Striking Tides, but on player 2's turn 2 (no
// first-turn attack lock): the click starts the activation -- 2 unpreventable damage to player 2's
// champion, 3 reserve payments, an attack on player 1's champion for 4. Pins that the validate-first
// refusal does not over-refuse a legal banish activation.
// Notes carried over from the original definition:
//   - Striking Tides banished by Seething Intercession -> p2 banish-0
//   - 1: end player 1 turn 1
//   - 2: player 2 activates the banished Striking Tides on turn 2
//   - 6: attack player 1's champion
gaScenario('seething-intercession-banished-attack-activatable-when-legal-costs-two-damage')
    ->deck($mordredDeck)
    ->seed('myBanish', 'Striking Tides', player: 2, properties: ['TurnEffects' => ['_seethingIntercession']])
    ->endTurn()
    ->action(2, 10002, 'myBanish-0!FSM!')->pay(3, 2)->answer(2, 'theirField-0')
    ->tested('Seething Intercession')
    ->register();

// Undo snapshot: a REFUSED banishment click must not overwrite the undo snapshot. A Striking Tides tagged
// _seethingIntercession sits in banishment; Dungeon Guide is played for real (the hand click saves the undo snapshot), then
// the banished attack is clicked on turn 1 (first-turn attack lock: refused). The snapshot must be unchanged.
// Notes carried over from the original definition:
//   - Dungeon Guide -> p1 myHand-0
//   - Striking Tides banished by Seething Intercession -> p1 banish-0 (tag seeded)
//   - 1: play the Dungeon Guide (saves the undo snapshot)
//   - 5: decline the optional On Enter
//   - 6: click the banished attack on turn 1 (opening-player attack lock): accepted as a no-op, refusal asserted on state
//   - 7: click it again (the engine re-reads the saved state between actions, so the undo snapshot is observable here)
gaScenario('banish-activation-refused-click-keeps-undo-snapshot')
    ->deck($mordredDeck)
    ->emptyZone('myHand', destZone: 'myGraveyard')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myHand', 'Fluffy Shopkeep')
    ->seed('myBanish', 'Striking Tides', properties: ['TurnEffects' => ['_seethingIntercession']])
    ->playSlot(0)->pay(3)->answer(1, 'NO')
    ->action(1, 10002, 'myBanish-0!FSM!')
    ->action(1, 10002, 'myBanish-0!FSM!')
    ->tested('Seething Intercession')
    ->register();

// --- Seiryuu, Azure Dragon: printed Spellshroud -- an opposing spell cannot target it ---
// Seiryuu, Azure Dragon (fcfxhkqda6, GKKYGv6t3R, ALLY): "Spellshroud, Taunt" -- the Spellshroud is the card's
// own, UNCONDITIONAL keyword (rules: .claude/GrandArchiveSim/refs/comprehensive-rules.md, Keywords >
// Spellshroud 1: "This can't be targeted by spells"). It was never implemented: HasSpellshroud()
// (Custom/GameLogic.php) recognised printed Spellshroud only through a hard-coded card-ID list and
// HasKeyword_Spellshroud(), which is never generated (Data/ProcessKeywordsGA.php has no Spellshroud keyword,
// so function_exists is false); these IDs were in neither, so FilterSpellshroudTargets() kept the card in the
// target list. Fixed: they are now in HasSpellshroud()'s printed-Spellshroud table (after the HasNoAbilities /
// NO_SPELLSHROUD gates).
// Player 2 controls the holders plus a Dungeon Guide; player 1 plays Liturgy of Corruption ("Put a buff
// counter on target ally ...", a NORM Spell): the target prompt offers ONLY the Dungeon Guide, answering with
// each holder is refused (expectFailure) and the legal answer then resolves.
gaSpellshroudCase('seiryuu-azure-dragon-printed-spellshroud-spell-cannot-target-it', ['#fcfxhkqda6', '#GKKYGv6t3R'], 'Liturgy of Corruption')
    ->register();

// --- Suzaku, Vermillion Phoenix: printed Spellshroud -- an opposing spell cannot target it ---
// Suzaku, Vermillion Phoenix (42mdt7ge5d, h57jayq7l2, ALLY): "Spellshroud" -- the Spellshroud is the card's
// own, UNCONDITIONAL keyword (rules: .claude/GrandArchiveSim/refs/comprehensive-rules.md, Keywords >
// Spellshroud 1: "This can't be targeted by spells"). It was never implemented: HasSpellshroud()
// (Custom/GameLogic.php) recognised printed Spellshroud only through a hard-coded card-ID list and
// HasKeyword_Spellshroud(), which is never generated (Data/ProcessKeywordsGA.php has no Spellshroud keyword,
// so function_exists is false); these IDs were in neither, so FilterSpellshroudTargets() kept the card in the
// target list. Fixed: they are now in HasSpellshroud()'s printed-Spellshroud table (after the HasNoAbilities /
// NO_SPELLSHROUD gates).
// Player 2 controls the holders plus a Dungeon Guide; player 1 plays Liturgy of Corruption ("Put a buff
// counter on target ally ...", a NORM Spell): the target prompt offers ONLY the Dungeon Guide, answering with
// each holder is refused (expectFailure) and the legal answer then resolves.
gaSpellshroudCase('suzaku-vermillion-phoenix-printed-spellshroud-spell-cannot-target-it', ['Suzaku, Vermillion Phoenix', '#h57jayq7l2'], 'Liturgy of Corruption')
    ->register();

// --- Supernova Divination: printed Spellshroud -- an opposing spell cannot target it ---
// Supernova Divination (qhBecpDUO9, UNIQUE,PHANTASIA): "Spellshroud" -- the Spellshroud is the card's own,
// UNCONDITIONAL keyword (rules: .claude/GrandArchiveSim/refs/comprehensive-rules.md, Keywords > Spellshroud 1:
// "This can't be targeted by spells"). It was never implemented: HasSpellshroud() (Custom/GameLogic.php)
// recognised printed Spellshroud only through a hard-coded card-ID list and HasKeyword_Spellshroud(), which is
// never generated (Data/ProcessKeywordsGA.php has no Spellshroud keyword, so function_exists is false);
// qhBecpDUO9 was in neither, so FilterSpellshroudTargets() kept the card in the target list. Fixed: it is now
// in HasSpellshroud()'s printed-Spellshroud table (after the HasNoAbilities / NO_SPELLSHROUD gates).
// Player 2 controls the holder plus a Ominous Shadow; player 1 plays Disenchant ("Destroy target phantasia", a
// Spell): the target prompt offers ONLY the Ominous Shadow, answering with the holder is refused
// (expectFailure) and the legal answer then resolves.
gaSpellshroudCase('supernova-divination-printed-spellshroud-spell-cannot-target-it', ['Supernova Divination'], 'Disenchant')
    ->register();

// --- Seeker's Rifle: [Class Bonus] Spellshroud -- active (Class Bonus met: player 2's champion is a Ranger
// (Diana, Keen Huntress)) ---
// Seeker's Rifle (3gygojwk0p, REGALIA,WEAPON): "[Class Bonus] Spellshroud" (a conditional printed Spellshroud:
// the keyword only applies while the controller's champion class matches). HasSpellshroud() had no entry for
// it (hard-coded ID list only), so a spell could always target it. Fixed: HasSpellshroud() returns true for it
// when IsClassBonusActive() holds for its controller.
// Condition: Class Bonus met: player 2's champion is a Ranger (Diana, Keen Huntress). Player 2 controls the
// holder plus a Mirrordepth's Blade; player 1 plays Fracturize ("Target item or weapon becomes a Cleric
// Fractal phantasia ...", a Spell): the target prompt offers ONLY the Mirrordepth's Blade, the holder answer
// is refused (expectFailure) and the legal answer resolves.
gaSpellshroudCase('seekers-rifle-class-bonus-spellshroud-ranger-champion-spell-cannot-target-it', ['Seeker\'s Rifle'], 'Fracturize', holderCounters: ['{"durability":2}'], oppChampion: 'Diana, Keen Huntress')
    ->register();

// --- Seeker's Rifle: [Class Bonus] Spellshroud -- negative control (Class Bonus NOT met: player 2's champion
// is an Assassin (Tristan, Underhanded), not a Ranger) ---
// Seeker's Rifle (3gygojwk0p): "[Class Bonus] Spellshroud". Negative control for the Class Bonus gate: Class
// Bonus NOT met: player 2's champion is an Assassin (Tristan, Underhanded), not a Ranger, so the holder has NO
// Spellshroud and the opposing Fracturize offers it: both the holder and the Mirrordepth's Blade are legal
// targets and answering with the holder resolves on it.
// Notes carried over from the original definition:
//   - player 2 champion -> Tristan, Underhanded (level 1 ASSASSIN)
//   - Seeker's Rifle -> p2 field-1
//   - Mirrordepth's Blade -> p2 field-2
//   - Fracturize -> p1 myHand-7
//   - player 1 plays Fracturize
//   - the holder is a legal target while the Class Bonus is off
gaScenario('seekers-rifle-class-bonus-spellshroud-non-ranger-champion-spell-can-target-it')
    ->deck($mordredDeck)
    ->champion(cardName: 'Tristan, Underhanded', player: 2)
    ->seed('myField', 'Seeker\'s Rifle', player: 2)
    ->seed('myField', 'Mirrordepth\'s Blade', player: 2)
    ->seed('myHand', 'Fracturize')
    ->castSpell('Fracturize', pay: 2, target: 'theirField-1')
    ->tested('Seeker\'s Rifle')
    ->expectProperty('p2Field-1', 'Counters', '{"durability":2,"_overrides":{"type":"PHANTASIA","subtypes":"CLERIC,FRACTAL","classes":"CLERIC","NO_ABILITIES":true,"granted_keywords":["Reservable"],"FRACTURIZED":true}}', 'NEGATIVE CONTROL: Seeker\'s Rifle (3gygojwk0p) has no Spellshroud while the Class Bonus is off, so it was offered and became a Cleric Fractal phantasia (Fracturize override)')
    ->expectProperty('p2Field-2', 'Counters', '{"durability":2}', 'Mirrordepth\'s Blade was not the chosen target and is untouched')
    ->expectProperty('p1Graveyard-0', 'CardID', 'cpvn96659y', 'Fracturize resolved into player 1\'s graveyard')
    ->expectDqEmpty()
    ->register();

// --- Snow Fairy: On Enter, rest target ally you don't control (it is then frozen) ---
// Snow Fairy (4s0c9XgLg7, WATER MAGE/FAIRY ALLY, reserve 3, Stealth): "On Enter: Rest target ally
// you don't control. That ally doesn't wake up during its controller's wake up phase as long as you
// control CARDNAME." Player 2's Dungeon Guide stands awake on its field; Snow Fairy is played for
// real (3 reserve payments) and the only legal target (the opposing Dungeon Guide) is chosen: it
// becomes rested and receives the FROZEN_BY_SNOW_FAIRY turn effect that the wake-up phase honours.
// Notes carried over from the original definition:
//   - Dungeon Guide (awake) -> p2 field-1
//   - Snow Fairy -> p1 myHand-7
//   - play Snow Fairy
//   - On Enter: rest the opposing Dungeon Guide
gaScenario('snow-fairy-enter-rests-target-ally')
    ->deck($mordredDeck)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Snow Fairy')
    ->castSpell('Snow Fairy', pay: 3, target: 'theirField-1')
    ->expectProperty('p1Field-1', 'CardID', '4s0c9XgLg7', 'Snow Fairy entered the field (played for real, so its On Enter fired)')
    ->expectProperty('p2Field-1', 'Status', '1', 'The targeted opposing ally is now rested')
    ->expectProperty('p2Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","FROZEN_BY_SNOW_FAIRY"]', 'The target carries the freeze marker the wake up phase honours')
    ->expectProperty('p2Field-0', 'Status', '2', 'The opposing champion (not an ally) is untouched')
    ->expectDqEmpty()
    ->register();

// --- Snow Fairy: the rested ally does NOT wake up in its controller's wake up phase (a normally rested ally does) ---
// Snow Fairy (4s0c9XgLg7): "That ally doesn't wake up during its controller's wake up phase as long
// as you control CARDNAME." Player 2 controls two rested-looking Dungeon Guides after Snow Fairy's On
// Enter: one was rested by Snow Fairy, the other was simply seeded rested (control). Both players
// pass through a full round; in player 2's SECOND turn wake up phase the control ally wakes up
// while the Snow Fairy target stays rested and keeps its freeze.
// Notes carried over from the original definition:
//   - Dungeon Guide -> p2 field-1 (will be rested by Snow Fairy)
//   - control: a Dungeon Guide that is rested without Snow Fairy -> p2 field-2
//   - Snow Fairy -> p1 myHand-7
//   - play Snow Fairy
//   - On Enter: rest the first opposing Dungeon Guide
//   - end player 1 turn 1
//   - end player 2 turn 2 (the wake up phase is skipped in round 1)
//   - player 1 declines the materialize offer of its second turn
//   - end player 1's second turn
//   - player 2 declines the materialize offer of its second turn (after ITS wake up phase)
gaScenario('snow-fairy-frozen-ally-skips-wake-up-phase')
    ->deck($mordredDeck)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 1])
    ->seed('myHand', 'Snow Fairy')
    ->castSpell('Snow Fairy', pay: 3, target: 'theirField-1')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->endTurn()->pass(2)
    ->expectProperty('p1Field-1', 'CardID', '4s0c9XgLg7', 'Snow Fairy is still on the field')
    ->expectProperty('p2Field-1', 'Status', '1', 'The Snow Fairy target is STILL rested after its controller\'s wake up phase')
    ->expectProperty('p2Field-1', 'TurnEffects', '["FROZEN_BY_SNOW_FAIRY"]', 'The freeze persists while Snow Fairy is controlled')
    ->expectProperty('p2Field-2', 'Status', '2', 'Control: the other rested Dungeon Guide DID wake up in the same wake up phase')
    ->expectDqEmpty()
    ->register();

// --- Snow Fairy: nothing happens when the opponent controls no ally ---
// Snow Fairy (4s0c9XgLg7): "Rest target ally you don't control." Player 2 controls only its champion,
// so there is no legal target: no prompt appears and Snow Fairy simply enters.
// Notes carried over from the original definition:
//   - Snow Fairy -> p1 myHand-7
//   - play Snow Fairy
gaScenario('snow-fairy-enter-no-opposing-ally')
    ->deck($mordredDeck)
    ->seed('myHand', 'Snow Fairy')
    ->castSpell('Snow Fairy', pay: 3)
    ->expectProperty('p1Field-1', 'CardID', '4s0c9XgLg7', 'Snow Fairy entered the field')
    ->expectZoneCount('myField', 1, 2, 'The opponent controls only its champion')
    ->expectProperty('p2Field-0', 'Status', '2', 'The opposing champion was not rested (champions are not ally targets)')
    ->expectDqEmpty()
    ->register();

// --- Snow Fairy: once Snow Fairy has left the field, the ally wakes up normally ---
// Snow Fairy (4s0c9XgLg7): the freeze lasts only "as long as you control CARDNAME". Player 2 has two
// Dungeon Guides: A (plain) is rested by Snow Fairy's On Enter, B carries two buff counters (3
// power). On player 1's next turn Snow Fairy attacks the awake B and dies to its retaliation (3
// damage vs 2 life). In player 2's following wake up phase A is no longer frozen: the engine drops
// the FROZEN_BY_SNOW_FAIRY effect and A wakes up normally.
// Notes carried over from the original definition:
//   - Dungeon Guide A (will be rested by Snow Fairy) -> p2 field-1
//   - Dungeon Guide B with 2 buff counters (3 power), awake -> p2 field-2
//   - Snow Fairy -> p1 myHand-7
//   - play Snow Fairy
//   - On Enter: rest Dungeon Guide A
//   - end player 1 turn 1
//   - end player 2 turn 2
//   - player 1 declines the materialize offer of its second turn
//   - Snow Fairy attacks
//   - targeting the awake Dungeon Guide B
//   - player 2 retaliates with Dungeon Guide B: Snow Fairy dies
//   - end player 1's second turn
//   - player 2 declines the materialize offer of its second turn (after ITS wake up phase)
gaScenario('snow-fairy-ally-wakes-up-after-snow-fairy-leaves')
    ->deck($mordredDeck)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Counters' => ['buff' => 2]])
    ->seed('myHand', 'Snow Fairy')
    ->castSpell('Snow Fairy', pay: 3, target: 'theirField-1')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->action(1, 10002, 'myField-1!FSM!')->target('theirField-2')->answer(2, 'myField-2')
    ->endTurn()->pass(2)
    ->expectProperty('p1Graveyard-0', 'CardID', '4s0c9XgLg7', 'Snow Fairy died to the retaliation and is in the graveyard')
    ->expectZoneCount('myField', 1, 1, 'Player 1 no longer controls Snow Fairy')
    ->expectProperty('p2Field-1', 'Status', '2', 'The formerly frozen Dungeon Guide woke up in its controller\'s wake up phase')
    ->expectProperty('p2Field-1', 'TurnEffects', '[]', 'The freeze marker was removed')
    ->expectDqEmpty()
    ->register();

// --- Snow Fairy: Stealth -- an attack cannot target it ---
// Snow Fairy (4s0c9XgLg7): "Stealth (This unit can't be targeted by attacks unless permitted by true
// sight.)" Player 2 attacks with a Dungeon Guide; the attack-target prompt lists only player 1's
// champion and answering with Snow Fairy is rejected, so the attack is aimed at the champion.
// Notes carried over from the original definition:
//   - Snow Fairy (Stealth) -> p1 field-1
//   - Dungeon Guide (the attacker) -> p2 field-1
//   - end player 1 turn 1
//   - player 2 attacks with Dungeon Guide
//   - the only legal target: player 1's champion
gaScenario('snow-fairy-stealth-cannot-be-attacked')
    ->deck($mordredDeck)
    ->seed('myField', 'Snow Fairy')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-1', 'A Stealth ally (Snow Fairy) is rejected as an attack target')
    ->answer(2, 'theirField-0')
    ->tested('Snow Fairy')
    ->expectDamage('p1Field-1', 0, 'Snow Fairy took no damage: it was never a legal attack target')
    ->expectDamage('p1Field-0', 1, 'The attack was aimed at the only legal target, the champion (1 damage)')
    ->expectProperty('p1Field-1', 'Status', '2', 'Snow Fairy is still awake and unharmed')
    ->expectDqEmpty()
    ->register();

// --- Striking Tides: [Mordred Bonus] On Hit, banish a floating-memory card from the graveyard to wake up your champion ---
// Striking Tides (qrxQGA1pc6, WATER WARRIOR/SWORD attack, reserve 3, power 4): "[Mordred Bonus] On
// Hit: You may banish a card with floating memory from your graveyard. If you do, wake up your
// champion." The champion is Mordred, Flawless Blade (level 2: attack cards in the graveyard have
// floating memory); the graveyard holds a Dungeon Guide (no floating memory) and a Pierce the
// Heavens (an attack card -> floating memory). Striking Tides is played for real on turn 3, hits
// player 2's champion for 4, the YES/NO is answered YES, the non-floating Dungeon Guide is rejected
// as a choice and the Pierce the Heavens is banished: the champion (rested by attacking) wakes up.
// Notes carried over from the original definition:
//   - Mordred, Flawless Blade: Mordred Bonus; attack cards in the graveyard have floating memory
//   - Dungeon Guide (no floating memory) -> p1 graveyard-0
//   - Pierce the Heavens (attack card -> floating memory) -> p1 graveyard-1
//   - Striking Tides -> p1 myHand-7
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the materialize offer
//   - activate Striking Tides
//   - attack player 2's champion
//   - On Hit: banish a floating-memory card to wake up the champion
//   - banish the Pierce the Heavens (floating memory)
gaScenario('striking-tides-on-hit-banish-floating-memory-wakes-champion')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Flawless Blade', lineage: ['Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myGraveyard', 'Dungeon Guide')
    ->seed('myGraveyard', 'Pierce the Heavens')
    ->seed('myHand', 'Striking Tides')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->play('Striking Tides')->pay(3)->target('theirField-0')->answer(1, 'YES')
    ->rejects(1, 'myGraveyard-0', 'A graveyard card WITHOUT floating memory (Dungeon Guide) is rejected as the banish choice')
    ->target('myGraveyard-1')
    ->expectDamage('p2Field-0', 4, 'Striking Tides hit for its printed 4')
    ->expectProperty('p1Banish-0', 'CardID', 'yguf3aw2ct', 'The floating-memory attack card (Pierce the Heavens) was banished from the graveyard')
    ->expectProperty('p1Graveyard-0', 'CardID', 'em6eEh9q8y', 'The Dungeon Guide (no floating memory) was NOT touched')
    ->expectZoneCount('myGraveyard', 2, 1, 'Graveyard = Dungeon Guide + the resolved Striking Tides')
    ->expectProperty('p1Field-0', 'Status', '2', 'The champion (rested by attacking) was woken up')
    ->expectDqEmpty()
    ->register();

// --- Striking Tides: the On Hit banish is optional (declined) -- the champion stays rested ---
// Striking Tides (qrxQGA1pc6): "You MAY banish a card with floating memory ... If you do, wake up
// your champion." Same setup as striking-tides-on-hit-banish-floating-memory-wakes-champion; the
// YES/NO is answered NO: nothing is banished and the champion stays rested after attacking.
// Notes carried over from the original definition:
//   - Mordred, Flawless Blade
//   - Pierce the Heavens (attack card -> floating memory) -> p1 graveyard-0
//   - Striking Tides -> p1 myHand-7
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the materialize offer
//   - activate Striking Tides
//   - attack player 2's champion
//   - decline the On Hit banish
gaScenario('striking-tides-on-hit-decline-banish-champion-stays-rested')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Flawless Blade', lineage: ['Mordred, Burnished Avenger', 'Spirit of Water'])
    ->seed('myGraveyard', 'Pierce the Heavens')
    ->seed('myHand', 'Striking Tides')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->play('Striking Tides')->pay(3)->target('theirField-0')->answer(1, 'NO')
    ->expectDamage('p2Field-0', 4, 'Striking Tides hit for its printed 4')
    ->expectProperty('p1Graveyard-0', 'CardID', 'yguf3aw2ct', 'The floating-memory card stays in the graveyard')
    ->expectZoneCount('myBanish', 0, 1, 'Nothing was banished')
    ->expectProperty('p1Field-0', 'Status', '1', 'The champion stays rested')
    ->expectDqEmpty()
    ->register();

// --- Striking Tides: no On Hit prompt when the graveyard holds no floating-memory card ---
// Striking Tides (qrxQGA1pc6): the On Hit only offers cards with floating memory. The champion is
// Mordred, Burnished Avenger (level 1: attack cards do NOT float) and the graveyard holds only a
// Dungeon Guide: no prompt appears, nothing is banished and the champion stays rested.
// Notes carried over from the original definition:
//   - Mordred, Burnished Avenger (Mordred Bonus, no floating grant)
//   - Pierce the Heavens: an attack card but WITHOUT floating memory at level 1 -> p1 graveyard-0
//   - Striking Tides -> p1 myHand-7
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the materialize offer
//   - activate Striking Tides
//   - attack player 2's champion
gaScenario('striking-tides-on-hit-no-floating-memory-card-no-prompt')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Burnished Avenger', lineage: ['Spirit of Water'])
    ->seed('myGraveyard', 'Pierce the Heavens')
    ->seed('myHand', 'Striking Tides')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->castSpell('Striking Tides', pay: 3, target: 'theirField-0')
    ->expectDamage('p2Field-0', 4, 'Striking Tides hit for its printed 4')
    ->expectProperty('p1Graveyard-0', 'CardID', 'yguf3aw2ct', 'The attack card without floating memory (level 1 champion) stays in the graveyard')
    ->expectZoneCount('myBanish', 0, 1, 'Nothing was banished')
    ->expectProperty('p1Field-0', 'Status', '1', 'The champion stays rested: no prompt, no wake up')
    ->expectDqEmpty()
    ->register();

// --- Striking Tides: the On Hit prompt needs the Mordred Bonus ---
// Striking Tides (qrxQGA1pc6): the banish/wake clause is a "[Mordred Bonus]". With a non-Mordred
// champion (the plain Spirit of Water) and a March On (an attack card with printed Floating
// Memory) in the graveyard, the attack hits for 4 but no prompt appears: the graveyard card stays
// and the champion stays rested.
// Notes carried over from the original definition:
//   - March On (attack card with printed Floating Memory) -> p1 graveyard-0
//   - Striking Tides -> p1 myHand-7
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the materialize offer
//   - activate Striking Tides
//   - attack player 2's champion
gaScenario('striking-tides-on-hit-requires-mordred-champion')
    ->deck($mordredDeck)
    ->seed('myGraveyard', 'March On')
    ->seed('myHand', 'Striking Tides')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->castSpell('Striking Tides', pay: 3, target: 'theirField-0')
    ->expectDamage('p2Field-0', 4, 'Striking Tides hit for its printed 4')
    ->expectProperty('p1Graveyard-0', 'CardID', 'zuAmYGyCcL', 'The floating-memory card (March On) stays in the graveyard')
    ->expectZoneCount('myBanish', 0, 1, 'Nothing was banished')
    ->expectProperty('p1Field-0', 'Status', '1', 'The champion stays rested')
    ->expectDqEmpty()
    ->register();

// --- Safeguard Paragon: Sacrifice it to prevent the next 4 non-combat damage to each unit you control this turn ---
// Safeguard Paragon (apu7wiw3cl, NORM CLERIC/WARRIOR ALLY, reserve 3): "Sacrifice Safeguard Paragon:
// Prevent the next 4 non-combat damage that would be dealt to each unit you control this turn."
// Player 1 controls a Safeguard Paragon and a Dungeon Guide. On its own turn player 2 plays Freezing
// Hail (2 damage to target unit) on that Dungeon Guide; in the priority window player 1 sacrifices
// the Paragon: every unit player 1 controls gets a PREVENT_NONCOMBAT_4 shield, the Paragon goes to
// the graveyard, and Freezing Hail's 2 damage is fully prevented (the shield keeps the remaining 2).
// Notes carried over from the original definition:
//   - Safeguard Paragon -> p1 field-1
//   - Dungeon Guide (the burn target) -> p1 field-2
//   - Freezing Hail (WATER, 2 damage to target unit) -> p2 myHand-7
//   - end player 1 turn 1
//   - p1 passes the end-of-main fast window (the Paragon's ability keeps windows open)
//   - p2 passes the same window (its fast Freezing Hail keeps windows open)
//   - p1 passes the beginning-of-opponent-turn window
//   - p2 passes the same window
//   - player 2 plays Freezing Hail
//   - player 1 responds in the effect-stack window: sacrifice Safeguard Paragon
//   - Freezing Hail resolves: target player 1's Dungeon Guide (now at field-1: the Paragon left)
gaScenario('safeguard-paragon-sacrifice-prevents-noncombat-damage')
    ->deck($mordredDeck)
    ->seed('myField', 'Safeguard Paragon')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Freezing Hail', player: 2)
    ->endTurn()->passBoth()->passBoth()
    ->play('Freezing Hail', 2)->pay(2, 2)->answer(1, 'myField-1@Activate-0@Sacrifice')->answer(2, 'theirField-1')
    ->tested('Safeguard Paragon')
    ->expectProperty('p1Graveyard-0', 'CardID', 'apu7wiw3cl', 'The Paragon was sacrificed as the cost')
    ->expectZoneCount('myField', 2, 1, 'Player 1 controls only its champion and the Dungeon Guide')
    ->expectProperty('p1Field-0', 'TurnEffects', '["PREVENT_NONCOMBAT_4"]', 'The champion got the full 4-damage non-combat prevention (unused)')
    ->expectProperty('p1Field-1', 'TurnEffects', '["PREVENT_NONCOMBAT_2","SKIP_WAKEUP"]', 'The Dungeon Guide\'s shield absorbed Freezing Hail\'s 2 damage: 4 - 2 = 2 left (SKIP_WAKEUP is Freezing Hail\'s own rider)')
    ->expectDamage('p1Field-1', 0, 'The Dungeon Guide took no damage from Freezing Hail')
    ->expectZoneCount('myMemory', 2, 2, 'Player 2 paid Freezing Hail\'s 2 reserve cost')
    ->expectDqEmpty()
    ->register();

// --- Safeguard Paragon: the prevention covers NON-combat damage only -- combat damage still goes through ---
// Safeguard Paragon (apu7wiw3cl): "Prevent the next 4 NON-COMBAT damage". Player 2's Dungeon Guide
// attacks player 1's champion on player 2's turn; in the attack's priority window player 1 sacrifices
// the Paragon (every unit gets the PREVENT_NONCOMBAT_4 shield) but the 1 combat damage is not
// prevented: the champion takes it and keeps the whole shield.
// Notes carried over from the original definition:
//   - Safeguard Paragon -> p1 field-1
//   - Dungeon Guide (the attacker, 1 power) -> p2 field-1
//   - end player 1 turn 1
//   - p1 passes the end-of-main fast window
//   - p1 passes the beginning-of-opponent-turn window
//   - player 2 attacks with Dungeon Guide
//   - targeting player 1's champion
//   - player 1 sacrifices the Paragon in the priority window
//   - p1 passes the window after the ability
//   - p1 passes the second window
gaScenario('safeguard-paragon-sacrifice-does-not-prevent-combat-damage')
    ->deck($mordredDeck)
    ->seed('myField', 'Safeguard Paragon')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->answer(1, 'myField-1@Activate-0@Sacrifice')
    ->pass(1)->pass(1)
    ->tested('Safeguard Paragon')
    ->expectProperty('p1Graveyard-0', 'CardID', 'apu7wiw3cl', 'The Paragon was sacrificed')
    ->expectDamage('p1Field-0', 1, 'The champion still took the 1 COMBAT damage')
    ->expectProperty('p1Field-0', 'TurnEffects', '["WAS_ATTACKED","PREVENT_NONCOMBAT_4","DAMAGED_SINCE_LAST_TURN"]', 'The non-combat shield is untouched (still 4)')
    ->expectDqEmpty()
    ->register();

// --- Spirit Blade: Dispersion: declining a further Sword still splits the damage for the swords already banished (dontSkipOnPass regression) ---
// Spirit Blade: Dispersion (7Rsid05Cf6): "Remove all durability counters from any amount of Sword weapons you control, then banish them. Choose any amount of units and deal damage equal to the amount of durability counters removed this way split among them." Stopping after some swords (declining the 'Choose another Sword weapon' prompt) must still split the damage for the counters already removed.
// Regression: SpiritBladeChooseSword's loop re-queue (Custom/CardDQHandlers.php) omitted dontSkipOnPass, so ExecuteStaticMethods() skipped the handler when the player answered PASS and its PASS branch (queue the MZSPLITASSIGN damage split for the accumulated durability) never ran: the first sword was banished and its 2 counters removed, but no damage was ever dealt or even offered. The first prompt (queued by SpiritBladeDispersion()) needs no flag: declining with no sword chosen has 0 counters to split either way.
// Two Swords (Clarent, Sword of Peace, m31WVJ9F04, durability 2 each) are seeded on the field; the first is chosen, the second declined with PASS, and the 2 damage is assigned to the opposing Dungeon Guide.
// Notes carried over from the original definition:
//   - champion patched so the WARRIOR spell is activatable (same as spirit-blade-dispersion-split-damage)
//   - Sword #1 (durability 2) -> myField-1
//   - Sword #2 (durability 2) -> myField-2
//   - Spirit Blade: Dispersion -> myHand-7
//   - opposing Dungeon Guide -> theirField-1
//   - pass the fast-action window so the spell resolves
//   - choose Sword #1 (2 durability counters removed, banished)
//   - decline choosing another Sword
//   - assign the 2 damage to the opposing Dungeon Guide
gaScenario('spirit-blade-dispersion-decline-second-sword-still-splits')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Crux Knight')
    ->seed('myField', 'Clarent, Sword of Peace', properties: ['Counters' => ['durability' => 2]])
    ->seed('myField', 'Clarent, Sword of Peace', properties: ['Counters' => ['durability' => 2]])
    ->seed('myHand', 'Spirit Blade: Dispersion')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->play('Spirit Blade: Dispersion')->pass(1)->target('myField-1')->pass(1)->answer(1, 'theirField-1:2')
    ->expectDamage('p2Field-1', 2, 'Declining the 2nd sword still deals the damage for the 1st sword\'s 2 removed durability counters (assigned to the opposing Dungeon Guide)')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":2}', 'The declined second Sword keeps its durability counters (it was not chosen)')
    ->expectZoneCount('myBanish', 1, 1, 'Only the chosen Sword was banished')
    ->expectDqEmpty()
    ->register();

// --- Sacred Engulfment: declining a further banish still empowers for the fire cards already banished (dontSkipOnPass regression) ---
// Sacred Engulfment (QvQhg1EOBR): "Banish any amount of fire element cards from your graveyard. Then empower 4+X, where X is the amount of fire element cards banished this way." Stopping early (declining the 'Banish a fire card from GY?' prompt) must still empower 4+X for the X cards already banished.
// Regression: SacredEngulfmentBanishLoop() queued its paired CUSTOM 'SacredEngulfmentProcess|N' decision without dontSkipOnPass, so ExecuteStaticMethods() skipped the handler when the player answered PASS and its PASS branch (Empower 4+X) never ran: fire cards were banished and the spell gave no Empower at all.
// Three Red Slimes (FIRE) are seeded in the graveyard; one is banished, the next offer declined with PASS -> empower 4+1 = 5. The champion gets a Subcards patch with a LUXEM champion so the Exalted element (which needs another advanced element in the lineage) is enabled for this EXALTED/FIRE card; the [Class Bonus] cost reduction is not reached (default champion has no class bonus), so the full reserve cost 3 is paid.
// Notes carried over from the original definition:
//   - LUXEM lineage -> enables EXALTED
//   - Red Slime (FIRE) #1 -> myGraveyard-0
//   - Red Slime (FIRE) #2
//   - Red Slime (FIRE) #3
//   - reserve 1/3
//   - reserve 2/3
//   - reserve 3/3
//   - banish the first fire card from the graveyard
//   - decline banishing another fire card
gaScenario('sacred-engulfment-decline-further-banish-still-empowers')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Zander, Blinding Steel'])
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myHand', 'Sacred Engulfment')
    ->play('Sacred Engulfment')->pay(3)->target('myGraveyard-0')->pass(1)
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","QvQhg1EOBR","EMPOWERED","EMPOWER_PLUS_5"]', 'Empower 4+X with X = 1 banished fire card = EMPOWER_PLUS_5, even though the second offer was declined')
    ->expectZoneCount('myBanish', 1, 1, 'Exactly one fire card was banished')
    ->expectDqEmpty()
    ->register();

// --- Striking Illuminance (2lukkhisu5): Prepare 2; On Attack: if prepared, reveal all cards in your memory (+1 POWER per luxem card revealed) ---
// Player 2's memory is seeded with two Bathe in Light (LUXEM) before play; the two reserve
// payments add two NORM cards, so the reveal covers 4 memory cards of which exactly 2 are luxem.
// Notes carried over from the original definition:
//   - Bathe in Light (LUXEM)
//   - Bathe in Light (LUXEM)
gaScenario('striking-illuminance-prepare-2-paid-reveals-memory-plus-2-power')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7', 'UAF6Nr7GUE'], 'Counters' => ['preparation' => 2]], player: 2)
    ->seed('myMemory', 'Bathe in Light', player: 2)
    ->seed('myMemory', 'Bathe in Light', player: 2)
    ->seed('myHand', 'Striking Illuminance', player: 2)
    ->endTurn()
    ->play('Striking Illuminance', 2)->pay(2, 2)->answer(2, 'YES')->answer(2, 'theirField-0')
    ->register();

gaScenario('striking-illuminance-prepare-2-declined-no-reveal-base-power')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7', 'UAF6Nr7GUE'], 'Counters' => ['preparation' => 2]], player: 2)
    ->seed('myMemory', 'Bathe in Light', player: 2)
    ->seed('myMemory', 'Bathe in Light', player: 2)
    ->seed('myHand', 'Striking Illuminance', player: 2)
    ->endTurn()
    ->play('Striking Illuminance', 2)->pay(2, 2)->answer(2, 'NO')->answer(2, 'theirField-0')
    ->register();

gaScenario('stillshard-strike-prepare-1-paid-recovers-1-plus-sheen')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7', 'tafqldAGRF'], 'Counters' => ['preparation' => 1], 'Damage' => 6], player: 2)
    ->patch('myField-0', ['Counters' => ['sheen' => 2]])
    ->seed('myHand', 'Stillshard Strike', player: 2)
    ->endTurn()
    ->play('Stillshard Strike', 2)->pay(2, 2)->answer(2, 'YES')->answer(2, 'theirField-0')
    ->register();

gaScenario('stillshard-strike-prepare-1-declined-no-recover')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7', 'tafqldAGRF'], 'Counters' => ['preparation' => 1], 'Damage' => 6], player: 2)
    ->patch('myField-0', ['Counters' => ['sheen' => 2]])
    ->seed('myHand', 'Stillshard Strike', player: 2)
    ->endTurn()
    ->play('Stillshard Strike', 2)->pay(2, 2)->answer(2, 'NO')->answer(2, 'theirField-0')
    ->register();

gaScenario('strike-from-the-mist-prepare-2-paid-cannot-be-intercepted')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7', 'tafqldAGRF'], 'Counters' => ['preparation' => 2]], player: 2)
    ->seed('myField', 'Swift Recruit')
    ->seed('myHand', 'Strike from the Mist', player: 2)
    ->endTurn()
    ->play('Strike from the Mist', 2)->pay(2, 2)->answer(2, 'YES')->answer(2, 'theirField-0')
    ->register();

// Notes carried over from the original definition:
//   - Swift Recruit is offered as an interceptor
gaScenario('strike-from-the-mist-prepare-2-declined-interceptor-offered')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7', 'tafqldAGRF'], 'Counters' => ['preparation' => 2]], player: 2)
    ->seed('myField', 'Swift Recruit')
    ->seed('myHand', 'Strike from the Mist', player: 2)
    ->endTurn()
    ->play('Strike from the Mist', 2)->pay(2, 2)->answer(2, 'NO')->answer(2, 'theirField-0')->pass(1)
    ->register();

gaScenario('soultrace-tessellation-prepare-1-paid-3-plus-2-sheen')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7', '2TCyILvBYa'], 'Counters' => ['preparation' => 1]], player: 2)
    ->seed('myBanish', 'Dungeon Guide', player: 2)
    ->seed('myBanish', 'Dungeon Guide', player: 2)
    ->seed('myBanish', 'Dungeon Guide', player: 2)
    ->seed('myBanish', 'Dungeon Guide', player: 2)
    ->seed('myBanish', 'Dungeon Guide', player: 2)
    ->seed('myBanish', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Soultrace Tessellation', player: 2)
    ->endTurn()
    ->play('Soultrace Tessellation', 2)->answer(2, 'myHand-7')->pay(2, 2)->pass(1)->answer(2, 'YES')
    ->answer(2, 'theirField-0')
    ->register();

gaScenario('soultrace-tessellation-prepare-1-declined-3-sheen')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7', '2TCyILvBYa'], 'Counters' => ['preparation' => 1]], player: 2)
    ->seed('myBanish', 'Dungeon Guide', player: 2)
    ->seed('myBanish', 'Dungeon Guide', player: 2)
    ->seed('myBanish', 'Dungeon Guide', player: 2)
    ->seed('myBanish', 'Dungeon Guide', player: 2)
    ->seed('myBanish', 'Dungeon Guide', player: 2)
    ->seed('myBanish', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Soultrace Tessellation', player: 2)
    ->endTurn()
    ->play('Soultrace Tessellation', 2)->answer(2, 'myHand-7')->pay(2, 2)->pass(1)->answer(2, 'NO')
    ->answer(2, 'theirField-0')
    ->register();

// Notes carried over from the original definition:
//   - move all 5 sheen onto the summoned Obelith
gaScenario('silvergale-monstrosity-call-prepare-2-paid-moves-sheen-to-obelith')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7', 'UAF6Nr7GUE'], 'Counters' => ['preparation' => 2]], player: 2)
    ->seed('myMastery', 'Fractured Memories', player: 2, properties: ['Counters' => ['sheen' => 5]])
    ->seed('myHand', 'Silvergale Monstrosity\'s Call', player: 2)
    ->endTurn()
    ->play('Silvergale Monstrosity\'s Call', 2)->pay(3, 2)->pass(1)->answer(2, 'YES')->answer(2, 'myField-1:5')
    ->register();

// "Any amount ... onto any amount of allies named Memorite Obelith": spread 4 of the 5 sheen over two Obeliths and leave 1 on Fractured Memories.
// Notes carried over from the original definition:
//   - an existing Memorite Obelith -> myField-1 (the summoned one becomes myField-2)
//   - 1 sheen onto the old Obelith, 3 onto the new one, 1 stays on Fractured Memories
gaScenario('silvergale-monstrosity-call-prepare-2-paid-splits-some-sheen-over-two-obeliths')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7', 'UAF6Nr7GUE'], 'Counters' => ['preparation' => 2]], player: 2)
    ->seed('myMastery', 'Fractured Memories', player: 2, properties: ['Counters' => ['sheen' => 5]])
    ->seed('myHand', 'Silvergale Monstrosity\'s Call', player: 2)
    ->seed('myField', 'Memorite Obelith', player: 2)
    ->endTurn()
    ->play('Silvergale Monstrosity\'s Call', 2)->pay(3, 2)->pass(1)->answer(2, 'YES')
    ->answer(2, 'myField-1:1,myField-2:3')
    ->covers(['prepare', 'sheen', 'split-assign'], 'Summon a Memorite Obelith token. If CARDNAME was prepared, move any amount of sheen counters from your Fractured Memories onto any amount of allies named Memorite Obelith you control.')
    ->register();

gaScenario('silvergale-monstrosity-call-prepare-2-declined-obelith-no-sheen')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7', 'UAF6Nr7GUE'], 'Counters' => ['preparation' => 2]], player: 2)
    ->seed('myMastery', 'Fractured Memories', player: 2, properties: ['Counters' => ['sheen' => 5]])
    ->seed('myHand', 'Silvergale Monstrosity\'s Call', player: 2)
    ->endTurn()
    ->play('Silvergale Monstrosity\'s Call', 2)->pay(3, 2)->pass(1)->answer(2, 'NO')
    ->register();

// top four: Dungeon Guide, Fluffy Shopkeep, Fairy Whispers (the three revealed), Charge the Soul (marker: must stay on top afterwards)
// Notes carried over from the original definition:
//   - Leeching Bolt aimed at my own champion (LV 3 damage)
gaScenario('spellshield-tera-prevent-three-reveals-three-into-material-preserved')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Kongming, Fel Eidolon')
    ->seed('myHand', 'Spellshield: Tera')
    ->seed('myHand', 'Leeching Bolt')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Charge the Soul'])
    ->castSpell('Spellshield: Tera', pay: 2, window: 'me')
    ->playSlot(5)->pay(2)->target('myField-0')
    ->register();

// PLAYER 2 seating (perspective check): the stack sits under player 2's turn-start draw (a filler Dungeon Guide); player 2 first
// declines the two opportunity windows player 1's pass opens
gaScenario('spellshield-tera-player-two-prevent-three-reveals-three-into-material-preserved')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Kongming, Fel Eidolon', player: 2)
    ->seed('myHand', 'Spellshield: Tera', player: 2)
    ->seed('myHand', 'Leeching Bolt', player: 2)
    ->deckTop(['Dungeon Guide', 'Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Charge the Soul'], player: 2)
    ->endTurn()->pass(2)->pass(2)
    ->play('Spellshield: Tera', 2)->pay(2, 2)->pass(2)
    ->playSlot(5, 2)->pay(2, 2)->answer(2, 'myField-0')
    ->register();

// top three, top to bottom: Fairy Whispers (not an ally), Fluffy Shopkeep (ally), Dungeon Guide (ally); then Charge the Soul (marker)
// Notes carried over from the original definition:
//   - reveal Dungeon Guide (3rd card) into hand
gaScenario('signaltech-x-ultra-rest-look-three-reveal-ally-rest-bottom-sacrifice')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'SignalTech X Ultra')
    ->deckTop(['Fairy Whispers', 'Fluffy Shopkeep', 'Dungeon Guide', 'Charge the Soul'])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myDeck-2')
    ->tested('SignalTech X Ultra')
    ->register();

gaScenario('signaltech-x-ultra-rest-look-three-decline-all-three-bottom-no-sacrifice')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'SignalTech X Ultra')
    ->deckTop(['Fairy Whispers', 'Fluffy Shopkeep', 'Dungeon Guide', 'Charge the Soul'])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)->pass(1)
    ->tested('SignalTech X Ultra')
    ->register();

// no ally card among the top three: no prompt, all three to the bottom, SignalTech is not sacrificed
gaScenario('signaltech-x-ultra-rest-look-three-no-ally-all-three-bottom-no-sacrifice')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'SignalTech X Ultra')
    ->deckTop(['Fairy Whispers', 'Fairy Whispers', 'Fairy Whispers', 'Charge the Soul'])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)
    ->tested('SignalTech X Ultra')
    ->register();

// PLAYER 2 seating (perspective check): player 1's pass opens a response window in which player 2 activates it (the activation
// is offered because the ability is usable at opportunity speed); no draw has happened for player 2 yet
gaScenario('signaltech-x-ultra-player-two-rest-look-three-reveal-ally-rest-bottom-sacrifice')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'SignalTech X Ultra', player: 2)
    ->deckTop(['Fairy Whispers', 'Fluffy Shopkeep', 'Dungeon Guide', 'Charge the Soul'], player: 2)
    ->endTurn()
    ->action(2, 10001, 'myField-1!CustomInput!Activate:0')->answer(2, 'myDeck-2')
    ->tested('SignalTech X Ultra')
    ->register();

// top: DG, FS, Fulgurite Coordinator (ARCANE, 3rd) then Charge the Soul (marker): reveal 3, 3 damage, arcane to hand, DG + FS to the bottom
gaScenario('storm-tyrant-eye-reveal-until-arcane-damage-rest-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Storm Tyrant\'s Eye')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fulgurite Coordinator', 'Charge the Soul'])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Storm Tyrant\'s Eye')
    ->register();

// --- Spring Cleaning (dZ0Y2ILgZW): "Put one of your omens into your graveyard. If you do, look at the top three cards of your deck. Banish one of them and put an omen counter on it. Put the
// rest on the bottom of your deck in any order." The generated handler looked with a fixed-index MZMove loop: only the top card was ever looked at. An omen is a banished card with an omen counter. ---
// Notes carried over from the original definition:
//   - an existing omen -> myBanish-0
//   - Spring Cleaning -> myHand-7
//   - top three looked at (the 3rd, Fairy Whispers, becomes the new omen; the other two go to the bottom), then a marker
gaScenario('spring-cleaning-omen-to-graveyard-looks-three-banishes-deep-card-as-omen-rest-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myHand', 'Spring Cleaning')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Charge the Soul'])
    ->play('Spring Cleaning')->pay(2)->target('myBanish-0')->target('myTempZone-2')
    ->register();

// --- Silvie, Earth's Tune (ZR8tnLruR6): "On Enter: Reveal cards from the top of your deck until you reveal a tera element Animal or Beast ally card. Put that card into your hand and the rest on the
// bottom of your deck." Same fixed-index bottom loop as Gaia's Songbird (fixed by the same override); the champion levels up from Silvie, With the Pack (level 2). ---
// Notes carried over from the original definition:
//   - Silvie, With the Pack (TAMER, level 2): the level-up base
//   - level-up memory payment 1
//   - reveal until Gaia's Songbird (TERA Animal ally, 4th); the three before it go to the bottom; a Fluffy Shopkeep (the level-up's own draw takes it); then a marker
//   - level up to Silvie, Earth's Tune
gaScenario('silvie-earths-tune-enter-reveals-until-deep-tera-animal-rest-to-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Silvie, Earth's Tune
# Main
10 Dungeon Guide
10 Fluffy Shopkeep
DECK
    )
    ->champion(cardName: 'Silvie, With the Pack')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Gaia\'s Songbird', 'Fluffy Shopkeep', 'Charge the Soul'])
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')
    ->tested('Silvie, Earth\'s Tune')
    ->register();

// --- Starbirth (qxu89i1mrk): "Draw a card into your memory. Then summon an Astral Shard token." The generated body was registered only in the dead $activateCardAbilities table (nothing dispatches
// it), so playing Starbirth did nothing. ---
// Notes carried over from the original definition:
//   - Diana, Moonpiercer (level 3, ASTRA): unlocks the ASTRA element
//   - Starbirth -> myHand-7
//   - the card drawn into memory, then a marker
gaScenario('starbirth-draws-into-memory-and-summons-astral-shard')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Diana, Moonpiercer')
    ->seed('myHand', 'Starbirth')
    ->deckTop(['Fluffy Shopkeep', 'Charge the Soul'])
    ->castSpell('Starbirth', pay: 1)
    ->register();

gaScenario('swooping-talons-level-two-mode-refuses-memory-only-item-destroys-low-reserve-item')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Kongming, Erudite Strategist')
    ->seed('myHand', 'Swooping Talons')
    ->seed('myField', 'Scepter of Lumina', player: 2)
    ->seed('myField', 'Forest Cake', player: 2)
    ->castSpell('Swooping Talons', pay: 3)
    ->rejects(1, 'theirField-1', 'Scepter of Lumina has memory cost 1 and no reserve cost: not a legal target')
    ->target('theirField-2')
    ->register();

// Notes carried over from the original definition:
//   - Shatter the Brittle -> myHand-7
//   - Craggy Fatestone (the prerequisite Fatestone; reserve 4: a legal target) -> myField-1
//   - Explosive Concoction (reserve 7, NO memory cost: not a legal target) -> theirField-1
gaScenario('shatter-the-brittle-refuses-expensive-reserve-only-item-destroys-cheap-one')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Shatter the Brittle')
    ->seed('myField', 'Craggy Fatestone')
    ->seed('myField', 'Explosive Concoction', player: 2)
    ->castSpell('Shatter the Brittle', pay: 2)
    ->rejects(1, 'theirField-1', 'Explosive Concoction has reserve cost 7 and no memory cost: not a legal target')
    ->target('myField-1')
    ->register();

gaScenario('spark-alight-refuses-spellshroud-unit-damages-ordinary-ally')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Spark Alight')
    ->seed('myField', 'Genbu, Black Tortoise', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->castSpell('Spark Alight', pay: 2)
    ->rejects(1, 'theirField-1', 'Genbu, Black Tortoise has spellshroud: not a legal target of the Spell Spark Alight')
    ->target('theirField-2')
    ->register();

gaScenario('stonescale-band-enter-discard-one-ally-then-decline-draws-one')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Stonescale Band
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Guo Jia, Chosen Disciple', lineage: ['Kongming, Fel Eidolon'])
    ->seed('myMemory', 'Fluffy Shopkeep')
    ->endTurn()
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)->target('myMaterial-0')->pay(1)->pass(1)
    ->tested('Stonescale Band')
    ->expectZoneCount('myGraveyard', 1, 1, 'Exactly one ally card was discarded')
    ->expectZoneCount('myHand', 7, 1, 'One ally discarded then declined: draws one card (hand back to 7)')
    ->expectZoneCount('myDeck', 12, 1, 'The draw came from the deck (13 -> 12)')
    ->register();

// Stabilizing Capacitance (c4sy8u49sk): "Put any amount of cards from your memory on the bottom of your deck in any order. Then draw
// that many cards into your memory." Put one memory card on the bottom, then decline the second: the draw into memory must still
// happen (StabilizingCapacitancePick was queued without dontSkipOnPass, so the decline skipped StabilizingCapacitanceFinish).
// Notes carried over from the original definition:
//   - Rai, Storm Seer (ARCANE Mage): the spell's element matches the champion
//   - memory card 1/2
//   - memory card 2/2
//   - Stabilizing Capacitance -> myHand-7
gaScenario('stabilizing-capacitance-decline-second-pick-still-draws-into-memory')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Rai, Storm Seer')
    ->seed('myMemory', 'Fluffy Shopkeep')
    ->seed('myMemory', 'Fluffy Shopkeep')
    ->seed('myHand', 'Stabilizing Capacitance')
    ->play('Stabilizing Capacitance')->pay(1)->target('myMemory-0')->pass(1)
    ->expectZoneCount('myMemory', 3, 1, 'One card went to the bottom of the deck and one was drawn into memory: memory is back to 3 (the Capacitance itself entered memory when paid)')
    ->expectZoneCount('myDeck', 13, 1, 'The deck is back to its starting size: one card on the bottom, one drawn')
    ->expectDqEmpty()
    ->covers(['optional-choice-decline', 'draw-into-memory'], 'Put any amount of cards from your memory on the bottom of your deck in any order. Then draw that many cards into your memory.')
    ->register();

// Suffocating Miasma (coxpnjvt9y): "At the beginning of each opponent's recollection phase, that player puts a debuff counter on an ally they control.
// If they don't, deal 2 unpreventable damage to their champion." Player 2, at the start of their recollection phase, declines (PASS):
// the 2 damage must be dealt (SuffocatingMiasmaRecollection was queued without dontSkipOnPass, so the decline skipped the "if they don't" branch).
// Notes carried over from the original definition:
//   - Suffocating Miasma (player 1's phantasia)
//   - Dungeon Guide: player 2's only ally, the debuff target they decline
gaScenario('suffocating-miasma-declined-debuff-deals-two-unpreventable-damage')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Suffocating Miasma')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->endTurn(2)
    ->endTurn()->pass(2)
    ->tested('Suffocating Miasma')
    ->covers(['optional-choice-decline', 'unpreventable-damage'], 'At the beginning of each opponent\'s recollection phase, that player puts a debuff counter on an ally they control. If they don\'t, deal 2 unpreventable damage to their champion.')
    ->register();

// Salamander's Breath (mob9nu6lal): "[Diana Bonus] On Attack: You may banish up to X fire element cards from your graveyard where X is the amount of Aethercharge cards in the attacker's intent. This attack gets +1 POWER for each card banished this way."
// Banish one fire card, then decline the second offer: the +1 POWER must still be applied (SalamandersBanish was queued without dontSkipOnPass, so the decline skipped the handler and the bonus was lost).
// Player 2 attacks on their first turn (nobody can attack on turn 1) with Salamander's Breath loaded with two Aethercharge cards, which go to the intent when it attacks (X = 2).
// Notes carried over from the original definition:
//   - Diana, Aether Dilettante in the lineage: Diana Bonus; RANGER class so the RANGER weapon can be used
//   - Salamander's Breath -> myField-1
//   - loaded with two Aetheric Calibration (Aethercharge)
//   - Baby Red Slime (fire) -> myGraveyard-0
//   - Baby Red Slime (fire) -> myGraveyard-1
gaScenario('salamanders-breath-decline-second-banish-still-applies-power')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Diana, Aether Dilettante'], classes: 'RANGER', player: 2)
    ->seed('myField', 'Salamander\'s Breath', player: 2)
    ->patch('myField-1', ['Subcards' => ['7l9th23niu', '7l9th23niu'], 'Counters' => ['durability' => 3]], player: 2)
    ->seed('myGraveyard', 'Baby Red Slime', player: 2)
    ->seed('myGraveyard', 'Baby Red Slime', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-0!FSM!')->answer(2, 'myField-1')->answer(2, 'theirField-0')
    ->answer(2, 'myGraveyard-0')->pass(2)
    ->tested('Salamander\'s Breath')
    ->covers(['optional-choice-decline', 'diana-bonus', 'on-attack'], '[Diana Bonus] On Attack: You may banish up to X fire element cards from your graveyard where X is the amount of Aethercharge cards in the attacker\'s intent. This attack gets +1 [POWER] for each card banished this way.')
    ->register();

gaScenario('sacrifice-play-sacrificing-two-chessman-allies-gives-plus-four-power')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'WARRIOR')
    ->seed('myField', 'Pawn Piece')
    ->seed('myField', 'Pawn Piece')
    ->seed('myField', 'Pawn Piece')
    ->seed('myHand', 'Sacrifice Play')
    ->endTurn()
    ->endTurn(2)->answer(1, '-')
    ->play('Sacrifice Play')->answer(1, 'myField-1&myField-2')->pay(3)->target('myField-1')->target('theirField-0')
    ->covers(['additional-cost-sacrifice', 'command-chessman'], 'As an additional cost to activate this card, sacrifice up to two awake Chessman allies.', 'CARDNAME enters the intent with +2 [POWER] for each ally sacrificed this way.')
    ->register();

gaScenario('sacrifice-play-declined-sacrifice-still-pays-reserve-for-base-power')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'WARRIOR')
    ->seed('myField', 'Pawn Piece')
    ->seed('myField', 'Pawn Piece')
    ->seed('myField', 'Pawn Piece')
    ->seed('myHand', 'Sacrifice Play')
    ->endTurn()
    ->endTurn(2)->answer(1, '-')
    ->play('Sacrifice Play')->pass(1)->pay(3)->target('myField-1')->target('theirField-0')
    ->covers(['optional-choice-decline', 'additional-cost-sacrifice'], 'As an additional cost to activate this card, sacrifice up to two awake Chessman allies.')
    ->register();

// Notes carried over from the original definition:
//   - Forest Cake is destroyed
gaScenario('smashing-force-banishes-two-fire-cards-and-destroys-a-low-cost-item')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'GUARDIAN', player: 2)
    ->seed('myGraveyard', 'Baby Red Slime', player: 2)
    ->seed('myGraveyard', 'Baby Red Slime', player: 2)
    ->seed('myField', 'Scepter of Lumina')
    ->seed('myField', 'Forest Cake')
    ->seed('myHand', 'Smashing Force', player: 2)
    ->endTurn()
    ->play('Smashing Force', 2)->pay(4, 2)->answer(2, 'theirField-0')->answer(2, 'YES')->answer(2, 'myGraveyard-0')
    ->answer(2, 'myGraveyard-0')
    ->rejects(2, 'theirField-1', 'Scepter of Lumina has memory cost 1 and no reserve cost: not a legal target')
    ->answer(2, 'theirField-2')
    ->expectZoneCount('theirField', 2, 2, 'Player 1\'s champion and the memory-only Scepter remain: the Forest Cake (reserve cost 2) was destroyed')
    ->expectZoneCount('myGraveyard', 1, 2, 'Both fire cards left the graveyard; only the resolved Smashing Force is there')
    ->expectZoneCount('myBanish', 2, 2, 'The two banished fire cards')
    ->covers(['on-attack', 'class-bonus', 'destroy', 'cost-filter'], '[Class Bonus] On Attack: You may banish two fire element cards from your graveyard. When you do, destroy target item or weapon with memory cost 0 or reserve cost 4 or less.')
    ->register();

gaScenario('smashing-force-declined-banish-keeps-the-fire-cards-and-the-item')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'GUARDIAN', player: 2)
    ->seed('myGraveyard', 'Baby Red Slime', player: 2)
    ->seed('myGraveyard', 'Baby Red Slime', player: 2)
    ->seed('myField', 'Scepter of Lumina')
    ->seed('myField', 'Forest Cake')
    ->seed('myHand', 'Smashing Force', player: 2)
    ->endTurn()
    ->play('Smashing Force', 2)->pay(4, 2)->answer(2, 'theirField-0')->answer(2, 'NO')
    ->expectZoneCount('myGraveyard', 3, 2, 'Declined: both fire cards stay in the graveyard (plus the resolved Smashing Force)')
    ->expectZoneCount('myBanish', 0, 2, 'Nothing was banished')
    ->expectZoneCount('theirField', 3, 2, 'Player 1\'s champion, Scepter and Forest Cake are all untouched')
    ->expectDqEmpty()
    ->covers(['on-attack', 'optional-choice-decline'], '[Class Bonus] On Attack: You may banish two fire element cards from your graveyard. When you do, destroy target item or weapon with memory cost 0 or reserve cost 4 or less.')
    ->register();

// Sinister Mindreaver (jozihslnhz): "On Champion Hit: Look at that opponent's memory. You may discard up to two cards from it. If you do, they draw that many cards into their memory." Discard one memory card, decline the second:
// the opponent draws one card into memory (declining used to skip SinisterMindreaverPick2, which does the drawing, so the opponent drew nothing).
// Notes carried over from the original definition:
//   - ASSASSIN class (Spirit of Fire has no class)
//   - Sinister Mindreaver -> p2 myField-1
//   - player 1's memory -> myMemory-0
//   - -> myMemory-1
//   - -> myMemory-2
gaScenario('sinister-mindreaver-decline-second-discard-opponent-still-draws-one')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'ASSASSIN', player: 2)
    ->seed('myField', 'Sinister Mindreaver', player: 2)
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Fluffy Shopkeep')
    ->seed('myMemory', 'Dungeon Guide')
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->answer(2, 'theirMemory-0')->pass(2)
    ->tested('Sinister Mindreaver')
    ->expectZoneCount('myMemory', 3, 1, 'Player 1 had 3 memory cards: one was discarded and one was drawn into memory for it')
    ->expectZoneCount('myGraveyard', 1, 1, 'The discarded memory card')
    ->expectZoneCount('myDeck', 12, 1, 'One card was drawn into memory')
    ->expectDqEmpty()
    ->covers(['optional-choice-decline', 'on-hit'], 'On Champion Hit: Look at that opponent\'s memory. You may discard up to two cards from it. If you do, they draw that many cards into their memory.')
    ->register();

// Stifling Trap (z5exbwdp7q): "Deal 2 damage to target ally, then negate all on enter triggers from that ally." With no ally anywhere to target (the opponent's Dungeon Guide is still on the stack) the Class Bonus alternate cost must not
// be offered: it spent two preparation counters on an activation that could not do anything.
// Notes carried over from the original definition:
//   - not an offered option (no ally to target): nothing happens and the two preparation counters are kept
//   - p1 passes the window
//   - player 2 declines the newly played Dungeon Guide's On Enter
gaScenario('stifling-trap-memory-alt-cost-not-offered-without-a-target-ally')
    ->deck($tristanDeck)
    ->patch('myField-0', ['CardID' => 'gt7lh9v221', 'Subcards' => ['bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 2]])
    ->seed('myMemory', 'Stifling Trap')
    ->endTurn()
    ->playSlot(0, 2)->pay(3, 2)->target('myMemory-0')->pass(1)->answer(2, 'NO')
    ->tested('Stifling Trap')
    ->covers(['alternate-cost', 'legal-target', 'cost-validation'], '[Class Bonus] If it\'s not your turn, you may remove two preparation counters from your champion to activate this card from your memory without paying its reserve cost.', 'Deal 2 damage to target ally.')
    ->register();

// Notes carried over from the original definition:
//   - MAGE class (cost reduction)
//   - Aquamirage Whisper (printed spellshroud) -> theirField-1
//   - Dungeon Guide -> theirField-2
//   - Spark Alight -> myHand-7
//   - the Dungeon Guide is the legal target
gaScenario('spark-alight-spellshroud-unit-is-not-a-legal-target')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'MAGE')
    ->seed('myField', 'Aquamirage Whisper', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Spark Alight')
    ->castSpell('Spark Alight', pay: 2)
    ->rejects(1, 'theirField-1', 'Aquamirage Whisper has spellshroud: not a legal target for Spark Alight')
    ->target('theirField-2')
    ->covers(['spellshroud', 'target-filter'], 'Deal 2 unpreventable damage to target unit.', 'Units with spellshroud can\'t be targeted by Spells.')
    ->register();

// Sojourner's Hunt (aqlbuznsz4): "[REST]: Sojourner's Hunt becomes a weapon subtype of your choice in addition to its other types until end of turn."
// Notes carried over from the original definition:
//   - Sojourner's Hunt -> myField-1
gaScenario('sojourners-hunt-rest-becomes-a-chosen-weapon-subtype-until-end-of-turn')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Sojourner\'s Hunt')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, '0')
    ->tested('Sojourner\'s Hunt')
    ->register();

// Notes carried over from the original definition:
//   - Seraphic Legion's Descent -> myHand-7
//   - Angel Attendant -> myHand-8
//   - Reverent Seraphim -> myMemory-0
//   - Benediction Angel -> myGraveyard-0
//   - Angelic Vanguard on top of the deck
gaScenario('seraphic-legions-descent-enter-banishes-angels-from-deck-hand-memory-and-graveyard-and-draws-into-memory')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Kongming, Fel Eidolon'])
    ->seed('myHand', 'Seraphic Legion\'s Descent')
    ->seed('myHand', 'Angel Attendant')
    ->seed('myMemory', 'Reverent Seraphim')
    ->seed('myGraveyard', 'Benediction Angel')
    ->deckTop(['Angelic Vanguard'])
    ->play('Seraphic Legion\'s Descent')->pay(3)->target('myDeck-0')->answer(1, 'myHand-4&myMemory-0&myGraveyard-0')
    ->register();

gaScenario('seraphic-legions-descent-level-three-pays-one-and-lets-you-activate-the-banished-angel')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Guo Jia, Heaven\'s Favored')
    ->seed('myField', 'Seraphic Legion\'s Descent')
    ->seed('myBanish', 'Angelic Vanguard', properties: ['Counters' => ['seraphicLegion' => 1]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(1)->target('myBanish-0')
    ->action(1, 10002, 'myBanish-0!FSM!')->answer(1, 'NO')->pay(3)
    ->tested('Seraphic Legion\'s Descent')
    ->register();

gaScenario('seraphic-legions-descent-banished-angel-is-not-activatable-without-the-level-three-ability')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Guo Jia, Heaven\'s Favored')
    ->seed('myField', 'Seraphic Legion\'s Descent')
    ->seed('myBanish', 'Angelic Vanguard', properties: ['Counters' => ['seraphicLegion' => 1]])
    ->action(1, 10002, 'myBanish-0!FSM!')
    ->tested('Seraphic Legion\'s Descent')
    ->expectZoneCount('myBanish', 1, 1, 'Without the ability the banished Angel cannot be activated: it stays banished')
    ->expectZoneCount('myField', 2, 1, 'Nothing entered the field')
    ->expectDqEmpty()
    ->register();

// Notes carried over from the original definition:
//   - Seraphic Legion's Descent -> myField-1 (the champion is level 0/1)
gaScenario('seraphic-legions-descent-level-three-ability-is-refused-below-level-three')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Seraphic Legion\'s Descent')
    ->seed('myBanish', 'Angelic Vanguard', properties: ['Counters' => ['seraphicLegion' => 1]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Seraphic Legion\'s Descent')
    ->register();

// --- Printed activation costs of field abilities (GAFieldAbilityCostTable): each fixture activates the ability in the
// generic environment of gaAbilitySandbox() and pins the paid cost ---
gaAbilitySandbox('scepter-of-lumina-ability-1-pays-its-printed-cost', 'Scepter of Lumina', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(5)->pass(1)
    ->tested('Scepter of Lumina')
    ->expectCostFootprint(hand: 9, memory: 7, banish: 1, graveyard: 3, field: 6)
    ->register();

gaAbilitySandbox('servant-s-obligation-ability-1-pays-its-printed-cost', 'Servant\'s Obligation', classes: 'GUARDIAN', cardPatch: ['Status' => 2], champion: 'nn48ne8a05')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->pass(1)
    ->tested('Servant\'s Obligation')
    ->expectCostFootprint(hand: 10, memory: 5, banish: 1, graveyard: 3, field: 7)
    ->register();

gaAbilitySandbox('sentinel-fabricator-ability-1-pays-its-printed-cost', 'Sentinel Fabricator', classes: 'GUARDIAN', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->pass(1)
    ->tested('Sentinel Fabricator')
    ->register();

gaAbilitySandbox('scarlet-tassel-ability-1-pays-its-printed-cost', 'Scarlet Tassel', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->pass(1)
    ->tested('Scarlet Tassel')
    ->register();

gaAbilitySandbox('sundering-moon-ability-1-pays-its-printed-cost', 'Sundering Moon', cardPatch: ['Status' => 2], champion: 'zd8l14052j')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->target('myField-0')->pass(1)
    ->tested('Sundering Moon')
    ->expectCostFootprint(hand: 11, memory: 4, banish: 0, graveyard: 3, field: 6)
    ->register();

gaAbilitySandbox('signaltech-one-ability-1-pays-its-printed-cost', 'SignalTech One', classes: 'RANGER', cardPatch: ['Status' => 2])
    ->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,px60u5n1do,em6eEh9q8y;Bottom=')->answer(1, 'YES')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->pass(1)
    ->tested('SignalTech One')
    ->expectCostFootprint(hand: 11, memory: 3, banish: 2, graveyard: 4, field: 6)
    ->register();

gaAbilitySandbox('slate-whetstone-ability-1-pays-its-printed-cost', 'Slate Whetstone', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)
    ->tested('Slate Whetstone')
    ->expectCostFootprint(hand: 14, memory: 2, banish: 1, graveyard: 3, field: 6)
    ->register();

gaAbilitySandbox('seed-of-empowerment-ability-1-pays-its-printed-cost', 'Seed of Empowerment', classes: 'MAGE', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)
    ->tested('Seed of Empowerment')
    ->expectCostFootprint(hand: 13, memory: 2, banish: 1, graveyard: 3, field: 6)
    ->register();

gaAbilitySandbox('spellward-scepter-ability-1-pays-its-printed-cost', 'Spellward Scepter', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)
    ->tested('Spellward Scepter')
    ->expectCostFootprint(hand: 13, memory: 2, banish: 1, graveyard: 3, field: 6)
    ->register();

gaAbilitySandbox('seed-of-nature-ability-1-pays-its-printed-cost', 'Seed of Nature', classes: 'TAMER', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)
    ->tested('Seed of Nature')
    ->expectCostFootprint(hand: 13, memory: 2, banish: 1, graveyard: 3, field: 6)
    ->register();

gaAbilitySandbox('sordelle-unmoored-exception-ability-1-pays-its-printed-cost', 'Sordelle, Unmoored Exception', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->target('myField-2')
    ->tested('Sordelle, Unmoored Exception')
    ->register();

// Sablier Guard (tu7jvjf2gh): "+1 POWER and +1 LIFE for each omen you have with different reserve costs."
gaScenario('sablier-guard-gets-plus-one-per-omen-with-different-reserve-costs')
    ->deck($gaCielDeck)
    ->seed('myField', 'Sablier Guard')
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Caretaker Drone', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->target('myField-0')
    ->tested('Sablier Guard')
    ->register();

// Notes carried over from the original definition:
//   - Ciel, Mirage's Grave (GUARDIAN, UMBRA)
//   - Grande Aiguille (Sword) -> theirField-1 from player 1's view
//   - Sinistre Stab -> p2 myHand-7
gaScenario('sinistre-stab-hit-with-fewer-than-five-omens-goes-to-the-bottom-of-your-lineage')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Ciel, Mirage\'s Grave', lineage: ['Ciel, Loyal Valet'], player: 2)
    ->seed('myField', 'Grande Aiguille', player: 2)
    ->seed('myHand', 'Sinistre Stab', player: 2)
    ->endTurn()
    ->play('Sinistre Stab', 2)->pay(3, 2)->answer(2, 'myField-1')->answer(2, 'theirField-0')
    ->register();

// Notes carried over from the original definition:
//   - five omens
//   - Ciel, Mirage's Grave (GUARDIAN, UMBRA)
//   - Grande Aiguille (Sword) -> theirField-1 from player 1's view
//   - Sinistre Stab -> p2 myHand-7
gaScenario('sinistre-stab-hit-with-five-omens-may-go-to-the-bottom-of-the-hit-champions-lineage')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myBanish', 'Dungeon Guide', player: 2, properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', player: 2, properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', player: 2, properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', player: 2, properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', player: 2, properties: ['Counters' => ['omen' => 1]])
    ->champion(cardName: 'Ciel, Mirage\'s Grave', lineage: ['Ciel, Loyal Valet'], player: 2)
    ->seed('myField', 'Grande Aiguille', player: 2)
    ->seed('myHand', 'Sinistre Stab', player: 2)
    ->endTurn()
    ->play('Sinistre Stab', 2)->pay(3, 2)->answer(2, 'myField-1')->answer(2, 'theirField-0')->answer(2, 'YES')
    ->register();

// Notes carried over from the original definition:
//   - five omens
//   - Ciel, Mirage's Grave (GUARDIAN, UMBRA)
//   - Grande Aiguille (Sword) -> theirField-1 from player 1's view
//   - Sinistre Stab -> p2 myHand-7
gaScenario('sinistre-stab-hit-with-five-omens-declining-leaves-it-in-the-graveyard')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myBanish', 'Dungeon Guide', player: 2, properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', player: 2, properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', player: 2, properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', player: 2, properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', player: 2, properties: ['Counters' => ['omen' => 1]])
    ->champion(cardName: 'Ciel, Mirage\'s Grave', lineage: ['Ciel, Loyal Valet'], player: 2)
    ->seed('myField', 'Grande Aiguille', player: 2)
    ->seed('myHand', 'Sinistre Stab', player: 2)
    ->endTurn()
    ->play('Sinistre Stab', 2)->pay(3, 2)->answer(2, 'myField-1')->answer(2, 'theirField-0')->answer(2, 'NO')
    ->register();

gaScenario('slime-calling-is-refused-during-your-own-main-phase')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Spirit of Wind', classes: 'TAMER')
    ->seed('myHand', 'Slime Calling')
    ->play('Slime Calling')
    ->expectZoneCount('myHand', 8, 1, 'Refused: the card is still in hand')
    ->expectZoneCount('myMemory', 0, 1, 'Nothing was paid')
    ->expectDqEmpty()
    ->register();

gaScenario('slime-calling-at-the-opponents-end-phase-activates-the-top-card-slime-for-one-less')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Spirit of Wind', classes: 'TAMER')
    ->seed('myHand', 'Slime Calling')
    ->deckTop(['Green Slime', 'Dungeon Guide', 'Dungeon Guide'])
    ->endTurn()
    ->endTurn(2)->target('myHand-7')->pay(2)->target('myTempZone-2')->answer(1, 'Top=;Bottom=em6eEh9q8y,em6eEh9q8y')
    ->pay(2)
    ->tested('Slime Calling')
    ->register();

// Sidereal Spellshot (xwwkxq0vp3): "[Class Bonus] Glimpse X. Then you may load Sidereal Spellshot into an Aetherwing weapon you control."
gaScenario('sidereal-spellshot-class-bonus-glimpses-then-loads')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Arisanna, Astral Zenith'], classes: 'RANGER')
    ->seed('myField', 'Salamander\'s Breath')
    ->seed('myHand', 'Sidereal Spellshot')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Dungeon Guide'])
    ->play('Sidereal Spellshot')->answer(1, 'Top=px60u5n1do;Bottom=em6eEh9q8y')->answer(1, 'YES')
    ->register();

gaScenario('sidereal-spellshot-without-the-class-bonus-skips-the-glimpse-but-can-still-load')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Arisanna, Astral Zenith'])
    ->seed('myField', 'Salamander\'s Breath')
    ->seed('myHand', 'Sidereal Spellshot')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Dungeon Guide'])
    ->play('Sidereal Spellshot')->answer(1, 'YES')
    ->register();

// Sundering Moon (8677jq0hfm): "[Jin Bonus] On Enter: If you control two or more wind element allies, CARDNAME gets +1 POWER until end of turn." The helper read an ambient variable ("currentMZID")
// that was never stored, so the +1 power was never granted. Jin is the champion, two Windrider Invokers are the wind allies and the Moon enters last (power 1 + 1 = 2).
// Notes carried over from the original definition:
//   - Jin as the champion
//   - Windrider Invoker (wind ally) -> field-1
//   - Windrider Invoker (wind ally) -> field-2
//   - Sundering Moon enters last -> field-3
gaScenario('sundering-moon-enter-with-two-wind-allies-gets-plus-one-power')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Jin, Fate Defiant')
    ->seed('myField', 'Windrider Invoker')
    ->seed('myField', 'Windrider Invoker')
    ->seed('myField', 'Sundering Moon')
    ->target('myField-0')
    ->tested('Sundering Moon', 'Windrider Invoker')
    ->register();

// Seaside Ringleader (eirpdm44nt): "[Class Bonus] [Element Bonus] (2), Banish this card from your graveyard: Draw a card into your memory. Until end of turn, Animal and Beast ally cards you activate this turn
// enter the field with an additional buff counter on them." A Baby Blue Slime (Animal) is then activated and enters with a buff counter.
// Notes carried over from the original definition:
//   - Tamer champion: the Class Bonus applies
//   - Seaside Ringleader -> myGraveyard-0
//   - Baby Blue Slime (ANIMAL) -> myHand-7
gaScenario('seaside-ringleader-graveyard-ability-draws-into-memory-and-buffs-animal-allies')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'TAMER')
    ->seed('myGraveyard', 'Seaside Ringleader')
    ->seed('myHand', 'Baby Blue Slime')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->pay(2)
    ->playSlot(5)->pay(2)
    ->tested('Seaside Ringleader', 'Baby Blue Slime')
    ->register();

// Sword Saint of Everflame (lpy7ie4v8n): "[Class Bonus] (2), Banish this card from your graveyard: Target fire element weapon or ally gets +2 POWER until end of turn." The (2) was never paid (the queued ReserveCard was
// popped as if it were the answered decision). A Warrior champion; Red Slime (fire ally) is the target.
// Notes carried over from the original definition:
//   - Red Slime (fire ally) -> myField-1
//   - Sword Saint of Everflame -> myGraveyard-0
gaScenario('sword-saint-of-everflame-graveyard-ability-pays-two-and-gives-fire-ally-plus-two-power')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'WARRIOR')
    ->seed('myField', 'Red Slime')
    ->seed('myGraveyard', 'Sword Saint of Everflame')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->target('myField-1')->pay(2)
    ->tested('Sword Saint of Everflame', 'Red Slime')
    ->register();

gaScenario('seaside-rangefinder-graveyard-ability-pays-two-banishes-itself-and-makes-a-unit-distant')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['Counters' => ['_overrides' => ['classes' => 'RANGER', 'element' => 'WATER']]])
    ->seed('myGraveyard', 'Seaside Rangefinder')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->target('theirField-0')->pay(2)
    ->tested('Seaside Rangefinder')
    ->expectProperty('p2Field-0', 'TurnEffects', '["DISTANT"]', 'The targeted unit became distant')
    ->expectZoneCount('myBanish', 1, 1, 'Seaside Rangefinder banished itself from the graveyard as the cost')
    ->expectZoneCount('myMemory', 2, 1, '(2) was paid')
    ->covers(['activated-ability', 'distant', 'class-bonus', 'cost'], '[Class Bonus] [Element Bonus] (2), Banish this card from your graveyard: Target unit becomes distant.')
    ->register();

gaScenario('seaside-rangefinder-graveyard-ability-refused-without-the-ranger-class')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['Counters' => ['_overrides' => ['classes' => 'WARRIOR', 'element' => 'WATER']]])
    ->seed('myGraveyard', 'Seaside Rangefinder')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->target('theirField-0')
    ->tested('Seaside Rangefinder')
    ->expectZoneCount('myGraveyard', 1, 1, 'Seaside Rangefinder stays in the graveyard: the ability is refused without the Ranger class')
    ->expectZoneCount('myMemory', 0, 1, 'Nothing was paid')
    ->expectProperty('p2Field-0', 'TurnEffects', '[]', 'The targeted unit did not become distant')
    ->covers(['activated-ability', 'class-bonus', 'cost-validation'], '[Class Bonus] [Element Bonus] (2), Banish this card from your graveyard: Target unit becomes distant. Activate this ability only if your champion\'s class matches this card\'s class.')
    ->register();

// Scorching Trap (wjbqjdmthh): "[Class Bonus] If it's not your turn, you may remove a preparation counter from your champion to activate this card from your memory without paying its reserve cost.
// Deal 2 damage to target attacking unit." Player 1's champion is Spirit of Fire with the ASSASSIN class and one preparation counter; player 2 attacks with a Dungeon Guide and player 1 answers from memory.
// Notes carried over from the original definition:
//   - Scorching Trap in MEMORY -> p1 myMemory-0
//   - Dungeon Guide, the attacker -> p2 field-1
//   - end player 1 turn
//   - player 2 attacks with the Dungeon Guide
//   - ... targeting player 1's champion
//   - player 1 activates Scorching Trap FROM MEMORY (alternate cost: remove one preparation counter)
gaScenario('scorching-trap-memory-alt-cost-removes-one-counter-and-deals-two-to-the-attacker')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['Counters' => ['_overrides' => ['classes' => 'ASSASSIN'], 'preparation' => 1]])
    ->seed('myMemory', 'Scorching Trap')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->target('myMemory-0')
    ->tested('Scorching Trap')
    ->register();

// Spirit of Purity (FUCJA8IAMi): "Lineage Release -- Each player banishes two cards from their graveyard." Each player makes their own choices, in their own frame ("myGraveyard-N").
// Notes carried over from the original definition:
//   - Spirit of Purity in the champion's inner lineage
//   - Dungeon Guide -> p1 graveyard-0
//   - Fluffy Shopkeep -> p1 graveyard-1
//   - Dungeon Guide -> p1 graveyard-2
//   - Fluffy Shopkeep -> p2 graveyard-0
//   - Dungeon Guide -> p2 graveyard-1
//   - Fluffy Shopkeep -> p2 graveyard-2
//   - activate the Lineage Release
//   - player 1 banishes two from their graveyard
//   - player 2 banishes two from THEIR graveyard
gaScenario('spirit-of-purity-lineage-release-each-player-banishes-two-from-their-graveyard')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Spirit of Purity'])
    ->seed('myGraveyard', 'Dungeon Guide')
    ->seed('myGraveyard', 'Fluffy Shopkeep')
    ->seed('myGraveyard', 'Dungeon Guide')
    ->seed('myGraveyard', 'Fluffy Shopkeep', player: 2)
    ->seed('myGraveyard', 'Dungeon Guide', player: 2)
    ->seed('myGraveyard', 'Fluffy Shopkeep', player: 2)
    ->action(1, 10001, 'myField-0!CustomInput!Activate:0')->target('myGraveyard-0')->target('myGraveyard-0')
    ->answer(2, 'myGraveyard-0')->answer(2, 'myGraveyard-0')
    ->tested('Spirit of Purity')
    ->register();

gaScenario('shadows-claw-activates-from-the-material-deck-with-four-preparation-counters')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['Subcards' => ['he6kd7hocc'], 'Counters' => ['_overrides' => ['classes' => 'ASSASSIN'], 'preparation' => 4]])
    ->seed('myMaterial', 'Shadow\'s Claw')
    ->action(1, 10002, 'myMaterial-0!FSM!')
    ->tested('Shadow\'s Claw')
    ->register();

gaScenario('shadows-claw-is-not-activated-from-the-material-deck-with-only-three-preparation-counters')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->patch('myField-0', ['Subcards' => ['he6kd7hocc'], 'Counters' => ['_overrides' => ['classes' => 'ASSASSIN'], 'preparation' => 3]])
    ->seed('myMaterial', 'Shadow\'s Claw')
    ->action(1, 10002, 'myMaterial-0!FSM!')
    ->tested('Shadow\'s Claw')
    ->register();
