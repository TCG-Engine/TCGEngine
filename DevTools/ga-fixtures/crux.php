<?php
/**
 * Phase 3e (Crux). readyFor() enables the crux element (and the class bonus where asked) through the champion lineage. 'quiet' deck.
 */

// ---- Claimed From Beyond ----

gaScenario('claimed-from-beyond-banishes-a-non-champion-object-whose-type-is-among-the-banished-cards')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Claimed From Beyond')
    ->seed('myBanish', 'Dungeon Guide')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Claimed From Beyond')
    ->play('Claimed From Beyond')->pay(3)->target('theirField-1')->passBoth()
    ->expectCardExists('theirBanish', 'Giant Tortoise', 'An ally was banished: an ally card is among my banishment')
    ->tested('Claimed From Beyond')
    ->covers(['banish', 'condition', 'type-match', 'targeting'], 'Banish target non-champion object if one of its types matches one of the types among cards in your banishment.')
    ->register();

gaScenario('claimed-from-beyond-does-nothing-when-no-banished-card-shares-the-objects-type')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Claimed From Beyond')
    ->seed('myBanish', 'Dungeon Guide')
    ->seed('myField', 'Training Sword', player: 2)
    ->seed('myHand', 'Claimed From Beyond')
    ->play('Claimed From Beyond')->pay(3)->target('theirField-1')->passBoth()
    ->expectCardExists('theirField', 'Training Sword', 'A weapon, while only an ally is banished: it stays on the field')
    ->tested('Claimed From Beyond')
    ->covers(['banish', 'condition', 'type-match'], 'if one of its types matches one of the types among cards in your banishment')
    ->register();

// ---- Enveloping Soulmist ----

gaScenario('enveloping-soulmist-class-bonus-awake-champion-pays-two-less-and-gains-stealth-and-a-preparation-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Enveloping Soulmist', classBonus: true)
    ->patch('myField-0', ['Counters' => ['_overrides' => ['classes' => 'ASSASSIN']]])
    ->seed('myHand', 'Enveloping Soulmist')
    ->castSpell('Enveloping Soulmist', pay: 2, window: '')
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"ASSASSIN"},"preparation":1}', 'One preparation counter')
    ->expectProperty('p1Field-0', 'TurnEffects', '["STEALTH","vz4kc558yx-ACTION_ACTIVATED"]', 'Stealth until end of turn')
    ->tested('Enveloping Soulmist')
    ->covers(['class-bonus', 'cost-reduction', 'stealth', 'counter'], 'Your champion gains **stealth** until end of turn. Put a **preparation** counter on your champion.')
    ->register();

gaScenario('enveloping-soulmist-with-a-rested-champion-costs-its-full-four')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Enveloping Soulmist', classBonus: true)
    ->patch('myField-0', ['Status' => 1, 'Counters' => ['_overrides' => ['classes' => 'ASSASSIN']]])
    ->seed('myHand', 'Enveloping Soulmist')
    ->play('Enveloping Soulmist')->pay(2)
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"ASSASSIN"}}', 'Rested champion: two payments are not enough, nothing resolved')
    ->tested('Enveloping Soulmist')
    ->covers(['class-bonus', 'condition'], '[Class Bonus] As long as your champion is awake, this card costs 2 less to activate.')
    ->register();

// ---- Ethereal Absorption ----

gaScenario('ethereal-absorption-returns-a-regalia-as-a-cost-to-draw-and-gain-two-preparation-counters')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Ethereal Absorption')
    ->seed('myField', 'Training Sword')
    ->seed('myHand', 'Ethereal Absorption')
    ->play('Ethereal Absorption')->pay(1)->target('myField-1')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":2}', 'Two preparation counters')
    ->expectZoneCount('myMaterial', 5, 1, 'The Training Sword went back to the material deck')
    ->expectZoneCount('myHand', 7, 1, 'Eight in hand minus the spell and one payment, plus the drawn card (8 - 1 - 1 + 1)')
    ->tested('Ethereal Absorption')
    ->covers(['additional-cost', 'return-to-material', 'draw', 'counter'], 'As an additional cost to activate this card, return a regalia you control to its owner\'s material deck.', 'Draw a card, then put two **preparation** counters on your champion.')
    ->register();

// ---- Ghosts of Pendragon ----

gaScenario('ghosts-of-pendragon-enter-returns-a-regalia-to-the-material-deck-to-draw-two')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Ghosts of Pendragon')
    ->seed('myField', 'Training Sword')
    ->seed('myHand', 'Ghosts of Pendragon')
    ->play('Ghosts of Pendragon')->pay(2)->answer(1, 'myField-1')
    ->expectZoneCount('myMaterial', 5, 1, 'The Training Sword went back to the material deck')
    ->expectZoneCount('myHand', 7, 1, 'Eight in hand minus the ally and two payments, plus two drawn (8 - 1 - 2 + 2)')
    ->tested('Ghosts of Pendragon')
    ->covers(['on-enter', 'return-to-material', 'draw', 'optional-choice'], '**On Enter:** You may return a regalia you control to its owner\'s material deck. If you do, draw two cards.')
    ->register();

// ---- Sword of Avarice ----

gaScenario('sword-of-avarice-enter-draws-a-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sword of Avarice')
    ->seed('myField', 'Sword of Avarice')
    ->observe()
    ->expectZoneCount('myHand', 8, 1, 'One card drawn on enter (7 + 1)')
    ->tested('Sword of Avarice')
    ->covers(['on-enter', 'draw'], '**On Enter:** Draw a card.')
    ->register();

gaScenario('sword-of-avarice-class-bonus-enter-draws-two-cards-instead')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sword of Avarice', classBonus: true)
    ->seed('myField', 'Sword of Avarice')
    ->observe()
    ->expectZoneCount('myHand', 9, 1, 'Class Bonus: two cards drawn (7 + 2)')
    ->tested('Sword of Avarice')
    ->covers(['on-enter', 'draw', 'class-bonus'], '**Class Bonus:** Draw two cards instead.')
    ->register();

// ---- Votive Runeblade ----

$gaVotiveClause = '[Class Bonus] **On Attack**: You may sacrifice another Sword regalia. If you do, wake up the attacker.';

gaScenario('votive-runeblade-class-bonus-on-attack-sacrifices-another-sword-regalia-to-wake-the-attacker')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Votive Runeblade', classBonus: true)
    ->seed('myField', 'Votive Runeblade')
    ->seed('myField', 'Training Sword')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-1')->answer(1, 'theirField-0')->answer(1, 'myField-2')
    ->expectProperty('p1Field-0', 'Status', '2', 'The champion woke up after attacking')
    ->expectZoneCount('myField', 2, 1, 'The other Sword regalia was sacrificed; the champion and the Runeblade itself remain')
    ->expectCardExists('myField', 'Votive Runeblade', 'The Runeblade itself stayed (it was not offered)')
    ->tested('Votive Runeblade')->covers(['class-bonus', 'on-attack', 'sacrifice', 'wake-up'], $gaVotiveClause)->register();

gaScenario('votive-runeblade-without-another-sword-regalia-the-attacker-stays-rested')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Votive Runeblade', classBonus: true)
    ->seed('myField', 'Votive Runeblade')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-1')->answer(1, 'theirField-0')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'Status', '1', 'No other Sword regalia: no offer and the champion stays rested')
    ->tested('Votive Runeblade')->covers(['class-bonus', 'on-attack', 'condition'], $gaVotiveClause)->register();

// ---- Recursive Confidant ----

gaScenario('recursive-confidant-class-bonus-enter-lets-you-activate-a-warrior-attack-from-the-banishment')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Recursive Confidant', classBonus: true)
    ->seed('myBanish', 'Savage Swing')
    ->seed('myHand', 'Recursive Confidant')
    ->reachTurnThree()
    ->play('Recursive Confidant')->pay(2)->target('myBanish-0')
    ->action(1, 10002, 'myBanish-0!FSM!')->pay(2)->target('theirField-0')
    ->expectDamage('p2Field-0', 2, 'The banished Savage Swing was activated and hit for 2')
    ->tested('Recursive Confidant')
    ->covers(['class-bonus', 'on-enter', 'banishment-activation', 'attack'], '[Class Bonus] **On Enter:** Until end of turn, you may activate target Warrior attack card in your banishment.')
    ->register();

// ---- Merlin, Kingslayer ----

gaScenario('merlin-kingslayer-recollection-puts-a-level-counter-and-an-even-count-draws-and-boosts-attacks')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Merlin, Kingslayer')
    ->patch('myField-0', ['Counters' => ['level' => 1]])
    ->reachTurnThree()
    ->expectProperty('p1Field-0', 'Counters', '{"level":2}', 'A level counter was added: 1 to 2')
    ->expectZoneCount('myHand', 9, 1, 'Seven opening cards, the turn draw and the card drawn for the even count')
    ->tested('Merlin, Kingslayer')
    ->covers(['recollection', 'counter', 'draw', 'stat-change'], 'At the beginning of your recollection phase, put a **level** counter on CARDNAME. Then, if there\'s an even amount of **level** counters on CARDNAME, draw a card and CARDNAME\'s attacks get +2 [POWER] until end of turn.')
    ->register();

gaScenario('merlin-kingslayer-an-odd-level-counter-count-draws-nothing-extra')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Merlin, Kingslayer')
    ->reachTurnThree()
    ->expectProperty('p1Field-0', 'Counters', '{"level":1}', 'The first level counter: odd')
    ->expectZoneCount('myHand', 8, 1, 'Seven opening cards and the turn draw only')
    ->tested('Merlin, Kingslayer')
    ->covers(['recollection', 'counter', 'condition'], 'if there\'s an even amount of **level** counters on CARDNAME, draw a card')
    ->register();

// ---- The Majestic Spirit ----

if (!function_exists('gaMajestic')) {
    function gaMajestic(string $slug, bool $spirit): GaScenario {
        $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->readyFor('The Majestic Spirit');
        if ($spirit) $s->seed('myField', 'The Majestic Spirit');
        return $s->seed('myField', 'Ghosts of Pendragon')->answer(1, '-')
            ->seed('myHand', 'Off With Her Head', player: 2)->endTurn()
            ->play('Off With Her Head', 2)->pay(2, 2)->target($spirit ? 'theirField-2' : 'theirField-1', 2)->passBoth();
    }
}

gaMajestic('the-majestic-spirit-prevents-half-of-the-damage-to-another-crux-unit', true)
    ->expectDamage($spirit = 'p1Field-2', 1, 'Off With Her Head dealt 2: half (1) was prevented')
    ->tested('The Majestic Spirit')
    ->covers(['prevention', 'crux'], 'If another crux element unit you control would take damage, prevent half of that damage, rounded up.')
    ->register();

gaMajestic('without-the-majestic-spirit-the-crux-unit-takes-the-full-damage', false)
    ->expectDamage('p1Field-1', 2, 'No Majestic Spirit: the full 2 damage')
    ->tested('The Majestic Spirit')
    ->covers(['prevention', 'condition'], 'If another crux element unit you control would take damage, prevent half of that damage, rounded up.')
    ->register();

gaScenario('the-majestic-spirit-gives-your-champions-spellshroud')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('The Majestic Spirit')->readyFor('Shattering Discharge', player: 2)
    ->seed('myField', 'The Majestic Spirit')
    ->seed('myHand', 'Shattering Discharge', player: 2)
    ->endTurn()->answer(2, 'myHand-7')->pay(1, 2)
    ->rejects(2, 'theirField-0', 'The champion has spellshroud: a Spell cannot target it')
    ->tested('The Majestic Spirit')
    ->covers(['spellshroud', 'champion'], 'Champions you control have **spellshroud**.')
    ->register();

// ---- Spirit Blade: Ensoul ----

$gaEnsoulClause = 'Choose any amount of Sword weapon cards with memory cost 1 or less from your banishment and/or material deck and put them onto the field. Until end of turn, Sword weapons you control can attack as though they were allies. At the beginning of your next end phase, sacrifice all Sword weapons you control.';

gaScenario('spirit-blade-ensoul-puts-a-sword-from-the-banishment-onto-the-field-and-it-attacks-as-an-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Spirit Blade: Ensoul')
    ->seed('myBanish', 'Training Sword')
    ->seed('myHand', 'Spirit Blade: Ensoul')
    ->reachTurnThree()
    ->play('Spirit Blade: Ensoul')->pay(6)->answer(1, 'myBanish-0')->answer(1, '-')
    ->action(1, 10002, 'myField-1!FSM!')->answer(1, 'theirField-0')->pass(2)
    ->expectCardExists('myField', 'Training Sword', 'The Sword weapon from the banishment is on the field')
    ->expectDamage('p2Field-0', 1, 'The Sword weapon attacked as though it were an ally')
    ->tested('Spirit Blade: Ensoul')->covers(['zone-movement', 'sword', 'attack-as-ally'], $gaEnsoulClause)->register();

gaScenario('spirit-blade-ensoul-sacrifices-the-sword-weapons-at-the-beginning-of-the-end-phase')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Spirit Blade: Ensoul')
    ->seed('myBanish', 'Training Sword')
    ->seed('myHand', 'Spirit Blade: Ensoul')
    ->reachTurnThree()
    ->play('Spirit Blade: Ensoul')->pay(6)->answer(1, 'myBanish-0')->answer(1, '-')
    ->endTurn()
    ->expectZoneCount('myField', 1, 1, 'At the beginning of the end phase the Sword weapon was sacrificed')
    ->tested('Spirit Blade: Ensoul')->covers(['delayed-trigger', 'sacrifice', 'sword'], $gaEnsoulClause)->register();

// ---- Merlin, Surreal Figment ----

gaScenario('merlin-surreal-figment-merlin-bonus-on-attack-puts-a-sheen-counter-on-a-target-unit')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Merlin, Memorite Vassal')
    ->seed('myField', 'Merlin, Surreal Figment')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, 'theirField-0')
    ->expectProperty('p2Field-0', 'Counters', '{"sheen":1,"_champDamageThisTurn":2}', 'A sheen counter on the target (plus the damage tracker from Merlin\'s 2 damage)')
    ->tested('Merlin, Surreal Figment')
    ->covers(['merlin-bonus', 'on-attack', 'counter', 'sheen'], '[Merlin Bonus] **On Attack: **Put a **sheen** counter on target unit.')
    ->register();

gaScenario('merlin-surreal-figment-without-the-merlin-bonus-the-attack-puts-no-sheen-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Merlin, Surreal Figment')
    ->seed('myField', 'Merlin, Surreal Figment')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')
    ->expectDqEmpty()
    ->tested('Merlin, Surreal Figment')
    ->covers(['merlin-bonus', 'condition'], '[Merlin Bonus] **On Attack: **Put a **sheen** counter on target unit.')
    ->register();

// ---- Vorpal Sword ----

if (!function_exists('gaVorpal')) {
    function gaVorpal(string $slug, string $weapon, int $retaliation, string $label): void {
        $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')
            ->seed('myField', $weapon)
            ->seed('myField', 'Night Barker', player: 2, properties: ['Counters' => ['buff' => 2]])
            ->reachTurnThree()
            ->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-1')->answer(1, 'theirField-1');
        if ($retaliation > 0) $s->answer(2, 'myField-1');
        $s->expectDqEmpty()
          ->expectDamage('p1Field-0', $retaliation, $label)
          ->tested('Vorpal Sword')
          ->covers(['retaliate', 'specter', 'condition'], 'Specter allies can\'t retaliate.')
          ->register();
    }
}

gaVorpal('vorpal-sword-specter-allies-cannot-retaliate-against-its-attack', 'Vorpal Sword', 0, 'The buffed Night Barker (3 power) did not retaliate');
gaVorpal('without-vorpal-sword-the-specter-ally-retaliates', 'Training Sword', 3, 'Night Barker 1 power + 2 buff counters retaliated for 3');

// ---- Facet the Forgotten ----

if (!function_exists('gaFacet')) {
    function gaFacet(string $slug): GaScenario {
        return gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')
            ->champion(cardName: 'Merlin, Memorite Vassal', lineage: ['Merlin, Kingslayer'])
            ->seed('myMemory', 'Dungeon Guide', player: 2)
            ->seed('myHand', 'Facet the Forgotten')
            ->play('Facet the Forgotten')->pay(5)->answer(1, 'myTempZone-0')->answer(1, 'NO');
    }
}

$gaFacetClause = '[Merlin Bonus] **On Enter:** Look at target opponent\'s memory. You may activate a card from among them without paying its costs and ignoring its elemental requirements. If you do, your opponents can\'t play cards with the same name as the activated card for as long as you control CARDNAME.';

gaFacet('facet-the-forgotten-merlin-bonus-enter-activates-an-opposing-memory-card-for-free')
    ->expectCardExists('myField', 'Dungeon Guide', 'The opposing memory card entered under my control without payment')
    ->expectZoneCount('myHand', 2, 1, 'Eight in hand minus Facet and the five payments (nothing paid for the Dungeon Guide)')
    ->tested('Facet the Forgotten')->covers(['merlin-bonus', 'on-enter', 'free-activation', 'memory'], $gaFacetClause)->register();

gaFacet('facet-the-forgotten-opponents-cannot-play-cards-with-the-activated-cards-name')
    ->endTurn()->pass(2)->pass(2)
    ->action(2, 10002, 'myHand-0!FSM!')
    ->expectDqEmpty()
    ->expectZoneCount('myHand', 8, 2, 'Player 2 could not play a Dungeon Guide: nothing was started and the hand is untouched (7 + the turn draw)')
    ->tested('Facet the Forgotten')->covers(['merlin-bonus', 'play-restriction'], $gaFacetClause)->register();
