<?php
/**
 * Fixtures for cards starting with 'O', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Otherworldly Possessions: enlighten counter per distinct reserve cost you control/graveyard ---
// Notes carried over from the original definition:
//   - Dungeon Guide (reserve cost 3)
//   - Fairy Whispers (reserve cost 1)
gaScenario('otherworldly-possessions-enlighten')
    ->standardDeck('Otherworldly Possessions')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myGraveyard', 'Fairy Whispers')
    ->seed('myHand', 'Otherworldly Possessions')
    ->castSpell('Otherworldly Possessions', pay: 5, window: 'both')
    ->register();

// --- Optical Control: choose a card type on enter ---
// Notes carried over from the original definition:
//   - LUXEM lineage/element unlock
gaScenario('optical-control-choose-type')
    ->standardDeck('Optical Control')
    ->champion(lineage: ['Zander, Blinding Steel'])
    ->seed('myHand', 'Optical Control')
    ->play('Optical Control')->pay(3)->answer(1, '2')
    ->register();

// --- Ornamental Greatsword: [Class Bonus] On Enter target ally gets +1 POWER until EOT ---
// Ornamental Greatsword is REGALIA with a 0-memory-cost On Enter ability, so (unlike the
// other REGALIA items in this deck) it must actually MATERIALIZE for its enterAbilities to
// fire -- direct field-seeding would skip the trigger entirely. Memory-cost REGALIA
// materialize like champions, via the material-phase MZMAYCHOOSE, only offered on the turn
// player's OWN turn after turn 1 (MaterializePhase() in MaterializeLogic.php), so both
// players end turn 1/2 to reach P1's turn 3. Its enterAbilities call
// IsClassBonusActive($player) with NO classes argument, which (per GameLogic.php's
// IsClassBonusActive) requires only that SOME champion is on the field -- not actually gated
// on this card's own GUARDIAN/WARRIOR classes, an engine-behavior detail noted here rather
// than assumed away; the default Spirit of Fire champion already satisfies it. A Dungeon
// Guide is seeded as the ally target.
// Notes carried over from the original definition:
//   - GUARDIAN champion: the [Class Bonus] is active (a bare class-bonus gate used to be always true)
//   - Dungeon Guide, the ally target
gaScenario('ornamental-greatsword-enter-ally-power')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Ornamental Greatsword
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK
    )
    ->champion(classes: 'GUARDIAN')
    ->seed('myField', 'Dungeon Guide')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->target('myField-1')
    ->tested('Ornamental Greatsword')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'TurnEffects', '["qyQLlDYBlr"]')
    ->expectProperty('p1Field-2', 'CardID', 'qyQLlDYBlr')
    ->expectProperty('p1Field-2', 'Status', '2')
    ->expectDamage('p1Field-2', 0)
    ->expectProperty('p1Field-2', 'Counters', '{"durability":1}')
    ->expectProperty('p1Field-2', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectZoneCount('myHand', 8, 1)
    ->expectZoneCount('myField', 3, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// --- Opening Cut: [Class Bonus] +2 POWER while exactly 1 card in memory ---
// Opening Cut's only ability is [Class Bonus] +2 POWER while its controller has exactly 1
// card in Memory (GameLogic.php ~11053). Champion CardID-patched to Lorraine, Blademaster
// (WARRIOR), with exactly 1 filler card seeded into myMemory. Reserve cost 1.
// Notes carried over from the original definition:
//   - exactly 1 card in memory
gaScenario('opening-cut-class-bonus-memory-power')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Blademaster')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myHand', 'Opening Cut')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->castSpell('Opening Cut', pay: 1)
    ->action(1, 10002, 'myField-0!FSM!')->target('theirField-0')->answer(2, '-')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'Status', '1')
    ->expectProperty('p1Field-0', 'Counters', '{"_champCombatDamageDealtThisTurn":3}')
    ->expectProperty('p1Graveyard-0', 'CardID', 'vBetRTn3eW')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'CHAMP_DEALT_COMBAT_DMG')
    ->expectDamage('p2Field-0', 3)
    ->expectProperty('p2Field-0', 'Counters', '{"_champDamageThisTurn":3}')
    ->expectProperty('p2Field-0', 'TurnEffects', '["WAS_ATTACKED","DAMAGED_SINCE_LAST_TURN","CHAMP_DMG_BY_P1"]')
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// --- Orb of Choking Fumes: Banish -- opponents' cards cost 1 more this turn; Class Bonus draw ---
// Orb of Choking Fumes is seeded directly onto the field (materialize flow out of scope, same
// as insignia-of-corhazi-rest-prepare). The starting champion's CardID is patched directly to
// Zander, Deft Executor (ASSASSIN) so IsClassBonusActive($player, ["ASSASSIN"]) is true for the
// ActivateAbility macro's Class Bonus draw clause.
// Notes carried over from the original definition:
//   - Zander, Deft Executor (ASSASSIN) - Class Bonus precondition
//   - Orb of Choking Fumes, seeded straight onto the field
gaScenario('orb-of-choking-fumes-banish-cost-cb-draw')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Zander, Deft Executor')
    ->seed('myField', 'Orb of Choking Fumes')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Orb of Choking Fumes')
    ->expectDqEmpty()
    ->expectProperty('p1Hand-7', 'CardID', 'em6eEh9q8y', 'Class Bonus draws a card')
    ->expectProperty('p1Banish-0', 'CardID', 'llQe0cg4xJ', 'The item banishes itself to activate')
    ->expectProperty('p1Banish-0', 'Status', '2')
    ->expectDamage('p1Banish-0', 0)
    ->expectProperty('p1Banish-0', 'Counters', '[]')
    ->expectProperty('p1Banish-0', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'llQe0cg4xJ_COST', 'Global effect makes opponents\' cards cost 1 more this turn')
    ->expectZoneCount('myHand', 8, 1)
    ->expectZoneCount('myField', 1, 1)
    ->register();

// Obscuring Threads: up to one target Fatestone or Fatebound object gains spellshroud until end
// of turn.
// Notes carried over from the original definition:
//   - MZMAYCHOOSE: target the Craggy Fatestone.
gaScenario('obscuring-threads-spellshroud')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
4 Obscuring Threads
6 Dungeon Guide
DECK
    )
    ->seed('myField', 'Craggy Fatestone')
    ->playSlot(2)->pay(2)->passBoth()->target('myField-1')
    ->tested('Obscuring Threads')
    ->expectProperty('myField-1', 'TurnEffects', '["ENTERED_THIS_TURN","SPELLSHROUD"]', 'The targeted Fatestone item gains spellshroud until end of turn')
    ->register();

// --- Overlapping Visages (PYAnl70edq): Each player sacrifices an ally. If at least one non-Distortion
// ally and at least one Distortion ally were sacrificed this way, you summon a Lost Being token. ---
// Notes carried over from the original definition:
//   - Merlin, Surreal Figment (P8RBSywC30) is a real DISTORTION ally; Dungeon Guide is a plain (non-Distortion) ally.
gaScenario('overlapping-visages-mixed-sac-summon')
    ->deckTemplate('spirit-dungeon-guide', 'Overlapping Visages')
    ->seed('myField', 'Merlin, Surreal Figment')
    ->seed('theirField', 'Dungeon Guide')
    ->playSlot(1)->pay(4)->target('myField-1')->answer(2, 'myField-1')->passBoth()
    ->tested('Overlapping Visages')
    ->register();

// --- Obelith Escort (3uqgjoBQ9G): Prepare 2. Summon a Memorite Obelith token with a sheen
// counter; an additional token if prepared. Reserve cost 3 -- cast for real, declining the
// optional Prepare 2 cost to keep the fixture to the base (single-token) case. ---
// Notes carried over from the original definition:
//   - "Prepare 2?" -- decline, so exactly one token is summoned.
gaScenario('obelith-escort-summon-sheen-token')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Obelith Escort
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
    ->playSlot(5)->pay(3)->passBoth()->answer(1, 'NO')
    ->tested('Obelith Escort')
    ->expectZoneCount('myGraveyard', 1, 1, 'Obelith Escort resolved off the EffectStack into the graveyard')
    ->expectProperty('myGraveyard-0', 'CardID', '3uqgjoBQ9G', 'The graveyard card is Obelith Escort itself, not a stranded/unrelated entry')
    ->expectZoneCount('myField', 2, 1, 'Declining Prepare 2 summoned exactly one Memorite Obelith token')
    ->expectProperty('myField-1', 'CardID', 'fdnlbJm3hr', 'The summoned token is a Memorite Obelith')
    ->expectProperty('myField-1', 'Counters', '{"sheen":1}', 'The token has one sheen counter (base clause, Prepare 2 declined)')
    ->register();

// --- Obstinate Cragback: printed Spellshroud -- an opposing spell cannot target it ---
// Obstinate Cragback (40oe1wf79p, WZKo8sYPxS, ALLY): "Spellshroud (Units with spellshroud can't be targeted by
// Spells.)" -- the Spellshroud is the card's own, UNCONDITIONAL keyword (rules:
// .claude/GrandArchiveSim/refs/comprehensive-rules.md, Keywords > Spellshroud 1: "This can't be targeted by
// spells"). It was never implemented: HasSpellshroud() (Custom/GameLogic.php) recognised printed Spellshroud
// only through a hard-coded card-ID list and HasKeyword_Spellshroud(), which is never generated
// (Data/ProcessKeywordsGA.php has no Spellshroud keyword, so function_exists is false); these IDs were in
// neither, so FilterSpellshroudTargets() kept the card in the target list. Fixed: they are now in
// HasSpellshroud()'s printed-Spellshroud table (after the HasNoAbilities / NO_SPELLSHROUD gates).
// Player 2 controls the holders plus a Dungeon Guide; player 1 plays Liturgy of Corruption ("Put a buff
// counter on target ally ...", a NORM Spell): the target prompt offers ONLY the Dungeon Guide, answering with
// each holder is refused (expectFailure) and the legal answer then resolves.
// Notes carried over from the original definition:
//   - Obstinate Cragback -> p2 field-1
//   - Obstinate Cragback -> p2 field-2
//   - Dungeon Guide -> p2 field-3
//   - Liturgy of Corruption -> p1 myHand-7
//   - player 1 plays Liturgy of Corruption
//   - the Dungeon Guide is the legal target
gaScenario('obstinate-cragback-printed-spellshroud-spell-cannot-target-it')
    ->deck($mordredDeck)
    ->seed('myField', 'Obstinate Cragback', player: 2)
    ->seed('myField', '#WZKo8sYPxS', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Liturgy of Corruption')
    ->castSpell('Liturgy of Corruption', pay: 2)
    ->rejects(1, 'theirField-1', 'Obstinate Cragback (40oe1wf79p) has spellshroud: not a legal target for the opposing Liturgy of Corruption')
    ->rejects(1, 'theirField-2', 'Obstinate Cragback (WZKo8sYPxS) has spellshroud: not a legal target for the opposing Liturgy of Corruption')
    ->target('theirField-3')
    ->tested('Obstinate Cragback', '#WZKo8sYPxS')
    ->expectProperty('p2Field-1', 'Counters', '[]', 'The refused answers left Obstinate Cragback (40oe1wf79p) untouched: Spellshroud stops the opposing Liturgy of Corruption from targeting it', step: 5)
    ->expectProperty('p2Field-2', 'Counters', '[]', 'The refused answers left Obstinate Cragback (WZKo8sYPxS) untouched: Spellshroud stops the opposing Liturgy of Corruption from targeting it', step: 5)
    ->expectProperty('p2Field-3', 'Counters', '[]', 'The refused answers did not apply Liturgy of Corruption to the Dungeon Guide either (prompt still pending)', step: 5)
    ->expectProperty('p2Field-1', 'Counters', '[]', 'Obstinate Cragback (40oe1wf79p) is unaffected by the resolved Liturgy of Corruption')
    ->expectProperty('p2Field-2', 'Counters', '[]', 'Obstinate Cragback (WZKo8sYPxS) is unaffected by the resolved Liturgy of Corruption')
    ->expectProperty('p2Field-3', 'Counters', '{"buff":1}', 'The only legal target, Dungeon Guide, received the buff counter')
    ->expectProperty('p1Graveyard-0', 'CardID', 'BZMKfFZ22T', 'Liturgy of Corruption resolved into player 1\'s graveyard')
    ->expectDqEmpty()
    ->register();

// --- Orb of Regret: declining a further shuffle still draws for the cards already shuffled (dontSkipOnPass regression) ---
// Orb of Regret (BY0E8si926): "Banish CARDNAME: Shuffle up to three cards from your hand into your deck, then draw that many cards." Shuffling fewer than three (declining the 'Shuffle another card into deck?' prompt) must still shuffle the deck and draw one card per card already shuffled in.
// Regression: OrbOfRegretShuffle's loop re-queue (Custom/CardDQHandlers.php) omitted dontSkipOnPass, so ExecuteStaticMethods() skipped the handler when the player answered PASS and the draw never happened (hand 6 instead of 7). The first prompt (queued by the generated ability) needs no flag: declining with 0 cards shuffled draws nothing either way.
// The Orb is seeded on the field; the player passes priority to open the fast-action window, activates it (banish self), shuffles myHand-0, then declines the second offer.
// Notes carried over from the original definition:
//   - Orb of Regret -> myField-1
//   - activate Orb of Regret (banish self)
//   - shuffle one card into the deck
//   - decline shuffling another card
//   - pass the fast-action window opened after the ability resolved
//   - opponent passes too
gaScenario('orb-of-regret-decline-still-draws')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->seed('myField', 'Orb of Regret')
    ->endTurn()->answer(1, 'myField-1@Activate-0@Banish_self-_shuffle_up_to_3_from_hand,_draw_that_many')->pay(1)
    ->pass(1)->passBoth()
    ->tested('Orb of Regret')
    ->expectZoneCount('myHand', 7, 1, 'Hand: 7 - 1 shuffled away + 1 drawn (declining the 2nd offer still draws for the 1 card shuffled)')
    ->expectZoneCount('myDeck', 5, 1, 'Deck: 5 + 1 shuffled in - 1 drawn = 5')
    ->expectDqEmpty()
    ->register();

// --- Overflow the Barrow (OiyjVzW7Av): "As long as you have no cards in your graveyard, this card costs 2 less to activate. Put a haunt counter on your
// Phantasmagoria. Then put the top X cards from your deck into your graveyard, where X is the amount of haunt counters on your Phantasmagoria." ---
// Generated body milled with a loop of MZMove($player, "myDeck-0", "myGraveyard"): only the TOP card was ever milled (see 1676ae91 Pure Cytosynth).
// Phantasmagoria (D3rexaXCBo) is seeded in the mastery with 2 haunt counters: playing the card adds a third, so exactly three cards are milled.
// top four: Dungeon Guide, Fluffy Shopkeep, Fairy Whispers (the three milled), Charge the Soul (marker: must be the new top card)
// Notes carried over from the original definition:
//   - UMBRA lineage/element unlock
gaScenario('overflow-the-barrow-adds-haunt-then-mills-haunt-count')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Tristan, Shadowdancer'])
    ->seed('myMastery', 'Phantasmagoria', properties: ['Counters' => ['haunt' => 2]])
    ->seed('myHand', 'Overflow the Barrow')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Fairy Whispers', 'Charge the Soul'])
    ->castSpell('Overflow the Barrow', pay: 1)
    ->register();

// Ombreux Chevalier (crv1etn4g3): "On Enter: Return one of your omens to your hand. If you do, banish a card from your hand and put an omen counter on it."
// Notes carried over from the original definition:
//   - UMBRA lineage
//   - a Fluffy Shopkeep omen -> myBanish-0
//   - Ombreux Chevalier -> myHand-7
gaScenario('ombreux-chevalier-enter-swaps-an-omen-for-a-card-from-hand')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Tristan, Shadowdancer'], classes: 'GUARDIAN')
    ->seed('myBanish', 'Fluffy Shopkeep', properties: ['Counters' => ['omen' => 1]])
    ->seed('myHand', 'Ombreux Chevalier')
    ->play('Ombreux Chevalier')->pay(3)->target('myBanish-0')->pay(1)
    ->register();

// Negative twin of 'ornamental-greatsword-enter-ally-power': the champion's class does not match, so the [Class Bonus] must NOT apply.
// Notes carried over from the original definition:
//   - Dungeon Guide, the ally target
gaScenario('ornamental-greatsword-without-the-class-bonus-gives-no-power')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Ornamental Greatsword
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK
    )
    ->seed('myField', 'Dungeon Guide')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->target('myField-1')
    ->tested('Ornamental Greatsword')
    ->register();

gaAbilitySandbox('orb-of-regret-ability-1-pays-its-printed-cost', 'Orb of Regret', classes: 'CLERIC', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->pass(1)
    ->tested('Orb of Regret')
    ->expectCostFootprint(hand: 13, memory: 2, banish: 1, graveyard: 3, field: 6)
    ->register();

// Overpowering Defense (14hr8i5oix): "Activate this card only if a Guardian unit you control is attacking. Negate all card activations you don't control."
gaScenario('overpowering-defense-cannot-be-activated-without-an-attacking-guardian')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Ciel, Loyal Valet')
    ->seed('myHand', 'Overpowering Defense')
    ->play('Overpowering Defense')
    ->expectZoneCount('myHand', 8, 1, 'Refused: no Guardian unit is attacking, the card stays in hand')
    ->expectZoneCount('myMemory', 0, 1, 'Nothing was paid')
    ->expectDqEmpty()
    ->register();

// Notes carried over from the original definition:
//   - Ciel, Loyal Valet (GUARDIAN)
//   - Grande Aiguille -> myField-1
//   - Overpowering Defense -> p2 myHand-7
gaScenario('overpowering-defense-can-be-activated-while-a-guardian-is-attacking')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Ciel, Loyal Valet', player: 2)
    ->seed('myField', 'Grande Aiguille', player: 2)
    ->seed('myHand', 'Overpowering Defense', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-0!FSM!')->answer(2, 'myField-1')->answer(2, 'theirField-0')->answer(2, 'myHand-7')
    ->pay(2, 2)
    ->tested('Overpowering Defense')
    ->expectZoneCount('theirGraveyard', 1, 1, 'Overpowering Defense resolved')
    ->expectZoneCount('theirMemory', 2, 1, 'Its (2) was paid')
    ->expectDqEmpty()
    ->register();

// Orb of Regret (BY0E8si926): "Banish CARDNAME: Shuffle up to three cards from your hand into your deck, then draw that many cards." Picks the last hand card each time.
gaScenario('orb-of-regret-shuffles-three-chosen-cards-into-the-deck-and-draws-three')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Orb of Regret')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myHand-6')->target('myHand-5')->target('myHand-4')
    ->pass(1)
    ->tested('Orb of Regret')
    ->register();
